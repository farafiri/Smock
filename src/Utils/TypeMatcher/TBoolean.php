<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 13:06
 */

namespace Mock\Utils\TypeMatcher;


class TBoolean implements TypeMatcherInterface
{
    public function match($value)
    {
        return is_bool($value);
    }
} 