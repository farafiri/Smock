<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 03.12.13
 * Time: 11:59
 */

namespace Mock\Utils;

class Utils
{
    public static function groupBy($inputArray, $groupingFunction)
    {
        $result = array();
        foreach($inputArray as $element) {
            $result[$groupingFunction($element)][] = $element;
        }

        return $result;
    }
}