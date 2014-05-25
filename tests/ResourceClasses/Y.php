<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 16.12.13
 * Time: 16:27
 */

namespace Mock\tests\ResourceClasses;

use Mock\Mock;
use Mock\tests\ResourceClasses\A as AClass;
use Mock\tests\ResourceClasses\Filter as ResourceFilter;


class Y extends X
{
    /**
     * @return boolean
     */
    public function tBoolArr() {return false;}
} 