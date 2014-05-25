<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 10:26
 */

use Mock\Utils\UseGetter;

class UseGetterTest extends PHPUnit_Framework_TestCase
{
    public function testUses1()
    {
        $getter = new UseGetter();
        $this->assertEquals(array(), $getter->getUses('Mock\tests\ResourceClasses\X'));
    }

    public function testUses2()
    {
        $getter = new UseGetter();
        $expected = array(
            'Mock' => 'Mock\Mock',
            'AClass' => 'Mock\tests\ResourceClasses\A',
            'ResourceFilter' => 'Mock\tests\ResourceClasses\Filter'
        );
        $this->assertEquals($expected, $getter->getUses('Mock\tests\ResourceClasses\Y'));
    }
} 