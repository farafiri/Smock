<?php

namespace Mock\tests\ResourceClasses;

class A
{
    public $constructorCalled = false;
	protected $a = 'undefined';
	private $b = 'undefined';
    public $c = 0;

    public function __construct($a, $b) {
	    $this->a = $a;
		$this->b = $b;
		$this->constructorCalled = true;
	}
	
	public function getA() {
	    return $this->a;
    }
	
	public function setA($a) {
	    $this->a = $a;
	}
	
	public function getB() {
	    return $this->b;
    }
	
	public function setB($b) {
	    $this->b = $b;
	}
	
	public function concat()
    {
	    return $this->concatWithSeparator('');
	}

    private function concatWithSeparator($separator)
    {
        return $this->getA() . $separator . $this->getB();
    }
}