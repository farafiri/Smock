<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 12:59
 */

namespace Mock\Utils\TypeMatcher;


interface TypeMatcherInterface
{
    /**
     * @param mixed $value
     *
     * @return boolean
     */
    function match($value);
} 