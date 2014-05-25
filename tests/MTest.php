<?php

use Mock\Mock;
use Mock\SFunction;
use Mock\tests\ResourceClasses\A;
use Mock\tests\ResourceClasses\X;

class XX
{
    public function __construct()
    {
        $this->cl = new ReflectionMethod('YY', 'a');
    }

    public function b()
    {
        return 'b';
    }

    public function a()
    {
        $this->cl->invokeArgs($this, func_get_args());
    }
}

class YY
{
    public function a() {
        return $this->b() . 'a';
    }
}

class MTest extends PHPUnit_Framework_TestCase
{
    protected $tt = 89;

    public function testOnce()
    {
        return 0;
        $y = new XX();
        var_dump($y->a());
    }

    public function testMethodReturningMock()
    {
        return 0;
        $mock = Mock::getInstance()->get('Mock\tests\ResourceClasses\A
		                                     getX -> Mock\tests\ResourceClasses\A
											    getB -> "BofX"
										        getZ -> Mock\tests\ResourceClasses\A ->
												    getA -> "AofZ"
													getB -> "BofZ".
											    getA -> "AofX"', $this);

        $this->assertEquals('undefined', $mock->getA());
        $this->assertEquals('undefined', $mock->getB());

        $x = $mock->getX();
        $this->assertTrue($x instanceof A);
        $this->assertEquals('AofX', $x->getA());
        $this->assertEquals('BofX', $x->getB());

        $z = $x->getZ();
        $this->assertTrue($z instanceof A);
        $this->assertEquals('AofZ', $z->getA());
        $this->assertEquals('BofZ', $z->getB());
    }

    public function qtestX()
    {
        $builder = $this->getMock('Mock\tests\ResourceClasses\A', array('geto'), array(1, 2));

        $cb = create_function('', '$args = func_get_args(); return $args[0] * 3 + 1;');

        $builder->expects($this->any())
            ->method('geto')
            ->will($this->returnCallback($cb));

        var_dump($cb);
    }

    public function qtestY()
    {
        $f = new SFunction('return $a . $b;', array('a'), array('b' => 'tx'));
        var_dump($f);
        $f(6);
        $a = var_export($f, true);
        $q = eval('return ' . $a . ';');
        var_dump($q);
    }
}