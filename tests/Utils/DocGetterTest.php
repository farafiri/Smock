<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 03.12.13
 * Time: 14:21
 */

use Mock\Utils\DocGetter;

class DocGetterTest extends PHPUnit_Framework_TestCase
{
    public function testSimple1()
    {
        $a = new DocGetter();
        $expected = array('value' => 'integer', 'source' => 'Mock\tests\ResourceClasses\X');
        $this->assertEquals($expected, $a->fullGet('Mock\tests\ResourceClasses\X', 'tInt', DocGetter::R_METHOD, 'return'));
    }

    public function testSimple2()
    {
        $a = new DocGetter();
        $expected = array('value' => 'boolean|array', 'source' => 'Mock\tests\ResourceClasses\X');
        $this->assertEquals($expected, $a->fullGet('Mock\tests\ResourceClasses\X', 'tBoolArr', DocGetter::R_METHOD, 'return'));
    }

    public function testGetFromNonExistingMethod()
    {
        $a = new DocGetter();
        $this->assertEquals(null, $a->fullGet('Mock\tests\ResourceClasses\X', 'qNonExistingMethod', DocGetter::R_METHOD, 'return'));
    }

    public function testGetFromMethodWithoutDocComment()
    {
        $a = new DocGetter();
        $this->assertEquals(null, $a->fullGet('Mock\tests\ResourceClasses\X', 'getA', DocGetter::R_METHOD, 'return'));
    }

    public function testGetFromMethodWithoutSpecifiedDoc()
    {
        $a = new DocGetter();
        $this->assertEquals(null, $a->fullGet('Mock\tests\ResourceClasses\X', 'tInt', DocGetter::R_METHOD, 'see'));
    }

    public function testGetTwoLineComment()
    {
        $a = new DocGetter();
        $expected = array('value' => "X::getA\n      A::getA", 'source' => 'Mock\tests\ResourceClasses\X');
        $this->assertEquals($expected, $a->fullGet('Mock\tests\ResourceClasses\X', 'tBoolArr', DocGetter::R_METHOD, 'see'));
    }

    public function testFromParentClass()
    {
        $a = new DocGetter();
        $expected = array('value' => 'www.link.to.example.org', 'source' => 'Mock\tests\ResourceClasses\X');
        $this->assertEquals($expected, $a->fullGet('Mock\tests\ResourceClasses\Y', 'tBoolArr', DocGetter::R_METHOD, 'example'));
    }

    public function testCurrentClassCoverParentClass()
    {
        $a = new DocGetter();
        $expected = array('value' => 'boolean', 'source' => 'Mock\tests\ResourceClasses\Y');
        $this->assertEquals($expected, $a->fullGet('Mock\tests\ResourceClasses\Y', 'tBoolArr', DocGetter::R_METHOD, 'return'));
    }

    public function testFromInterfaces()
    {
        $a = new DocGetter();
        $expected = array('value' => 'string', 'source' => 'Mock\tests\ResourceClasses\StringInterface');
        $this->assertEquals($expected, $a->fullGet('Mock\tests\ResourceClasses\Y', 'getString', DocGetter::R_METHOD, 'return'));
    }

    public function testSimpleGetFromProperty()
    {
        $a = new DocGetter();
        $expected = array('value' => 'integer', 'source' => 'Mock\tests\ResourceClasses\Y');
        $this->assertEquals($expected, $a->fullGet('Mock\tests\ResourceClasses\Y', 'pp', DocGetter::R_PROPERTY, 'var'));
    }
}