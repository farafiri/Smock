<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 03.12.13
 * Time: 20:20
 */

use Mock\PHPUnitMockBuilder;
use Mock\tests\ResourceClasses\A;
use Mock\tests\ResourceClasses\B;
use Mock\tests\ResourceClasses\X;

class PHPUnitMockBuilderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->builder = new PHPUnitMockBuilder(null);
    }

    public function testx()
    {

    }
}