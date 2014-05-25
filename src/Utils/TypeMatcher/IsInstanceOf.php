<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 13:33
 */

namespace Mock\Utils\TypeMatcher;


class IsInstanceOf
{
    protected $className;

    public function __construct($className)
    {
        $this->className = $className;
    }

    public function match($value)
    {
        return $value instanceof $this->className;
    }
}