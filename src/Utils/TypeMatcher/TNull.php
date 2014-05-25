<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 13:09
 */

namespace Mock\Utils\TypeMatcher;


class TNull implements TypeMatcherInterface
{
    public function match($value)
    {
        return $value === null;
    }
} 