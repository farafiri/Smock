<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 13:16
 */

namespace Mock\Utils\TypeMatcher;


class ArrayOf implements TypeMatcherInterface
{
    protected $collectionElementTypeMatcher;

    /**
     * @param TypeMatcherInterface $collectionElementTypeMatcher
     */
    public function __construct($collectionElementTypeMatcher)
    {
        $this->collectionElementTypeMatcher = $collectionElementTypeMatcher;
    }

    public function match($value)
    {
        if (!is_array($value) && !($value instanceof \Traversable)) {
            return false;
        }

        foreach($value as $collectionElement) {
            if (!$this->collectionElementTypeMatcher->match($collectionElement)) {
                return false;
            }
        }

        return true;
    }
} 