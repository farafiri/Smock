<?php

namespace Mock;
use Mock\Utils\HackAccess;


class DataContainer {
    protected $containers;

    public function __construct(array $containers)
    {
        $this->containers = $containers;
    }

    public function get($key, $default = null)
    {
        $getterMethod = 'get' . ucfirst($key);
        foreach($this->containers as $container) {
            if (is_array($container) || $container instanceof \ArrayAccess) {
                if (array_key_exists($key, $container)) {
                    return $container[$key];
                }
            } elseif (is_object($container)) {
                if (HackAccess::propertyExist($container, $key)) {
                    return $container->$key;
                } elseif (HackAccess::isCallable($container, $getterMethod)) {
                    return $container->$getterMethod();
                }
            }
        }

        return $default;
    }

    public function exists($key)
    {
        $getterMethod = 'get' . ucfirst($key);
        foreach($this->containers as $container) {
            if (is_array($container) || $container instanceof \ArrayAccess) {
                if (array_key_exists($key, $container)) {
                    return true;
                }
            } elseif (is_object($container)) {
                if (HackAccess::propertyExist($container, $key) || HackAccess::isCallable($container, $getterMethod)) {
                    return true;
                }
            }
        }

        return false;
    }
} 