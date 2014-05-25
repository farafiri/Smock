<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 13:02
 */

namespace Mock\Utils\TypeMatcher;


class TString implements TypeMatcherInterface
{
    public function match($value)
    {
        return is_string($value);
    }
} 