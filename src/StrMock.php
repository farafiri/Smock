<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 03.12.13
 * Time: 11:58
 */

namespace Mock;

class StrMock
{
    public $className;
    public $constructorArgs;
    public $methods;
    public $properties;

    public function __construct($className, $constructorArgs, $methods, $properties)
    {
        $this->className = $className;
        $this->constructorArgs = $constructorArgs;
        $this->methods = $methods;
        $this->properties = $properties;
    }

    public function __toString()
    {
        $methods = array();
        foreach($this->methods as $name => $method) {
            $methods[] = "'" . addslashes($name) . "'=>" . $method;
        }

        $properties = array();
        foreach($this->properties as $name => $property) {
            $properties[] = "'" . addslashes($name) . "'=>" . $property;
        }

        $constructorArgs = $this->constructorArgs === null ? 'null' : ('array(' . implode(',', $this->constructorArgs) . ')');

        return '$builder->buildMock(\'' . addslashes($this->className) . '\',' . $constructorArgs . ',array(' . implode(',', $methods) . '),array(' . implode(',', $properties) . '))';
    }
}