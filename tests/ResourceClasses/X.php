<?php

namespace Mock\tests\ResourceClasses;

class X implements StringInterface
{
    const CA = 12;
	const CB = "78";
	public static $va = "va";
	public static $vb = 42;
	protected static $vp = "protected variable";

    /**
     * @var integer
     */
    public $pp = 0;
	
    protected function protectedMethod() {return "undefined";}

    public function getA() {return "undefined";}
	public function getB() {return "undefined";}
	public function getC() {return "undefined";}
	public function getX() {return "undefined";}
	public function getZ() {return "undefined";}
    public function getT() {return "undefined";}
	public function sign() {return "undefined";}
	public function grade() {return "undefined";}
	public function containNumber() {return "undefined";}
	public function first() {return "undefined";}
	public function second() {return "undefined";}
	public function logicOr() {return "undefined";}
	public function a() {return "undefined";}
	public function b() {return "undefined";}
	public function c() {return "undefined";}
	public function setA() {return "undefined";}
	public function check() {return "undefined";}
	public function getName() {return "undefined";}
	public function getParent() {return "undefined";}
	public function getG() {return "undefined";}
	public function getO() {return "undefined";}
	public function getChild() {return "undefined";}
	public function getTop() {return "undefined";}
	public function getChild1() {return "undefined";}
	public function getChild2() {return "undefined";}
	public function getArray() {return "undefined";}
	
	public final function fin() {return "undefined";}

    /**
     * @return integer
     */
    public function tInt() {return 34;}

    /**
     * @return boolean|array
     * @see X::getA
     *      A::getA
     * @example www.link.to.example.org
     */
    public function tBoolArr() {return false;}

    /**
     * @return A
     */
    public function tA() {
        return new A(0,0);
    }

    public function pA(A $a) {
        return "5";
    }

    public function getString()
    {
        return "some string";
    }
}