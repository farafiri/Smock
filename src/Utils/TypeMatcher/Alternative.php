<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 13:13
 */

namespace Mock\Utils\TypeMatcher;


class Alternative implements TypeMatcherInterface
{
    protected $alternatives;

    /**
     * @param TypeMatcherInterface[] $alternatives
     */
    public function __construct($alternatives)
    {
        $this->alternatives = $alternatives;
    }

    public function match($value)
    {
        foreach($this->alternatives as $alternative) {
            if ($alternative->match($value)) {
                return true;
            }
        }

        return false;
    }
} 