<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 13:00
 */

namespace Mock\Utils\TypeMatcher;


class TInteger implements TypeMatcherInterface
{
    public function match($value)
    {
        return is_int($value);
    }
} 