<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 23.12.13
 * Time: 19:22
 */

use Mock\tests\ResourceClasses\A;
use Mock\tests\ResourceClasses\X;

class TypeMatcherBuilderTest extends PHPUnit_Framework_TestCase
{
    protected static $sut;

    static public function setUpBeforeClass()
    {
        self::$sut = new \Mock\Utils\TypeMatcher\TypeMatcherBuilder();
    }

    protected function getSut()
    {
        return self::$sut;
    }

    public function testIntWithInt()
    {
        $this->assertTrue($this->getSut()->get('int')->match(1));
    }

    public function testIntWithString()
    {
        $this->assertFalse($this->getSut()->get('int')->match("1"));
    }

    public function testIntWithArray()
    {
        $this->assertFalse($this->getSut()->get('int')->match(array(2)));
    }

    public function testIntegerWithInt()
    {
        $this->assertTrue($this->getSut()->get('integer')->match(1));
    }

    public function testIntegerWithString()
    {
        $this->assertFalse($this->getSut()->get('integer')->match("1"));
    }

    public function testIntegerWithArray()
    {
        $this->assertFalse($this->getSut()->get('integer')->match(array(2)));
    }

    public function testStringWithInt()
    {
        $this->assertFalse($this->getSut()->get('string')->match(1));
    }

    public function testStringWithString()
    {
        $this->assertTrue($this->getSut()->get('string')->match("1"));
    }

    public function testStringWithArray()
    {
        $this->assertFalse($this->getSut()->get('string')->match(array("abc")));
    }

    public function testBoolWithInt()
    {
        $this->assertFalse($this->getSut()->get('bool')->match(1));
    }

    public function testBoolWithString()
    {
        $this->assertFalse($this->getSut()->get('bool')->match("1"));
    }

    public function testBoolWithBool()
    {
        $this->assertTrue($this->getSut()->get('bool')->match(true));
    }

    public function testBooleanWithInt()
    {
        $this->assertFalse($this->getSut()->get('boolean')->match(1));
    }

    public function testBooleanWithString()
    {
        $this->assertFalse($this->getSut()->get('boolean')->match("1"));
    }

    public function testBooleanWithBool()
    {
        $this->assertTrue($this->getSut()->get('boolean')->match(true));
    }

    public function testMixedWithInt()
    {
        $this->assertTrue($this->getSut()->get('mixed')->match(1));
    }

    public function testMixedWithString()
    {
        $this->assertTrue($this->getSut()->get('mixed')->match("1"));
    }

    public function testMixedWithBool()
    {
        $this->assertTrue($this->getSut()->get('mixed')->match(true));
    }

    public function testArrayWithInt()
    {
        $this->assertFalse($this->getSut()->get('array')->match(0));
    }

    public function testArrayWithString()
    {
        $this->assertFalse($this->getSut()->get('array')->match("1"));
    }

    public function testArrayWithArray()
    {
        $this->assertTrue($this->getSut()->get('array')->match(array('a', 1, null)));
    }

    public function testFloatWithFloat()
    {
        $this->assertTrue($this->getSut()->get('float')->match(0.5));
    }

    public function testFloatWithString()
    {
        $this->assertFalse($this->getSut()->get('float')->match("1"));
    }

    public function testObjectWithInt()
    {
        $this->assertFalse($this->getSut()->get('object')->match(0));
    }

    public function testObjectWithString()
    {
        $this->assertFalse($this->getSut()->get('object')->match("1"));
    }

    public function testObjectWithArray()
    {
        $this->assertFalse($this->getSut()->get('object')->match(array(1)));
    }

    public function testObjectWithObject()
    {
        $this->assertTrue($this->getSut()->get('object')->match(new \stdClass()));
    }

    public function testCallableWithArray()
    {
        $this->assertFalse($this->getSut()->get('callable')->match(array()));
    }

    public function testCallableWithClosure()
    {
        $this->assertTrue($this->getSut()->get('callable')->match(function() {return 0;}));
    }

    public function testClassAWithInt()
    {
        $this->assertFalse($this->getSut()->get('Mock\tests\ResourceClasses\A')->match(1));
    }

    public function testClassAWithA()
    {
        $this->assertTrue($this->getSut()->get('Mock\tests\ResourceClasses\A')->match(new A(1, 1)));
    }

    public function testClassAWithX()
    {
        $this->assertFalse($this->getSut()->get('Mock\tests\ResourceClasses\A')->match(new X()));
    }

    public function testBackslashClassAWithA()
    {
        $this->assertTrue($this->getSut()->get('\Mock\tests\ResourceClasses\A')->match(new A(1, 1)));
    }

    public function testBackslashClassAWithX()
    {
        $this->assertFalse($this->getSut()->get('\Mock\tests\ResourceClasses\A')->match(new X()));
    }

    public function testAlternativeIntOrBoolWithString()
    {
        $this->assertFalse($this->getSut()->get('int|bool')->match("q")) ;
    }

    public function testAlternativeIntOrBoolWithArray()
    {
        $this->assertFalse($this->getSut()->get('int|bool')->match(array())) ;
    }

    public function testAlternativeIntOrBoolWithInt()
    {
        $this->assertTrue($this->getSut()->get('int|bool')->match(5)) ;
    }

    public function testAlternativeIntOrBoolWithBool()
    {
        $this->assertTrue($this->getSut()->get('int|bool')->match(true)) ;
    }

    public function testCollectionOfIntWithInt()
    {
        $this->assertFalse($this->getSut()->get('int[]')->match(3)) ;
    }

    public function testCollectionOfIntWithArrayContainingString()
    {
        $this->assertFalse($this->getSut()->get('int[]')->match(array(1, 2, "c"))) ;
    }

    public function testCollectionOfIntWithArrayOfInt()
    {
        $this->assertTrue($this->getSut()->get('int[]')->match(array(1, 2, 3))) ;
    }

    public function testCollectionOfIntWithEmptyArray()
    {
        $this->assertTrue($this->getSut()->get('int[]')->match(array())) ;
    }

    public function testBoolOrCollectionOfIntWithArrayOfBool()
    {
        $this->assertFalse($this->getSut()->get('bool|int[]')->match(array(true)));
    }

    public function testBoolOrCollectionOfIntWithBool()
    {
        $this->assertTrue($this->getSut()->get('bool|int[]')->match(true));
    }

    public function testBoolOrCollectionOfIntWithArrayOfInt()
    {
        $this->assertTrue($this->getSut()->get('bool|int[]')->match(array(1, 2, 3)));
    }

    public function testBoolOrCollectionOfIntWithArrayOfIntAndBool()
    {
        $this->assertFalse($this->getSut()->get('bool|int[]')->match(array(true, 5)));
    }

    public function testCollectionOfBoolOrCollectionOfIntWithArrayOfBool()
    {
        $this->assertTrue($this->getSut()->get('bool[]|int[]')->match(array(true)));
    }

    public function testCollectionOfBoolOrCollectionOfIntWithBool()
    {
        $this->assertFalse($this->getSut()->get('bool[]|int[]')->match(true));
    }

    public function testCollectionOfBoolOrCollectionOfIntWithArrayOfInt()
    {
        $this->assertTrue($this->getSut()->get('bool[]|int[]')->match(array(1, 2, 3)));
    }

    public function testCollectionOfBoolOrCollectionOfIntWithArrayOfIntAndBool()
    {
        $this->assertFalse($this->getSut()->get('bool[]|int[]')->match(array(true, 5)));
    }

    public function testCollectionOfBoolOrIntWithArrayOfBool()
    {
        $this->assertTrue($this->getSut()->get('(bool|int)[]')->match(array(true)));
    }

    public function testCollectionOfBoolOrIntWithBool()
    {
        $this->assertFalse($this->getSut()->get('(bool|int)[]')->match(true));
    }

    public function testCollectionOfBoolOrIntWithArrayOfInt()
    {
        $this->assertTrue($this->getSut()->get('(bool|int)[]')->match(array(1, 2, 3)));
    }

    public function testCollectionOfBoolOrIntWithArrayOfIntAndBool()
    {
        $this->assertTrue($this->getSut()->get('(bool|int)[]')->match(array(true, 5)));
    }
} 