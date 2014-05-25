<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 03.12.13
 * Time: 11:57
 */

namespace Mock;

class PHPUnitMockBuilder
{
    protected $base = null;
    protected $onceCache = array();
    protected $uniqueValues = array();
    protected $consecutiveCallIndex = array();

    public function __construct()
    {
        $this->docGetter = new Utils\DocGetter();
        $this->classNameResolver = new Utils\RealClassNameResolver(new Utils\UseGetter());
        $this->typeMatchBuilder = new Utils\TypeMatcher\TypeMatcherBuilder();
    }

    public function reset()
    {
        $this->uniqueValues = array();
        $this->consecutiveCallIndex = array();
    }

    public function setTestCase($testCase)
    {
        $this->base = $testCase;
    }

    public function assertClass($className)
    {
        if (!class_exists($className) && !interface_exists($className)) {
            throw new MockBuildException("Not existing class/interface $className used");
        }
    }

    public function assertDataKey($data, $key)
    {
        if (!$data->exists($key)) {
            throw new MockBuildException("Key $key in data must be set");
        }
    }

    public function getConsecutiveCallIndex($object, $methodName)
    {
        $key = spl_object_hash($object) . '/' . $methodName;
        if (isset($this->consecutiveCallIndex[$key])) {
            $this->consecutiveCallIndex[$key]++;
        } else {
            $this->consecutiveCallIndex[$key] = 0;
        }

        return $this->consecutiveCallIndex[$key];
    }

    public function generateUniqueValue($regex)
    {
        $result = null;
        $limit = 1000;
        while(($result === null || isset($this->uniqueValues[$result])) && --$limit) {
            $result = \ParserGenerator\RegexUtil::getInstance()->generateString($regex);
        }
        $this->uniqueValues[$result] = true;

        if ($limit == 0) {
            $this->base->fail('Cannot generate new unique value from given regular expression');
        }

        return $result;
    }

    public function setUniqueValues()
    {
        $this->uniqueValues = array();

        foreach(func_get_args() as $value) {
            $this->uniqueValues[$value] = true;
        }
    }

    public function assertClassConstant($className, $constName)
    {
        if (!defined($className . '::' . $constName)) {
            throw new MockBuildException("Class constant $className::$constName not defined");
        }
    }

    public function assertClassVar($className, $varName)
    {
        if (!property_exists($className, $varName)) {
            throw new MockBuildException("Class variable $className::$varName not defined");
        }

        $refClass = new \ReflectionClass($className);
        $refProperty = $refClass->getProperty($varName);
        if (!$refProperty->isPublic()) {
            throw new MockBuildException("Class variable $className::$varName is not public");
        }
    }

    public function assertMethod($className, $methodName)
    {
        if (interface_exists($className)) {
            if (!method_exists($className, $methodName)) {
                throw new MockBuildException("Attempt of redefinig non existing interface method $className::$methodName");
            }
        } else {
            if (!method_exists($className, $methodName)) {
                throw new MockBuildException("Attempt of redefinig non existing class method $className::$methodName");
            }
            $method = new \ReflectionMethod($className, $methodName);
            if ($method->isPrivate()) {
                throw new MockBuildException("Attempt of redefinig private method $className::$methodName");
            }
        }
    }

    public function assertMethodReturnsCorrectValueType($className, $methodName, $value)
    {
        $doc = $this->docGetter->fullGet($className, $methodName, Utils\DocGetter::R_METHOD, 'return');
        if ($doc) {
            $classNameResolver = $this->classNameResolver;
            $sourceClass = $doc['source'];
            $fullReturnDoc = preg_replace_callback('/[\\\\A-Za-z0-9_]+/', function($a) use ($classNameResolver, $sourceClass) {
                if (in_array($a[0], array('integer', 'int', 'boolean', 'bool', 'float', 'real', 'double', 'string', 'resource', 'callable', 'array', 'object', 'mixed'))) {
                    return $a[0];
                }
                return $classNameResolver->resolve($a[0], $sourceClass);
            } , $doc['value']);

            if (!$this->typeMatchBuilder->get($fullReturnDoc)->match($value)) {
                $type = gettype($value);
                throw new MockBuildException("Incorrect type of value($type) returned by mocked method $className::$methodName. Expected $fullReturnDoc");
            }
        }

        return $value;
    }

    protected function buildMethodsCallbacks($className, $methods) {
        $methodCallbacks = array();

        $methodsBySelector = array();

        foreach($methods as $name => $callback) {
            if ($name[0] === '/' || $name === '*') {
                $methodsBySelector[$name] = $callback;
            } else {
                $methodCallbacks[$name] = $callback;
            }
        }

        if ($methodsBySelector) {
            $classMethods = array_diff(get_class_methods($className), array_keys($methodCallbacks));
            foreach($classMethods as $methodName) {
                foreach($methodsBySelector as $selector => $callback) {
                    if ($selector === '*' || preg_match($selector, $methodName)) {
                        $methodCallbacks[$methodName] = $callback;
                        break;
                    }
                }
            }
        }

        return $methodCallbacks;
    }

    public function buildMock($className, $constructorArgs, $methods, $properties)
    {
        $mockBuilder = $this->base->getMockBuilder($className);

        if ($constructorArgs === null) {
            $mockBuilder->disableOriginalConstructor();
        } else {
            $mockBuilder->setConstructorArgs($constructorArgs);
        }

        $methodCallbacks = $this->buildMethodsCallbacks($className, $methods);

        $methodNames = array_keys($methodCallbacks);
        $mockBuilder->setMethods($methodNames ?: array('____someNotExistingMethod'));

        $mock = $mockBuilder->getMock();

        foreach($methodCallbacks as $name => $method) {
            if ($method) {
                $mock->expects($this->base->any())
                    ->method($name)
                    ->will(new MethodStub($method));
            }
        }

        foreach($properties as $name => $value) {
            $ref = new \ReflectionProperty($className, $name);
            $ref->setAccessible(true);
            $ref->setValue($mock, $value);
        }

        return $mock;
    }

    public function onceCacheHas($obj, $hash)
    {
        return array_key_exists(spl_object_hash($obj) . '_' . $hash, $this->onceCache);
    }

    public function onceCacheGet($obj, $hash)
    {
        return $this->onceCache[spl_object_hash($obj) . '_' . $hash];
    }

    public function onceCacheSet($obj, $hash, $value)
    {
        $this->onceCache[spl_object_hash($obj) . '_' . $hash] = $value;
    }

    public function arrayCheck($arr, $checks, $captureAll)
    {
        if (!is_array($arr)) {
            return false;
        }
        $capturedKeys = array();
        foreach($checks as $check) {
            $checked = false;
            foreach($arr as $key => $value) {
                if ($check($key, $value)) {
                    $checked = true;
                    if ($captureAll) {
                        $capturedKeys[$key] = $value;
                    } else {
                        break;
                    }
                }
            }
            if (!$checked) {
                return false;
            }
        }

        return !$captureAll || $arr == $capturedKeys;
    }

    public function match($value, $pattern, $invocation)
    {
        if (is_string($pattern)) {
            return preg_match($pattern, $value);
        }
        if ($pattern instanceof \PHPUnit_Framework_Constraint) {
            try {
                return $pattern->evaluate($value);
            } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
                return false;
            }
        }
        if (is_callable($pattern)) {
            return $pattern($value, $invocation);
        }

        return false;
    }

    public function getObjectKeyValue($className, $object, $keyName)
    {
        try {
            $x = new \ReflectionProperty($className, $keyName);
            $x->setAccessible(true);
            return $x->getValue($object);
        } catch(\Exception $e) {
            if (property_exists($object, $keyName)) {
                return isset($object->$keyName) ? $object->$keyName : null;
            }

            if (method_exists($object, $keyName)) {
                return $object->$keyName();
            }

            $getMethod = 'get' . ucfirst($keyName);
            if (method_exists($object, $getMethod)) {
                return $object->$getMethod();
            }

            throw new MockBuildException("Not existing property $className::$keyName used");
        }
    }
}