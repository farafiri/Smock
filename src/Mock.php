<?php

namespace Mock;

use Mock\Utils\Utils;

class Mock
{
    const VAR_NAME_PREFIX = 'UN_';
    protected $ct = 0;
    protected $parser;
    protected $checkCode;
    protected $useAliases = array();
	static protected $instance;
	
    public function __construct()
	{
	    $this->parser = new MockParser();
        $this->errorAndChecks = new ErrorsAndChecks();
        $this->builder = new PHPUnitMockBuilder();
	}

    protected function bindClosuresOn()
    {
        return false;
    }

    public function useAlias($className, $alias = null)
    {
        if ($alias === null) {
            $explodedClassName = explode('\\', $className);
            $alias = $explodedClassName[count($explodedClassName) - 1];
        }

        $this->useAliases[$alias] = $className;
    }

    public function releaseAlias($alias)
    {
        unset($this->useAliases[$alias]);
    }

	static public function getInstance()
	{
	    if (!static::$instance) {
	        static::$instance = new static();
	    }

		return static::$instance;
	}
	
	public function buildMock($className, $constructorArgs, $methods, $properties)
	{
	    return new StrMock($className, $constructorArgs, $methods, $properties);
	}
	
	public function get($input, $testCase, $extraData = array())
	{
        $hackAccess = new \Mock\Utils\HackAccess($testCase, false);
        // $data is used in eval code
        if (is_array($extraData) || $extraData instanceof \ArrayAccess) {
            $data = new DataContainer(array($extraData, $hackAccess));
        } else {
            $data = new DataContainer(array($hackAccess));
        }

	    //builder is used in eval code
        $builder = $this->builder;
        $builder->setTestCase($testCase);
        $builder->reset();
		$mockReturnCode = $this->getMockReturnCode($input);
		//echo "\n\n$mockReturnCode\n";
        return eval($mockReturnCode);
	}
	
	public function getMockReturnCode($input)
	{
	    $parsedInput = $this->parser->parse($input);
		if (!$parsedInput) {
		    $error = $this->parser->getError();

            $posData = \ParserGenerator\Parser::getLineAndCharacterFromOffset($input, $error['index']);

            $expected = implode(' or ', $this->parser->generalizeErrors($error['expected']));
            $foundLength = 20;
            $found = substr($input, $error['index']);
            if (strlen($found) > $foundLength) {
                $found = substr($found, 0, $foundLength) . '...';
            }
            throw new MockBuildException("Given grammar is incorrect:\nline: " . $posData['line'] . ', character: ' . $posData['char'] . "\nexpected: " . $expected . "\nfound: " . $found);
        }
	    return $this->buildWholeCode($parsedInput);
	}
	
	protected function buildWholeCode($parsedInput)
	{
	    $parsedInput->refreshOwners();
        $this->errorAndChecks->checkCode = '';
        $uniqueValues = array_map(function($node) {return $node->getSubnode(1);}, $parsedInput->findAll('value:uniq'));
        $addUniqueCode = '$builder->setUniqueValues(' . implode(',', $uniqueValues) . ');';

	    $mockCode = $this->getReturnCodeOf($parsedInput->getSubnode(0), array('variables' => array(), 'currentObjectVarName' => null, 'aliases' => $this->useAliases, 'templates' => array()));
		return $this->errorAndChecks->checkCode . $addUniqueCode . $mockCode;
	}
	
	protected function wrappIntoClosure($node, $context)
	{
	    return 'call_user_func(' . $this->getFunctionHeader($context) . '{' . $this->getReturnCodeOf($node, $context) . '})';
	}
	
	protected function getFunctionHeader($context, $paramsStr = '')
	{
	    $variables = $context['variables'];
		if (!empty($context['currentObjectVarName']) && !in_array($context['currentObjectVarName'], $context['variables'])) {
			$variables[] = $context['currentObjectVarName'];
		}
		
		$contextVariablesStr = implode('', array_map(function($a) {return ',&$' . $a;}, $variables));
		
		return 'function(' . $paramsStr . ') use($builder,&$data' . $contextVariablesStr . ')';
	}

    /**
     * this function should be protected but i must use it in closure
     *
     * @param $className string
     * @param $context   array
     * @return string
     */
    public function getClassName($className, $context)
    {
        return isset($context['aliases'][$className]) ? $context['aliases'][$className] : $className;
    }

    protected function updateContext(&$context, $nodes)
    {
        foreach($nodes as $node) {
            switch ($node->getType()) {
                case 'classAlias':
                    $className = (string) $node->getSubnode(2);
                    if ($node->getSubnode(5) !== null) {
                        $alias = (string) $node->getSubnode(5);
                    } else {
                        $explodedClassName = explode("\\", $className);
                        $alias = $explodedClassName[count($explodedClassName) - 1];
                    }

                    $context['aliases'][$alias] = $this->getClassName($className, $context);

                    break;
                case 'template':
                    $templateName = $node->getSubnode(2);
                    $templateNode = $node->getSubnode(4);
                    $context['templates'][(string) $templateName] = $templateNode;

                    break;
            }
        }
    }
	
	protected function getValueOf($node, $context, $mustReturnFullExpresion = true, $doTypeCheck = false)
	{
        if ($doTypeCheck) {
            $subnode0 = $node->getSubnode(0);
            $isConst = $subnode0->isBranch() && in_array($subnode0->getDetailType(), array('classConst', 'simple'));
            return $this->checkMethodReturnsCorrectValueType($context, $this->getValueOf($node, $context, $mustReturnFullExpresion), $isConst);
        }

	    switch ($node->getType() . ':' . $node->getDetailType()) {
		    case "value:get":
			    return $this->wrappIntoClosure($node, $context);
		    case "exprValue:array":
			    return $this->getValueOf($node->getSubnode(0), $context);
		    case "exprValue:simple":
                return $node->toString();
			case "exprValue:classConst":
                $className = $this->getClassName($node->getSubnode(0)->toString(), $context);
                $constName = (string) $node->getSubnode(2);
                $this->errorAndChecks->checkClassConst($className, $constName, $node);
                return '\\' . $className . '::' . $constName;
			case "exprValue:classVar":
                $className = $this->getClassName($node->getSubnode(0)->toString(), $context);
                $constName = (string) $node->getSubnode(2);
                $this->errorAndChecks->checkClassVar($className, $constName, $node);
                return '\\' . $className . '::$' . $constName;
			case "exprValue:ref":
			    $varName = substr((string) $node, 1);
			    if (in_array($varName, $context['variables']) || ($varName === $context['currentObjectVarName'])) {
					return '$' . $varName;
				} else {
                    $this->errorAndChecks->checkDataKey($varName);
					return '$data->get(\'' . $varName . '\')';
				}
			case "value:expr":
			    return $this->getValueOf($node->getSubnode(0), $context);
            case "value:regex":
                return "\$builder->generateUniqueValue('$node')";
            case "value:uniq":
                return $node->getSubnode(1)->toString();
			case "value:class":

			    if ($mustReturnFullExpresion) {
				    return $this->wrappIntoClosure($node, $context);
				}
                $this->updateContext($context, $node->getSubnode(0)->getMainNodes());
                return $this->getValueOf($node->getSubnode(1), $context);
            case "class:0":
                $classId = $node->getSubnode(0)->toString();
                $extendedByTemplates = isset($node->extendedByTemplates) ? $node->extendedByTemplates : array();
                if (isset($context['templates'][$classId]) && !in_array($classId, $extendedByTemplates)) {
                    $resultNode = $context['templates'][$classId]->copy();
                    $resultNode->extendedByTemplates = array_merge($extendedByTemplates, array($classId));
                    $this->extendTemplateNodeWithClassNode($resultNode, $node);
                    return $this->getValueOf($resultNode, $context, $mustReturnFullExpresion);
                }

			    $className = $this->getClassName($classId, $context);

                $this->errorAndChecks->checkClass($className);
				$context['currentObjectClassName'] = $className;
				
				$args = null;
				if ($node->getNestedSubnode(1, 0) !== null) {
				    $args = array();
				    foreach($node->getSubnode(1)->getSubnode(1)->getMainNodes() as $argNode) {
					    $args[] = $this->getValueOf($argNode, $context);
					}
				}
				
				$methodsNodes = $node->getSubnode(3)->getMainNodes();
				$methods = array();

                $default = $node->getNestedSubnode(2, 2) ? $node->getSubnode(2)->getSubnode(2) : null;

                $methodNames = array();
				
				foreach($methodsNodes as $methodNode) {
				    if ($methodNode->getDetailType() === 'cases') {
						$methodName = $methodNode->getSubnode(1)->toString();
                        if (isset($methodNames[$methodName])) {
                            $this->errorAndChecks->errorRepeatedMethod($methodName, $methodNode);
                        } else {
                            $methodNames[$methodName] = true;
                        }
                        $this->errorAndChecks->checkMethod($className, $methodNode);
						$context['currentMethodName'] = $methodName;
						$methodContent = $this->getFunctionHeader($context, '$invocation') . '{$args = $invocation->parameters;';
						foreach($methodNode->getSubnode(2)->getSubnodes() as $case) {
							$methodContent .= $this->createCaseBody($case, $context);
						}

                        if ($default) {
                            $methodContent .= $this->getMethodExecuteCode($default, $context);
                        } else {
						    $methodContent .= "throw new \\PHPUnit_Framework_AssertionFailedError('Invalid parameters for method');";
                        }
                        $methodContent .= '}';
                        $methods[$methodName] = $methodContent;
					} elseif ($methodNode->getDetailType() === 'undefined') {
					    $methodName = $methodNode->getSubnode(1)->toString();
						$methods[$methodName] = 'null';
					}
				}

                $context['currentMethodName'] = null;
                $properties = array();
                $propertyNodes = $node->getSubnode(4)->getMainNodes();
                foreach($propertyNodes as $propertyNode) {
                    $propertyName = substr($propertyNode->getSubnode(0)->toString(), 1);
                    $properties[$propertyName] = $this->getValueOf($propertyNode->getSubnode(2), $context);
                }
				
				return $this->buildMock($className, $args, $methods, $properties);
			case "array:0":
			    $result = '';
				foreach($node->getSubnode(1)->getMainNodes() as $arrayValueNode) {
				    switch ($arrayValueNode->getDetailType()) {
					    case "simple":
						    $key = null;
							$value = $this->getValueOf($arrayValueNode->getSubnode(0), $context);
							break;
						case "keya":
						    $key = "'" . $arrayValueNode->getSubnode(0) . "'";
							$value = $this->getValueOf($arrayValueNode->getSubnode(2), $context);
							break;
						case "keyb":
						    $key = $this->getValueOf($arrayValueNode->getSubnode(0), $context);
							$value = $this->getValueOf($arrayValueNode->getSubnode(2), $context);
							break;
					}
					
					$result .= $result ? ',' : 'array(';
					$result .= ($key === null) ? $value : ($key . '=>' . $value);
				}
				return $result . ')';
		}
	}

    protected function extendTemplateNodeWithClassNode($templateNode, $classNode)
    {
        //constructor
        if ($classNode->getNestedSubnode(1, 0)) {
            $templateNode->setSubnode(1, $classNode->getSubnode(1)->copy());
            $templateNode->getSubnode(1)->owner = $templateNode;
        }

        //default method behaviour
        if ($classNode->getNestedSubnode(2, 0)) {
            $templateNode->setSubnode(2,  $classNode->getSubnode(2)->copy());
            $templateNode->getSubnode(2)->owner = $templateNode;
        }

        //methods
        $getName = function ($node) {
            return $node->getSubnode(1)->toString();
        };

        $templateMethodsNode = $templateNode->getSubnode(3);
        $methodsToAdd = array();
        foreach(Utils::groupBy(array_merge($classNode->getSubnode(3)->getSubnodes(), $templateMethodsNode->getSubnodes()), $getName) as $methodName => $methodNodes) {
            if (count($methodNodes) == 1) {
                if ($methodNodes[0]->owner !== $templateMethodsNode) {
                    $methodsToAdd[] = $methodNodes[0]->copy();
                }
            } else {
                $methods = array();
                foreach ($methodNodes as $methodNode) {
                    if (isset($methods[$methodNode->owner === $templateMethodsNode])) {
                        $this->errorAndChecks->errorRepeatedMethod($methodName, $methodNode);
                    }
                    $methods[$methodNode->owner === $templateMethodsNode] = $methodNode;
                }

                if ($methods[true]->getDetailType() !== 'cases' || $methods[false]->getDetailType() !== 'cases') {
                    //template is completely overriden by classNode
                    $methods[true]->setDetailType($methods[false]->getDetailType());
                    $methods[true]->setSubnodes($methods[false]->copy()->getSubnodes());
                    $methods[true]->refreshOwners(false);
                } else {
                    $templateCasesNode = $methods[true]->getSubnode(2);
                    $classCases = array();
                    foreach($methods[false]->getSubnode(2)->getSubnodes() as $classCase) {
                        $classCaseCopy = $classCase->copy();
                        $classCaseCopy->owner = $templateCasesNode;
                        $classCases[] = $classCaseCopy;
                    }
                    $subnodes = $templateCasesNode->getSubnodes();
                    array_splice($subnodes, 0, 0, $classCases);
                    $templateCasesNode->setSubnodes($subnodes);
                }
            }
        }

        $subnodes = $templateMethodsNode->getSubnodes();
        array_splice($subnodes, 0, 0, $methodsToAdd);
        $templateMethodsNode->setSubnodes($subnodes);

        $templateMethodsNode->refreshOwners(false);

        $getName = function ($node) {
            return $node->getSubnode(0)->toString();
        };

        $templatePropertiesNode = $templateNode->getSubnode(4);

        foreach(Utils::groupBy(array_merge($classNode->getSubnode(4)->getSubnodes(), $templatePropertiesNode->getSubnodes()), $getName) as $propertyName => $propertyNodes) {
            if (count($propertyNodes) == 1) {
                if ($propertyNodes[0]->owner !== $templatePropertiesNode) {
                    $templatePropertiesNode->setSubnode(null, $propertyNodes[0]->copy());
                }
            } else {
                $properties = array();
                foreach ($propertyNodes as $propertyNode) {
                    if (isset($properties[$propertyNode->owner === $templatePropertiesNode])) {
                        $this->errorAndChecks->errorRepeatedProperty($propertyName, $propertyNode);
                    }
                    $properties[$propertyNode->owner === $templatePropertiesNode] = $propertyNode;
                }

                $properties[true]->setSubnodes($properties[false]->copy()->getSubnodes());
                $properties[true]->refreshOwners(false);
            }
        }

        $templatePropertiesNode->refreshOwners(false);
    }
	
    protected function generateVarName()
	{
	    return static::VAR_NAME_PREFIX . (++$this->ct);
	}
	
	protected function isGeneratedVarName($varName)
	{
	    return strpos($varName, static::VAR_NAME_PREFIX) === 0;
	}
	
	protected function getReturnCodeOf($node, $context, $doTypeCheck = false)
	{
	    switch ($node->getDetailType()) {
		    case "get":
                $this->updateContext($context, $node->getSubnode(0)->getMainNodes());
			    $node = $node->getSubnode(1);
                //Alias name after (get ... as $alias) is optional but we need it so we are going to made it up
                //$node->getSubnode(6)
				if ($node->getSubnode(6) === null) {
				    $node->setSubnode(6, '$' . $this->generateVarName());
				}
                $result = '';
			    $all = array_merge($node->getSubnode(0)->getSubnodes(), array($node));
                // Preparing variables (setting to null). If we want to redefine context var then we lose reference by unset
				foreach($all as $singleClass) {
				    $dolarAlias = (string) $singleClass->getSubnode(6);
					
					$aliasWithoutDolar = substr($dolarAlias, 1);
					if (in_array($aliasWithoutDolar, $context['variables']) || $context['currentObjectVarName'] === $aliasWithoutDolar) {
					    $result = "unset($dolarAlias);";
					}
				    $result .= $dolarAlias . '=null;';
					if (!in_array($aliasWithoutDolar, $context['variables'])) {
					    $context['variables'][] = $aliasWithoutDolar;
					}
				}
                //setting variables to objects
			    foreach($all as $singleClass) {
				    $dolarAlias = (string) $singleClass->getSubnode(6);
					$context['currentObjectVarName'] = substr($dolarAlias, 1);
					$result .= $dolarAlias . '=' . $this->getValueOf($singleClass->getSubnode(3), $context, false) . ';';
				}
				$result .= 'return ' . $dolarAlias . ';';
				return $result;
		    case "class":
			    $varName = $this->generateVarName();
				$context['currentObjectVarName'] = $varName;
				$objectCode = $this->getValueOf($node, $context, false, $doTypeCheck);
                return "\$$varName=null;\$$varName=$objectCode;return \$$varName;";
		    default:
                $valueOf = $this->getValueOf($node, $context, true, $doTypeCheck);
			    return "return $valueOf;";
		}
	}

    protected function buildParamCondition($node, $context, $checked)
    {
        switch ($node->getDetailType()) {
            case "logicOp":
                $operator = (string) $node->getSubnode(1);
                if ($operator === 'and') {
                    $operator = '&&';
                }
                if ($operator === 'or') {
                    $operator = '||';
                }

                return $this->buildParamCondition($node->getSubnode(0), $context, $checked) . $operator . $this->buildParamCondition($node->getSubnode(2), $context, $checked);
            case "bracket":
                return "(" . $this->buildParamCondition($node->getSubnode(1), $context, $checked) . ")";
            case "type":
                $type = (string) $node;
                if ($type == 'boolean') {
                    $type = 'bool';
                }

                return "is_$type($checked)";
            case "class":
                if (empty($context['isStringMatch'])) {
                    $className = $this->getClassName((string) $node->getSubnode(0), $context);
                    $classConstrains = array("(($checked) instanceof $className)");
                    if($node->getNestedSubnode(1, 1)) {
                        foreach($node->getSubnode(1)->getSubnode(1)->getMainNodes() as $keyVal) {
                            $key = (string) $keyVal->getSubnode(0);
                            $key = "\$builder->getObjectKeyValue('$className', $checked, '$key')";
                            $classConstrains[] = $this->buildParamCondition($keyVal->getSubnode(2), $context, $key);
                        }
                    }
                    return implode('&&', $classConstrains);
                } else {
                    return "($checked == '" . $node . "')";
                };
            case 'match':
                $pattern = $this->getValueOf($node->getSubnode(1), $context);
                return "\$builder->match($checked,$pattern,\$invocation)";
            case "not":
                return '!(' . $this->buildParamCondition($node->getSubnode(1), $context, $checked) . ')';
            case "array":
                $captureAll = (string) $node->getSubnode(2) ? 'false' : 'true';

                $checks = array();
                foreach($node->getSubnode(1)->getMainNodes() as $keyValuePattern) {
                    $context['isStringMatch'] = true;
                    $keyCheck = $this->buildParamCondition($keyValuePattern->getSubnode(0), $context, '$key');
                    $context['isStringMatch'] = false;
                    $valueCheck = $this->buildParamCondition($keyValuePattern->getSubnode(2), $context, '$value');
                    $checks[] = $this->getFunctionHeader($context, '$key,$value') . '{return (' . $keyCheck . ')&&(' . $valueCheck . ');}';
                }

                $checks = implode(',', $checks);
                return "\$builder->arrayCheck($checked, array($checks), $captureAll)";
            case "comparison":
                $operator = (string)$node->getSubnode(0) ?: '===';
                $operator = str_replace('x', '', $operator);
                return  $checked . $operator . $this->getValueOf($node->getSubnode(1), $context);
            case "regex":
                $escapedRegex = addslashes($node->toString());
                return "preg_match('$escapedRegex',$checked)";
            case "anything":
                return "true";
        }
    }
	
	protected function createCaseBody($case, $context)
	{
	    $conditions = array();
	    if ($case->getNestedSubnode(0, 0)) {
		    $params = $case->getSubnode(0)->getSubnode(1)->getMainNodes();
			$conditions[] = 'count($args)==' . count($params);
			foreach($params as $index => $param) {
                $condition = $this->buildParamCondition($param, $context, '$args[' . $index . ']');
                if ($condition !== 'true') {
			        $conditions[] = $condition;
                }
			}
		}
        $methodExecute = $this->getMethodExecuteCode($case->getSubnode(3), $context);
		
		if (count($conditions)) {
		    $result = 'if(' . implode('&&', $conditions) . '){' . $methodExecute . '};';
		} else {
		    $result = $methodExecute;
		}
		
		return $result;
	}

    protected function getMethodExecuteCode($methodExecute, $context)
    {
        $methodExecuteCode = 'return null;';
        switch ($methodExecute->getDetailType()) {
            case 'origin':
                $methodExecuteCode = '$r=new \\ReflectionMethod($invocation->className,$invocation->methodName);';
                $methodExecuteCode .= '$r->setAccessible(true);return $r->invokeArgs($' . $context['currentObjectVarName'] . ',$args);';
                break;
            case 'this':
                $currentObjectVarName = $context['currentObjectVarName'];
                $methodExecuteCode = 'return $' . $currentObjectVarName . ';';
                break;
            case 'consecutive':
                $consecutiveNodes = $methodExecute->getSubnode(2)->getMainNodes();
                $methodExecuteCode = 'switch ($builder->getConsecutiveCallIndex($' . $context['currentObjectVarName'] . ', $invocation->methodName)) {';
                foreach($consecutiveNodes as $index => $consecutiveNode) {
                    $methodExecuteCode .= ($index + 1 == count($consecutiveNodes)) ? 'default: ' : "case $index: ";
                    $methodExecuteCode .= $this->getMethodExecuteCode($consecutiveNode, $context);
                }
                $methodExecuteCode .= '};';
                break;
            case 'args':
                $methodExecuteCode = 'return ' . $methodExecute . ';';
                break;
            case 'phpUnit':
                //$methodExecuteCode = 'return call_user_func(array(' . $this->getValueOf($methodExecute->getSubnode(2), $context) . ',"invoke"),$invocation);';
                $methodExecuteCode = '$__DATA = ' . $this->getValueOf($methodExecute->getSubnode(2), $context) . ';return $__DATA->invoke($invocation);';
                break;
            case 'call':
                $methodExecuteCode = 'return call_user_func_array(' . $this->getValueOf($methodExecute->getSubnode(1), $context) . ',$args);';
                break;
            case 'once':
                $currentObjectVarName = $context['currentObjectVarName'];
                if ($currentObjectVarName && !$this->isGeneratedVarName($currentObjectVarName) && !in_array($currentObjectVarName, $context['variables'])) {
                    $context['variables'][] = $currentObjectVarName;
                }
                $cacheKey = ++$this->ct;
                $code = $this->getValueOf($methodExecute->getSubnode(2), $context, true, true);
                $methodExecuteCode = "if(!\$builder->onceCacheHas(\$$currentObjectVarName, $cacheKey)){\$builder->onceCacheSet(\$$currentObjectVarName,$cacheKey,$code);}";
                $methodExecuteCode .= "return \$builder->onceCacheGet(\$$currentObjectVarName, $cacheKey);";
                break;
            case 'value':
                $currentObjectVarName = $context['currentObjectVarName'];
                if ($currentObjectVarName && !$this->isGeneratedVarName($currentObjectVarName) && !in_array($currentObjectVarName, $context['variables'])) {
                    $context['variables'][] = $currentObjectVarName;
                }
                $methodExecuteCode = $this->getReturnCodeOf($methodExecute->getSubnode(0), $context, true);
                break;
            case 'fail':
                $failLocation = ' failed';
                $userMessage = $methodExecute->getNestedSubnode(1, 0) ? $methodExecute->getSubnode(1)->getSubnode(0)->getValue() : null;
                $escapedMessage = addslashes(($userMessage ?: ''). $failLocation);
                $methodExecuteCode = "throw new \\PHPUnit_Framework_AssertionFailedError('$escapedMessage');";
                break;
            case 'throw':
                $exceptionClass = $methodExecute->getSubnode(1)->toString();
                $methodExecuteCode = "throw new \\$exceptionClass();";
                break;
            case 'phpCode':
                $methodExecuteCode = $this->getCodeFromPhpCodeNode($methodExecute, $context);
        }

        return $methodExecuteCode;
    }

    protected function getCodeFromPhpCodeNode($methodExecute, $context)
    {
        $startStr = '';
        $methodExecute = $methodExecute->copy();
        $that = $this;

        /* only direct assignment makes local variable
         * $q = 45; //makes local variable
         * $q[1] = 45; //tries to use data
         */
        foreach($methodExecute->findAll('phpExpr:assign', true) as $assignNode) {
            if ($assignNode->getSubnode(0)->getDetailType() == 'var') {
                $phpVarNode = $assignNode->getSubnode(0)->getSubnode(0);
                $context['variables'][] = (string) $phpVarNode->getSubnode(1);
            }
        }

        $methodExecute->inPlaceTranslate('phpVar', function ($node) use ($context) {
            $varName = (string) $node->getSubnode(1);

            if ($varName === 'this' || $varName === 'args' || in_array($varName, $context['variables'])) {
                return null;
            }

            $node->getSubnode(1)->getSubnode(0)->setContent("data->get('$varName')");
        });

        $methodExecute->inPlaceTranslate('className', function ($node) use ($context, $that) {
            $node->getSubnode(0)->setContent('\\' . $that->getClassName((string) $node, $context));
        });

        if (!$this->bindClosuresOn() && empty($context['doNotEmulateThis'])) {
            $methodExecute->inPlaceTranslate('phpVar', function ($node) use ($context) {
                if ((string) $node !== '$this') {
                    return null;
                }

                if (isset($node->owner) &&
                    isset($node->owner->owner) &&
                    $node->owner->owner->getType() == 'phpExprElement' &&
                    in_array($node->owner->owner->getDetailType(), array('method', 'property'))) {

                    $node->getSubnode(1)->getSubnode(0)->setContent("__this__hackAccess");
                } else {
                    $node->getSubnode(1)->getSubnode(0)->setContent($context['currentObjectVarName']);
                }
            });

            $startStr = '$__this__hackAccess = new \Mock\Utils\HackAccess($' . $context['currentObjectVarName'] . ');';
        }

        $methodExecute->inPlaceTranslate('phpStatement:return', function ($node) use ($context, $that) {
            return 'return ' . $that->checkMethodReturnsCorrectValueType($context, (string) $node->getSubnode(2), false) . ';';
        });

        return $startStr . $methodExecute->toString(\ParserGenerator\SyntaxTreeNode\Base::TO_STRING_REDUCED_WHITESPACES);
    }

    public function checkMethodReturnsCorrectValueType($context, $valueCode, $isConstExpr)
    {
        $cName = isset($context['currentObjectClassName']) ? $context['currentObjectClassName'] : null;
        $mName = isset($context['currentMethodName']) ? $context['currentMethodName'] : null;
        if (substr($mName, 0, 1) === '/' || substr($mName, 0, 1) === '*') {
            $mName = null;
        }
        return $this->errorAndChecks->checkMethodReturnsCorrectValueType($cName, $mName, $valueCode, $isConstExpr);
    }
}