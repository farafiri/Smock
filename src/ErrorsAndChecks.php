<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 03.12.13
 * Time: 12:07
 */

namespace Mock;


class ErrorsAndChecks {
    public $checkCode = '';

    public function errorRepeatedMethod($methodName, $methodNode)
    {
        throw new MockBuildException("Repeated method: $methodName");
    }

    public function errorRepeatedProperty($propertyName, $propertyNode)
    {
        throw new MockBuildException("Repeated property: $propertyName");
    }

    public function checkClass($className)
    {
        $this->checkCode .= "\$builder->assertClass('$className');";
    }

    public function checkMethod($className, $methodNode)
    {
        $overrideModeNode = $methodNode->getSubnode(0);
        $methodSelectorNode = $methodNode->getSubnode(1);
        $methodName = (string) $methodSelectorNode;

        if ($methodSelectorNode->getDetailType() === 'name' && $overrideModeNode->getDetailType() === 'override') {
            $this->checkCode .= "\$builder->assertMethod('$className','$methodName');";
        }
    }

    public function checkClassConst($className, $constName, $node)
    {
        $this->checkCode .= "\$builder->assertClassConstant('$className','$constName');";
    }

    public function checkClassVar($className, $varName, $node)
    {
        $this->checkCode .= "\$builder->assertClassVar('$className','$varName');";
    }

    public function checkDataKey($dataKey)
    {
        $this->checkCode .= "\$builder->assertDataKey(\$data,'$dataKey');";
    }

    public function checkMethodReturnsCorrectValueType($className, $methodName, $valueCode, $isConstExpr)
    {
        if (empty($className) || empty($methodName)) {
            return $valueCode;
        }
        $checkCode = "\$builder->assertMethodReturnsCorrectValueType('$className','$methodName',$valueCode)";
        if (!$isConstExpr) {
            return $checkCode;
        } else {
            $this->checkCode .= $checkCode . ';';
            return $valueCode;
        }
    }
} 