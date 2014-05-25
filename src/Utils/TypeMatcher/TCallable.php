<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 13:12
 */

namespace Mock\Utils\TypeMatcher;


class TCallable implements TypeMatcherInterface
{
    public function match($value)
    {
        return is_callable($value);
    }
} 