<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 10.12.13
 * Time: 10:40
 */

namespace Mock\Utils;

/**
 * Class HackAccess
 *
 * This class allow to use protected/private properties/methods
 * Do not use it at home.
 *
 * @package Mock\Utils
 */
class HackAccess
{
    protected $host;
    protected $askParentClass;

    public static function isCallable($object, $methodName)
    {
        if ($object instanceof self) {
            return (bool) $object->getReflectionMethod($methodName);
        } else {
            return is_callable(array($object, $methodName));
        }
    }

    public static function propertyExist($object, $property)
    {
        if ($object instanceof self) {
            return $object->getReflectionProperty($property) !== null || property_exists($object->host, $property);
        } else {
            property_exists($object, $property);
        }
    }

    public function __construct($host, $askParentClass = true)
    {
        $this->host = $host;
        $this->askParentClass = $askParentClass;
    }

    protected function getReflectionProperty($property)
    {
        try {
            return new \ReflectionProperty(get_class($this->host), $property);
        } catch (\ReflectionException $e) {
            if (!$this->askParentClass) {
                return null;
            }

            try {
                return new \ReflectionProperty(get_parent_class($this->host), $property);
            } catch (Exception $e) {
                return null;
            }
        }
    }

    public function __get($property)
    {
        $r = $this->getReflectionProperty($property);
        if ($r) {
            $r->setAccessible(true);
            return $r->getValue($this->host);
        } else {
            return isset($this->host->$property) ? $this->host->$property : null;
        }
    }

    public function __set($property, $value)
    {
        $r = $this->getReflectionProperty($property);
        if ($r) {
            $r->setAccessible(true);
            $r->setValue($this->host, $value);
        } else {
            $this->host->$property = $value;
        }
    }

    public function __isset($property)
    {
        $r = $this->getReflectionProperty($property);
        if ($r) {
            $r->setAccessible(true);
            return $r->getValue($this->host) !== null;
        } else {
            return isset($this->host, $property);
        }
    }

    protected function getReflectionMethod($methodName)
    {
        try {
            return new \ReflectionMethod(get_class($this->host), $methodName);
        } catch (\ReflectionException $e) {
            if (!$this->askParentClass) {
                return null;
            }

            try {
                return new \ReflectionMethod(get_parent_class($this->host), $methodName);
            } catch (\ReflectionException $e) {
                return null;
            }
        }
    }

    public function __call($methodName, $arguments)
    {
        $r = $this->getReflectionMethod($methodName);

        if ($r) {
            $r->setAccessible(true);
            $result = $r->invokeArgs($this->host, $arguments);
            return $result === $this->host ? $this : $result;
        } else {
            throw new \BadMethodCallException();
        }
    }
} 