<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 13:04
 */

namespace Mock\Utils\TypeMatcher;


class TMixed implements TypeMatcherInterface
{
    public function match($value)
    {
        return true;
    }
} 