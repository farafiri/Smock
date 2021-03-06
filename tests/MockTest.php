<?php

use Mock\Mock;
use Mock\tests\ResourceClasses\A;
use Mock\tests\ResourceClasses\X;

class MockTest extends PHPUnit_Framework_TestCase
{
    protected function getSMock($mock, $extraData = array())
    {
        return Mock::getInstance()->get($mock, $this, $extraData);
    }

    public function testGenerateClassWithoutConstructor()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A');

        $this->assertTrue($mock instanceof A);
        $this->assertFalse($mock->constructorCalled);
        $this->assertEquals('undefined', $mock->getA());
        $this->assertEquals('undefined', $mock->getB());

        $mock->setA(234);
        $mock->setB("bbb");
        $this->assertEquals(234, $mock->getA());
        $this->assertEquals("bbb", $mock->getB());
        $this->assertEquals("234bbb", $mock->concat());
    }

    public function testGenerateClassWithConstructor()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A(4, "b")');

        $this->assertTrue($mock instanceof A);
        $this->assertTrue($mock->constructorCalled);
        $this->assertEquals(4, $mock->getA());
        $this->assertEquals('b', $mock->getB());

        $mock->setA(234);
        $mock->setB("bbb");
        $this->assertEquals(234, $mock->getA());
        $this->assertEquals("bbb", $mock->getB());
        $this->assertEquals("234bbb", $mock->concat());
    }

    public function testSimpleMethod()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X getA -> 15');
        $this->assertEquals(15, $mock->getA());

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X getA -> "abc\\\\vv"');
        $this->assertEquals("abc\\vv", $mock->getA());

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X getA -> true');
        $this->assertEquals(true, $mock->getA());

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X getA -> false');
        $this->assertEquals(false, $mock->getA());

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X getA -> null');
        $this->assertEquals(null, $mock->getA());

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X getA -> 0');
        $this->assertEquals(0, $mock->getA());
    }

    public function testWrongStringProducesException()
    {
        $this->setExpectedException('Mock\MockBuildException');

        $this->getSMock('A B');
    }

    public function testWrongStringProducesExceptionWithProperPositionData()
    {
        try {
            $this->getSMock('Mock\tests\ResourceClasses\X
                                 getA -> "a"
                                 getC -> "c" "b"
                                 getZ -> 345');
        } catch (\Mock\MockBuildException $e) {
            $this->assertContains("\nline: 3, character: 46\n", $e->getMessage());

            return 0;
        }

        $this->fail("Incorrect mock string should produce exception");
    }

    public function testSimpleMethods()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> 671
                                     getB -> "ooo"
                                     getC -> true ');

        $this->assertEquals(671, $mock->getA());
        $this->assertEquals("ooo", $mock->getB());
        $this->assertEquals(true, $mock->getC());
    }

    public function testMethodReturningMock()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getX -> Mock\tests\ResourceClasses\X.
                                     getA -> "ooo" ');

        $x = $mock->getX();
        $this->assertTrue($x instanceof X);
        $this->assertEquals("ooo", $mock->getA());
        $this->assertEquals("undefined", $x->getA());
    }

    public function testMethodReturningNestedMock()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getX -> Mock\tests\ResourceClasses\X
                                         getA -> "ooo" ');

        $x = $mock->getX();
        $this->assertTrue($x instanceof X);
        $this->assertEquals('undefined', $mock->getA());
        $this->assertEquals("ooo", $x->getA());
    }

    public function testMethodReturningMockComplexExample()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getX -> Mock\tests\ResourceClasses\X
                                         getB -> "BofX"
                                         getZ -> Mock\tests\ResourceClasses\X
                                             getA -> "AofZ"
                                             getB -> "BofZ".
                                         getA -> "AofX"');

        $this->assertEquals('undefined', $mock->getA());
        $this->assertEquals('undefined', $mock->getB());

        $x = $mock->getX();
        $this->assertTrue($x instanceof X);
        $this->assertEquals('AofX', $x->getA());
        $this->assertEquals('BofX', $x->getB());

        $z = $x->getZ();
        $this->assertTrue($z instanceof X);
        $this->assertEquals('AofZ', $z->getA());
        $this->assertEquals('BofZ', $z->getB());
    }

    public function testRepeatedMethodShouldThrowError()
    {
        $this->setExpectedException('Mock\MockBuildException');

        $this->getSMock('Mock\tests\ResourceClasses\X
                             getX -> Mock\tests\ResourceClasses\X.
                             getX -> "ooo" ');
    }

    public function testMockingNonExistingClassShouldRaiseError()
    {
        $this->setExpectedException('Mock\MockBuildException');

        $this->getSMock('Mock\tests\ResourceClasses\NonExistingClass');
    }

    public function testMockingNonExistingMethodShouldRaiseError()
    {
        $this->setExpectedException('Mock\MockBuildException');

        $this->getSMock('Mock\tests\ResourceClasses\X
                             nonExistingMethod -> 1');
    }

    public function testMockingNonExistingMethodWithOperator()
    {
        $this->getSMock('Mock\tests\ResourceClasses\X
                             +protectedMethod -> 1');
    }

    public function testMockingNonExistingMethodWithAdd()
    {
        $this->getSMock('Mock\tests\ResourceClasses\X
                             (add)protectedMethod -> 1');
    }

    public function testMethodWithExpectedParams()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getX (4)   -> 40
                                          (5)   -> 50
                                          ("5") -> "five"
                                                -> "undefined"');

        $this->assertEquals(40, $mock->getX(4));
        $this->assertEquals(50, $mock->getX(5));
        $this->assertEquals("five", $mock->getX("5"));
        $this->assertEquals("undefined", $mock->getX(6));
        $this->assertEquals("undefined", $mock->getX(0));
        $this->assertEquals("undefined", $mock->getX("4"));
        $this->assertEquals("undefined", $mock->getX(4, 0));
        $this->assertEquals("undefined", $mock->getX(5, 0));
        $this->assertEquals("undefined", $mock->getX("5", 0));
    }

    public function testMethodExpectingAnything()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getX (4, 5) -> "45"
                                          (4, *) -> "4?"
                                          (*, 5) -> "?5"
                                          (4)    -> "4"');

        $this->assertEquals("45", $mock->getX(4, 5));
        $this->assertEquals("?5", $mock->getX(0, 5));
        $this->assertEquals("4?", $mock->getX(4, 4));
        $this->assertEquals("4?", $mock->getX(4, null));
        $this->assertEquals("4", $mock->getX(4));
    }

    public function testMethodExpectingAnythingCheckNumberOfParameters()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getX (*) -> "one param"
                                          ()  -> "no params"
                                              -> "any count of params"');

        $this->assertEquals("one param", $mock->getX(345));
        $this->assertEquals("one param", $mock->getX(false));
        $this->assertEquals("one param", $mock->getX(null));
        $this->assertEquals("no params", $mock->getX());
        $this->assertEquals("any count of params", $mock->getX(2, 4));
        $this->assertEquals("any count of params", $mock->getX(2, 4, 5, 8, 3, 4, 8,2));
        $this->assertEquals("any count of params", $mock->getX(2, null));
        $this->assertEquals("any count of params", $mock->getX(null, null));
    }


    public function testMethodThrow()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getX -> throw BadMethodCallException');

        $this->setExpectedException('BadMethodCallException');
        $mock->getX();
    }

    public function testMethodFail()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getX -> fail "message"');

        $this->setExpectedException('PHPUnit_Framework_AssertionFailedError');
        $mock->getX();
    }

    public function testMethodDefaultBehaviourIsToFail()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getX (4) -> 40
                                          (5) -> 50');

        $this->assertEquals(40, $mock->getX(4));
        $this->assertEquals(50, $mock->getX(5));
        $this->setExpectedException('PHPUnit_Framework_AssertionFailedError');
        $mock->getX(6);
    }

    public function testMethodParametersWithComparison()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     sign (>0) -> 1
                                          (<0) -> -1
                                          (0)  -> 0
                                          (*)  -> throw InvalidArgumentException');

        $this->assertEquals(1, $mock->sign(34));
        $this->assertEquals(1, $mock->sign(1));
        $this->assertEquals(1, $mock->sign(0.004));
        $this->assertEquals(-1, $mock->sign(-1));
        $this->assertEquals(-1, $mock->sign(-200));
        $this->assertEquals(0, $mock->sign(0));

        $this->setExpectedException('InvalidArgumentException');
        $this->assertEquals(0, $mock->sign("0"));
    }

    public function testMethodParametersWithComparison2()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     grade (>=90) -> "excellent"
                                           (>=70) -> "good"
                                           (>=40) -> "passed"
                                                  -> "failed"');

        $this->assertEquals("failed", $mock->grade(34));
        $this->assertEquals("failed", $mock->grade(0));
        $this->assertEquals("failed", $mock->grade(39));
        $this->assertEquals("passed", $mock->grade(40));
        $this->assertEquals("passed", $mock->grade(55));
        $this->assertEquals("passed", $mock->grade(69));
        $this->assertEquals("good", $mock->grade(70));
        $this->assertEquals("good", $mock->grade(89));
        $this->assertEquals("excellent", $mock->grade(99));
    }

    public function testMethodParametersWithRegex()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     containNumber (/\d+/) -> true
                                                           -> false');

        $this->assertFalse($mock->containNumber('lorem ipsum dolor sit emet'));
        $this->assertFalse($mock->containNumber('another text'));
        $this->assertFalse($mock->containNumber(''));
        $this->assertTrue($mock->containNumber('12'));
        $this->assertTrue($mock->containNumber('lorem ipsum dolor sit emet123'));
        $this->assertTrue($mock->containNumber('lorem ipsum dolor 51t emet'));
    }

    public function testMethodParametersWithEscapeCharacterInRegex()
    {
        //TODO: method name
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     containNumber (/\\\\/) -> "b"
                                                   (/\\//)  -> "/"
                                                            -> ""');

        $this->assertEquals("b", $mock->containNumber('ads \\ sdf'));
        $this->assertEquals("/", $mock->containNumber('asd/ f'));
        $this->assertEquals("", $mock->containNumber('dsf g sf'));
    }

    public function testMethodReturnArgs()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     first  (*, *) -> $args[0]
                                     second (*, *) -> $args[1]');

        $this->assertEquals(1, $mock->first(1, 2));
        $this->assertEquals(2, $mock->second(1, 2));
        $this->assertEquals("", $mock->first("", "arg"));
        $this->assertEquals("arg", $mock->second("", "arg"));
    }

    public function testMethodReturnArgsComplexExample()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     logicOr  (*) -> $args[0]
                                              (==null, *) -> $args[1]
                                              (*     , *) -> $args[0]
                                              (==null, ==null, *) -> $args[2]
                                              (==null, *     , *) -> $args[1]
                                              (*     , *     , *) -> $args[0]');

        $this->assertEquals(8, $mock->logicOr(8));
        $this->assertEquals(0, $mock->logicOr(0));
        $this->assertEquals(null, $mock->logicOr(null));
        $this->assertEquals("ping", $mock->logicOr("ping", "pong"));
        $this->assertEquals("pong", $mock->logicOr("", "pong"));
        $this->assertEquals(null, $mock->logicOr(0, false, null));
        $this->assertEquals(false, $mock->logicOr(0, false, false));
        $this->assertEquals("uuu", $mock->logicOr(0, "uuu", false));
        $this->assertEquals(true, $mock->logicOr(true, "uuu", false));
    }

    public function testMethodReturnsSelf()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     a -> self');

        $this->assertTrue($mock === $mock->a());
    }

    public function testMethodReturnsSelfNestedCase()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     a -> self
                                     b -> Mock\tests\ResourceClasses\X
                                         a -> self.
                                     c -> self');

        $this->assertTrue($mock === $mock->a());
        $this->assertFalse($mock === $mock->b());
        $this->assertFalse($mock === $mock->b()->a());
        $mockB = $mock->b();
        $this->assertTrue($mockB === $mockB->a());
        $this->assertTrue($mock === $mock->c());
        $mockB = $mock->b();
        $this->assertTrue($mockB === $mockB->a());
    }

    public function testGettingFromData()
    {
        $data = new \ArrayObject(array(
            'a' => 'key a',
            'b' => 'B',
            'c' => new \stdClass()
        ));

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     a -> $a
                                     b -> $b
                                     c -> Mock\tests\ResourceClasses\X
                                         a -> $a
                                         c -> $c', $data);

        $this->assertEquals($data['a'], $mock->a());
        $this->assertEquals($data['b'], $mock->b());
        $this->assertEquals($data['a'], $mock->c()->a());
        $this->assertEquals($data['c'], $mock->c()->c());

        $data['a'] = 'A';
        $data['c'] = false;

        $this->assertEquals($data['a'], $mock->a());
        $this->assertEquals($data['b'], $mock->b());
        $this->assertEquals($data['a'], $mock->c()->a());
        $this->assertEquals($data['c'], $mock->c()->c());
    }

    public function testMethodReturnsOrigin()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A(6, 8)
                                     getA -> origin');

        $this->assertEquals(6, $mock->getA());
        $mock->setA(0);
        $this->assertEquals(0, $mock->getA());
    }

    public function testMethodReturnsOriginCaseWithArgumentTest()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A(6, 8)
                                     setA (0) -> null
                                              -> origin');

        $this->assertEquals(6, $mock->getA());
        $mock->setA(0);
        $this->assertEquals(6, $mock->getA());
        $mock->setA(12);
        $this->assertEquals(12, $mock->getA());
    }

    public function testMethodReturnsOriginCaseWithProtectedMethod()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\Filter
                                     check (-8) -> true
                                           (*)  -> origin');

        $this->assertEquals(array(1, 8, -8, 2, -8), $mock->filter(array(1, 0, 8, -20, -8, 2, -8, -7)));
    }

    public function testBaseWith()
    {
        $mock = $this->getSMock('get Mock\tests\ResourceClasses\X
                                     getA -> $a
                                 as $a');

        $this->assertSame($mock, $mock->getA());
    }

    public function testBaseWith2()
    {
        $mock = $this->getSMock('with Mock\tests\ResourceClasses\X
                                     getA -> $a
                                     getB -> $b
                                     getName -> "A"
                                 as $a
                                 get Mock\tests\ResourceClasses\X
                                     getA -> $a
                                     getB -> $b
                                     getName -> "B"
                                 as $b');

        $b = $mock;
        $a = $b->getA();
        $this->assertEquals("A", $a->getName());
        $this->assertEquals("B", $b->getName());
        $this->assertSame($b, $a->getB());
        $this->assertSame($b, $b->getB());
        $this->assertSame($a, $a->getA());
        $this->assertSame($a, $b->getA());
    }

    public function testWithWithLotOfObjects()
    {
        $mock = $this->getSMock('with Mock\tests\ResourceClasses\X
                                     getParent -> $parent
                                     getName -> "Alpha"
                                 as $a
                                 with Mock\tests\ResourceClasses\X
                                     getParent -> $parent
                                     getName -> "Beta"
                                 as $b
                                 with Mock\tests\ResourceClasses\X
                                     getParent -> $parent
                                     getName -> "Gamma"
                                 as $g
                                 with Mock\tests\ResourceClasses\X
                                     getParent -> $parent
                                     getName -> "Xi"
                                 as $x
                                 with Mock\tests\ResourceClasses\X
                                     getParent -> $parent
                                     getName -> "Omega"
                                 as $o
                                 get Mock\tests\ResourceClasses\X
                                     getA -> $a
                                     getB -> $b
                                     getG -> $g
                                     getX -> $x
                                     getO -> $o
                                 as $parent');


        $this->assertEquals("Alpha", $mock->getA()->getName());
        $this->assertSame($mock, $mock->getA()->getParent());
        $this->assertEquals("Beta", $mock->getB()->getName());
        $this->assertSame($mock, $mock->getB()->getParent());
        $this->assertEquals("Gamma", $mock->getG()->getName());
        $this->assertSame($mock, $mock->getG()->getParent());
        $this->assertEquals("Xi", $mock->getX()->getName());
        $this->assertSame($mock, $mock->getX()->getParent());
        $this->assertEquals("Omega", $mock->getO()->getName());
        $this->assertSame($mock, $mock->getO()->getParent());
    }

    public function testSimpleNestedWith()
    {
        $mock = $this->getSMock('get Mock\tests\ResourceClasses\X
                                     getChild -> get Mock\tests\ResourceClasses\X
                                         getParent -> $top
                                         getTop -> $top
                                         getChild -> get Mock\tests\ResourceClasses\X
                                             getParent -> $a1
                                             getTop -> $top
                                             getChild -> Mock\tests\ResourceClasses\X
                                                 getParent -> $a2
                                                 getTop -> $top
                                         as $a2
                                     as $a1
                                 as $top');

        $a0 = $mock;
        $a1 = $a0->getChild();
        $this->assertTrue($a1 instanceof X);
        $this->assertNotSame($a0, $a1);
        $this->assertSame($a0, $a1->getParent());
        $this->assertSame($a0, $a1->getTop());

        $a2 = $a1->getChild();
        $this->assertTrue($a2 instanceof X);
        $this->assertNotSame($a0, $a2);
        $this->assertSame($a1, $a2->getParent());
        $this->assertSame($a0, $a2->getTop());

        $a3 = $a2->getChild();
        $this->assertTrue($a3 instanceof X);
        $this->assertNotSame($a0, $a3);
        $this->assertSame($a2, $a3->getParent());
        $this->assertSame($a0, $a3->getTop());
    }

    public function testWithOverride()
    {
        $mock = $this->getSMock('get Mock\tests\ResourceClasses\X
                                     getChild -> get Mock\tests\ResourceClasses\X
                                         getA -> $a
                                     as $a
                                     getA -> $a
                                 as $a');

        $child = $mock->getChild();
        $this->assertNotSame($mock, $child);
        $this->assertSame($mock, $mock->getA());
        $this->assertSame($child, $child->getA());
        //intentional repetition:
        $this->assertSame($mock, $mock->getA());
        $this->assertSame($child, $child->getA());
    }

    public function testWithOverridesData()
    {
        $data = array(
            'a' => 'data A',
            'b' => 'data B',
            'c' => 'data C'
        );
        $mock = $this->getSMock('with Mock\tests\ResourceClasses\X as $a
                                 get Mock\tests\ResourceClasses\X
                                     getA -> $a
                                     getChild1 -> get Mock\tests\ResourceClasses\X
                                         getA -> $a
                                         getB -> $b
                                         getC -> $c
                                     as $_
                                     getChild2 -> with Mock\tests\ResourceClasses\X as $b get Mock\tests\ResourceClasses\X
                                         getA -> $a
                                         getB -> $b
                                         getC -> $c
                                     as $_
                                 as $_', $data);

        $this->assertTrue($mock->getA() instanceof X);
        $child1 = $mock->getChild1();
        $child2 = $mock->getChild2();
        $this->assertTrue($child1->getA() instanceof X);
        $this->assertEquals('data B', $child1->getB());
        $this->assertEquals('data C', $child1->getC());
        $this->assertTrue($child2->getA() instanceof X);
        $this->assertTrue($child2->getB() instanceof X);
        $this->assertEquals('data C', $child1->getC());
    }

    public function testAsAfterGetIsNotObligatory()
    {
        $mock = $this->getSMock('with Mock\tests\ResourceClasses\X as $a
                                 get Mock\tests\ResourceClasses\X
                                     getA -> $a');

        $this->assertTrue($mock->getA() instanceof X);
    }

    public function testAllDataShouldBeFilled()
    {
        $data = array("b" => 5, "c" => null);

        $mock = $this->getSMock('with Mock\tests\ResourceClasses\X as $a
                                 with Mock\tests\ResourceClasses\X as $b
                                 get Mock\tests\ResourceClasses\X
                                     getA -> $a
                                     getB -> $b
                                     getC -> $c', $data);

        $this->assertTrue($mock instanceof X);

        unset($data['c']);
        $this->setExpectedException('Mock\MockBuildException');

        $mock = $this->getSMock('with Mock\tests\ResourceClasses\X as $a
                                 with Mock\tests\ResourceClasses\X as $b
                                 get Mock\tests\ResourceClasses\X
                                     getA -> $a
                                     getB -> $b
                                     getC -> $c', $data);
    }

    public function testMethodWithExpectedParamsFromData()
    {
        $data = new \ArrayObject(array("a" => 5, "b" => "b"));

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     a ($a) -> true
                                       ($b) -> true
                                       (*)  -> false', $data);

        $this->assertTrue($mock->a(5));
        $this->assertTrue($mock->a("b"));
        $this->assertFalse($mock->a(6));
        $this->assertFalse($mock->a("a"));
        $this->assertFalse($mock->a("5"));

        $data['a'] = 6;
        $data['b'] = 'a';

        $this->assertFalse($mock->a(5));
        $this->assertFalse($mock->a("b"));
        $this->assertTrue($mock->a(6));
        $this->assertTrue($mock->a("a"));
        $this->assertFalse($mock->a("5"));
    }

    public function testMethodWithAliasInExpectedParams()
    {
        if (PHPUnit_Runner_Version::id() < '3.7') {
            return 0;
            $this->markTestSkipped("Cause of phpUnit clonig object passed as method arguments in versions < 3.7 these tests will fail");
        }

        $mock = $this->getSMock('with Mock\tests\ResourceClasses\X as $a
                                 with Mock\tests\ResourceClasses\X as $b
                                 get Mock\tests\ResourceClasses\X
                                     getA -> $a
                                     getB -> $b
                                     a ($a) -> "a"
                                       ($b) -> "b"
                                       (*)  -> false');

        $this->assertEquals('a', $mock->a($mock->getA()));
        $this->assertEquals('b', $mock->a($mock->getB()));
    }

    public function testClassConstant()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> Mock\tests\ResourceClasses\X::CA
                                     getB -> Mock\tests\ResourceClasses\X::CB
                                     a (Mock\tests\ResourceClasses\X::CA) -> "CA"
                                       (Mock\tests\ResourceClasses\X::CB) -> "CB"
                                       (*)  -> false');

        $this->assertEquals(X::CA, $mock->getA());
        $this->assertEquals(X::CB, $mock->getB());
        $this->assertEquals("CA", $mock->a(X::CA));
        $this->assertEquals("CB", $mock->a(X::CB));
    }

    public function testNonExistingClassConstantShouldProduceError()
    {
        $this->setExpectedException('Mock\MockBuildException');

        $this->getSMock('Mock\tests\ResourceClasses\X
                             getA -> Mock\tests\ResourceClasses\X::CC');
    }

    public function testClassVariable()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> Mock\tests\ResourceClasses\X::$va
                                     getB -> Mock\tests\ResourceClasses\X::$vb
                                     a (Mock\tests\ResourceClasses\X::$va) -> "va"
                                       (Mock\tests\ResourceClasses\X::$vb) -> "vb"
                                       (*)  -> false');

        $this->assertEquals(X::$va, $mock->getA());
        $this->assertEquals(X::$vb, $mock->getB());
        $this->assertEquals("va", $mock->a(X::$va));
        $this->assertEquals("vb", $mock->a(X::$vb));
    }

    public function testNonExistingClassVariableShouldProduceError()
    {
        $this->setExpectedException('Mock\MockBuildException');

        $this->getSMock('Mock\tests\ResourceClasses\X
                             getA -> Mock\tests\ResourceClasses\X::$vv');
    }

    public function testNonPublicClassVariableShouldProduceError()
    {
        $this->setExpectedException('Mock\MockBuildException');

        $this->getSMock('Mock\tests\ResourceClasses\X
                             getA -> Mock\tests\ResourceClasses\X::$vp');
    }

    public function testWithSupportsSimpleTypes()
    {
        $mock = $this->getSMock('with 10 as $a
                                 with "dog" as $b
                                 get Mock\tests\ResourceClasses\X
                                     getA -> $a
                                     getB -> $b');

        $this->assertEquals(10, $mock->getA());
        $this->assertEquals("dog", $mock->getB());
    }

    public function testSimpleArray()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getArray -> [1, 3, 5, 7]');

        $this->assertEquals(array(1, 3, 5, 7), $mock->getArray());
    }

    public function testSimpleArrayWithVariousTypes()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getArray -> [0, true, "str", null]');

        $this->assertEquals(array(0, true, "str", null), $mock->getArray());
    }

    public function testSimpleArrayWithReferences()
    {
        $mock = $this->getSMock('with Mock\tests\ResourceClasses\X as $a
                                 with Mock\tests\ResourceClasses\X as $b
                                 get Mock\tests\ResourceClasses\X
                                     getA -> $a
                                     getB -> $b
                                     getArray -> [$a, $b]');

        $this->assertEquals(array($mock->getA(), $mock->getB()), $mock->getArray());
    }

    public function testArrayWithStringKeys()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getArray -> ["one" : 1, "two" : 2, "three" : 3]');

        $this->assertEquals(array("one" => 1, "two" => 2, "three" => 3), $mock->getArray());
    }

    public function testArrayWithVariousKeys()
    {
        $data = array('key' => 'x');
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getArray -> [$key : "data", Mock\tests\ResourceClasses\X::$va : "classVar", 5 : "int"]', $data);

        $this->assertEquals(array("x" => "data", "va" => "classVar", 5 => "int"), $mock->getArray());
    }

    public function testArrayWithNotEscapedStringKeys()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getArray -> [one : 1, two : 2, three : 3]');

        $this->assertEquals(array("one" => 1, "two" => 2, "three" => 3), $mock->getArray());
    }

    public function testMethodWithExpectedParameterEqualToArray()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA ([1])     -> 1
                                          ([2])     -> 2
                                          ([a : 5]) -> 5
                                                    -> 0');

        $this->assertEquals(1, $mock->getA(array(1)));
        $this->assertEquals(2, $mock->getA(array(2)));
        $this->assertEquals(0, $mock->getA(array(1, 2)));
        $this->assertEquals(0, $mock->getA(array(5)));
        $this->assertEquals(0, $mock->getA(array()));
        $this->assertEquals(5, $mock->getA(array('a' => 5)));
    }

    public function testMethodWithCall()
    {
        $data['callback'] = "array_sum";

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> call $callback', $data);

        $this->assertEquals(12, $mock->getA(array(4, 3, 4, 1)));
        $this->assertEquals(7, $mock->getA(array(4, 3)));
        $this->assertEquals(1111, $mock->getA(array(1000, 100, 10, 1)));
    }

    public function testMethodWithCallProvidedCallback()
    {
        $data['callback'] = function ($a, $b, $c) {
            return $a . '.' . $b . '.' . $c;
        };

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> call $callback', $data);

        $this->assertEquals('11.12.13', $mock->getA(11, 12, 13));
        $this->assertEquals('a.b.c', $mock->getA('a', 'b', 'c'));
    }

    public function testArrayWithFullGetClass()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getArray -> [with Mock\tests\ResourceClasses\X
                                                      getA -> "a" as $b
                                                  get Mock\tests\ResourceClasses\X
                                                      getB -> $b,
                                                  Mock\tests\ResourceClasses\X
                                                      getA -> self]');

        list($a1, $a2) = $mock->getArray();

        $this->assertEquals("a", $a1->getB()->getA());
        $this->assertEquals($a2, $a2->getA());
    }

    public function testReturnArray()
    {
        $data = $this->getSMock('with Mock\tests\ResourceClasses\A as $a
                                 with Mock\tests\ResourceClasses\X as $x
                                 get [a : $a, x : $x]');

        $this->assertTrue(is_array($data));
        $this->assertTrue($data['a'] instanceof A);
        $this->assertTrue($data['x'] instanceof X);
    }

    public function testUndefinedMethod()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> 5
                                     getB -> undefined');
        /*
         *  This code is here to show difference between defined and undefined method
         *  This assertion would pass but its commented because it is not desired behaviour
         *
         *  $mock->expects($this->any())->method('getA')->will($this->returnValue(100));
         *  $this->assertEquals(5, $mock->getA());
         */

        $mock->expects($this->any())
             ->method('getB')
             ->will($this->returnValue(100));

        $this->assertEquals(100, $mock->getB());
    }

    public function testDefineAllMethods()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> "a"
                                     * -> "*"
                                     getB -> "b"');

        $this->assertEquals("a", $mock->getA());
        $this->assertEquals("b", $mock->getB());
        $this->assertEquals("*", $mock->getX());
        $this->assertEquals("*", $mock->a());
    }

    public function testDefineMethodsByRegex()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> "a"
                                     getB -> "b"
                                     /^get/ -> 0
                                     /^set/ -> self');

        $this->assertEquals("a", $mock->getA());
        $this->assertEquals("b", $mock->getB());
        $this->assertEquals(0, $mock->getX());
        $this->assertEquals(0, $mock->getChild());
        $this->assertEquals($mock, $mock->setA());
        $this->assertEquals("undefined", $mock->sign());
    }

    public function testOriginOnRegexDefinedMethods()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     * (0) -> 0
                                           -> origin');

        $this->assertEquals(0, $mock->getA(0));
        $this->assertEquals("undefined", $mock->getA(5));
        $this->assertEquals(0, $mock->check(0));
        $this->assertEquals("undefined", $mock->check(5));
    }

    public function testDefineDefaultReturnNull()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     default : null
                                     getA (1) -> 10
                                          (2) -> 20');

        $this->assertEquals(10, $mock->getA(1));
        $this->assertEquals(20, $mock->getA(2));
        $this->assertEquals(null, $mock->getA(3));
        $this->assertEquals(null, $mock->getA(1, 0));
    }

    public function testDefineDefaultReturnOrigin()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     default : origin
                                     getA (*, *) -> 2
                                          (*) -> 1');

        $this->assertEquals(2, $mock->getA(1, 6));
        $this->assertEquals(1, $mock->getA(2));
        $this->assertEquals("undefined", $mock->getA());
        $this->assertEquals("undefined", $mock->getA(3, null, null));
    }

    public function testDefaultAffectsOnlyDefinedMethods()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     default : null
                                     getA (1) -> 10
                                          (2) -> 20');

        $this->assertEquals("undefined", $mock->getB());
        $this->assertEquals("undefined", $mock->getC(2));;
    }

    public function testUse()
    {
        $mock = $this->getSMock('use Mock\tests\ResourceClasses\X
                                 X');

        $this->assertTrue($mock instanceof X);
    }

    public function testUseAs()
    {
        $mock = $this->getSMock('use Mock\tests\ResourceClasses\X as ResourceX
                                 ResourceX');

        $this->assertTrue($mock instanceof X);
    }

    public function testUseAsCannotBeAccessedByDefaultClassShortName()
    {
        $this->setExpectedException('Mock\MockBuildException');

        $this->getSMock('use Mock\tests\ResourceClasses\X as ResourceX
                         X');
    }

    public function testUseWithGet()
    {
        $mock = $this->getSMock('use Mock\tests\ResourceClasses\X
                                 with X as $x
                                 get X
                                     getX -> $x');

        $this->assertTrue($mock instanceof X);
        $this->assertTrue($mock->getX() instanceof X);
    }

    public function testUseInNestedMock()
    {
        $mock = $this->getSMock('use Mock\tests\ResourceClasses\X as ResourceX
                                 ResourceX
                                     getA -> ResourceX
                                         getA -> [ResourceX
                                                      getA -> ResourceX,
                                                  ResourceX]');

        $this->assertTrue($mock instanceof X);
        $this->assertTrue($mock->getA() instanceof X);
        $arr = $mock->getA()->getA();
        $this->assertTrue(is_array($arr));
        $this->assertTrue($arr[0] instanceof X);
        $this->assertTrue($arr[0]->getA() instanceof X);
        $this->assertTrue($arr[1] instanceof X);
    }

    public function testUseWithArray()
    {
        $mock = $this->getSMock('use Mock\tests\ResourceClasses\X as Z
                                 get [Z, Z, Z]');

        $this->assertTrue($mock[0] instanceof X);
        $this->assertTrue($mock[1] instanceof X);
        $this->assertTrue($mock[2] instanceof X);
        $this->assertTrue(empty($mock[3]));
    }

    public function testUseOverriding()
    {
        $mock = $this->getSMock('use Mock\tests\ResourceClasses\X as Z
                                 Z
                                     getA -> use Mock\tests\ResourceClasses\A as Z
                                             Z');

        $this->assertTrue($mock instanceof X);
        $this->assertTrue($mock->getA() instanceof A);
    }

    public function testUseWithClassConstAndVar()
    {
        $mock = $this->getSMock('use Mock\tests\ResourceClasses\X as Z
                                 Z
                                     getA -> Z::$va
                                     getB -> Z::CB');

        $this->assertEquals(X::$va, $mock->getA());
        $this->assertEquals(X::CB, $mock->getB());
    }

    public function testUseAliasAndReleaseAliasFunction()
    {
        Mock::getInstance()->useAlias('Mock\tests\ResourceClasses\X', 'ResourceClassX');
        $this->assertTrue($this->getSMock('ResourceClassX') instanceof X);

        Mock::getInstance()->releaseAlias('ResourceClassX');
        $this->setExpectedException('Mock\MockBuildException');
        $this->getSMock('ResourceClassX');
    }

    public function testPropertySet()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A
                                     $a -> 34
                                     $b -> "str"');

        $this->assertEquals('34str', $mock->concat());
    }

    public function testOnce()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> once Mock\tests\ResourceClasses\X
                                         getA -> once with Mock\tests\ResourceClasses\X as $q
                                                 get Mock\tests\ResourceClasses\X
                                                     getA -> $q');

        $this->assertSame($mock->getA(), $mock->getA());
        $this->assertSame($mock->getA()->getA(), $mock->getA()->getA());
        $this->assertSame($mock->getA()->getA()->getA(), $mock->getA()->getA()->getA());
    }

    public function testInheritMethodsFromTemplate()
    {
        $mock = $this->getSMock('template T : Mock\tests\ResourceClasses\X
                                     getA -> "template A"
                                 T
                                     getB -> "B"');

        $this->assertTrue($mock instanceof X);
        $this->assertEquals("template A", $mock->getA());
        $this->assertEquals("B", $mock->getB());
    }

    public function testOverridingMethodsFromTemplate()
    {
        $mock = $this->getSMock('template T : Mock\tests\ResourceClasses\X
                                     getA -> "template A"
                                     getB -> "template B"
                                 T
                                     getB -> "B"');

        $this->assertTrue($mock instanceof X);
        $this->assertEquals("template A", $mock->getA());
        $this->assertEquals("B", $mock->getB());
    }

    public function testMethodsFromTemplateAreExtendedNotFullyOverrided()
    {
        $mock = $this->getSMock('template T : Mock\tests\ResourceClasses\X
                                     getA (2) -> "template A"
                                          (1) -> "template A"
                                 T
                                     default : "default"
                                     getA (1) -> "A"');

        $this->assertTrue($mock instanceof X);
        $this->assertEquals("A", $mock->getA(1));
        $this->assertEquals("template A", $mock->getA(2));
        $this->assertEquals("default", $mock->getA(3));
    }

    public function testInheritPropertiesFromTemplate()
    {
        $mock = $this->getSMock('template T : Mock\tests\ResourceClasses\A
                                     $a -> "template A"
                                 T
                                     $b -> "B"');

        $this->assertTrue($mock instanceof A);
        $this->assertEquals("template A", $mock->getA());
        $this->assertEquals("B", $mock->getB());
        $this->assertEquals("template AB", $mock->concat());
    }

    public function testOverridePropertiesFromTemplate()
    {
        $mock = $this->getSMock('template T : Mock\tests\ResourceClasses\A
                                     $a -> "template A"
                                     $b -> "template B"
                                 T
                                     $a -> "A"');

        $this->assertTrue($mock instanceof A);
        $this->assertEquals("A", $mock->getA());
        $this->assertEquals("template B", $mock->getB());
        $this->assertEquals("Atemplate B", $mock->concat());
    }

    public function testTemplateWithDefault()
    {
        $mock = $this->getSMock('template T : Mock\tests\ResourceClasses\A
                                     default : 1
                                 T
                                     getA (5) -> 10');

        $this->assertEquals(10, $mock->getA(5));
        $this->assertEquals(1, $mock->getA());
    }

    public function testTemplateDefaultOverriding()
    {
        $mock = $this->getSMock('template T : Mock\tests\ResourceClasses\A
                                     default : 1
                                 T
                                     default : 2
                                     getA (5) -> 10');

        $this->assertEquals(10, $mock->getA(5));
        $this->assertEquals(2, $mock->getA());
    }

    public function testVarInTemplateAreInClassDefinitionScopeNotInTemplateScope()
    {
        $data = array('a' => 'data A', 'b' => 'data B', 'c' => 'data C');

        $mock = $this->getSMock('template T : Mock\tests\ResourceClasses\X
                                     getA -> $a
                                     getB -> $b
                                     getC -> $c
                                 get Mock\tests\ResourceClasses\X
                                     getT -> T.
                                     getX -> get Mock\tests\ResourceClasses\X
                                         getT -> T
                                     as $b
                                 as $a', $data);

        $b = $mock->getX();
        $this->assertEquals($mock, $mock->getT()->getA());
        $this->assertEquals('data B', $mock->getT()->getB());
        $this->assertEquals('data C', $mock->getT()->getC());

        $this->assertEquals($mock, $b->getT()->getA());
        $this->assertEquals($b, $b->getT()->getB());
        $this->assertEquals('data C', $b->getT()->getC());
    }

    public function testExtendingTemplate()
    {
        $mock = $this->getSMock('template T : Mock\tests\ResourceClasses\X
                                     getA -> "T-A"
                                     getB -> "T-B"
                                     getC -> "T-C"
                                 template P : T
                                     getA -> "P-A"
                                     getC -> "P-C"
                                 P
                                     getC -> "C"');

        $this->assertEquals("C", $mock->getC());
        $this->assertEquals("P-A", $mock->getA());
        $this->assertEquals("T-B", $mock->getB());
    }

    public function testOverridingClassWithTemplate()
    {
        $mock = $this->getSMock('template Mock\tests\ResourceClasses\X : Mock\tests\ResourceClasses\X
                                     getA -> "T-A"
                                 Mock\tests\ResourceClasses\X
                                     getB -> "B"');

        $this->assertEquals("T-A", $mock->getA());
        $this->assertEquals("B", $mock->getB());
    }

    public function testMockingMethodWithChangedResultType()
    {
        $this->setExpectedException('Mock\MockBuildException');
        $this->getSMock('Mock\tests\ResourceClasses\X
                             tInt -> "aa"');
    }

    public function testMockingMethodWithCorrectResultType()
    {
        $this->getSMock('Mock\tests\ResourceClasses\X
                             tInt -> 12');
    }

    public function testMockingMethodWithChangedResultTypeFromConst()
    {
        $this->setExpectedException('Mock\MockBuildException');
        $this->getSMock('Mock\tests\ResourceClasses\X
                             tInt -> Mock\tests\ResourceClasses\X::CB');
    }

    public function testMockingMethodWithCorrectResultTypeFromClassVariable()
    {
        $this->getSMock('Mock\tests\ResourceClasses\X
                             tInt -> Mock\tests\ResourceClasses\X::$vb');

    }

    public function testMockingMethodWithChangedResultTypeInRuntime()
    {
        $data = new \ArrayObject(array('a' => 12));

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     tInt -> $a', $data);

        $mock->tInt();
        $this->setExpectedException('Mock\MockBuildException');
        $data['a'] = 'str';
        $mock->tInt();
    }

    public function testMockingMethodWithCorrectResultTypeClass()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     tA -> Mock\tests\ResourceClasses\A');
        $mock->tA();
    }

    public function testMockingMethodWithChangedResultTypeClass()
    {
        $this->setExpectedException('Mock\MockBuildException');

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     tA -> Mock\tests\ResourceClasses\X');
        $mock->tA();
    }

    public function testPHPUnitMethod()
    {
        $data = array(
            'a' => $this->returnValue('A'),
            'c' => $this->onConsecutiveCalls(1, 2, 3, 4, 5),
            'x' => $this->throwException(new \Exception())
        );

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                              getA -> phpUnit $a
                                              getC -> phpUnit $c
                                              getX -> phpUnit $x', $data);

        $this->assertEquals('A', $mock->getA());
        $this->assertEquals(1, $mock->getC());
        $this->assertEquals(2, $mock->getC());
        $this->setExpectedException('Exception');
        $this->assertEquals($mock, $mock->getX());
    }

    public function testMethodCodeBasic()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> { return 5 + 6; }
                                     getB -> { return "a" . "q"; }');

        $this->assertEquals(11, $mock->getA());
        $this->assertEquals("aq", $mock->getB());
    }

    public function testMethodCodeComplexExpression()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> { return true ? "a" . (7 - 2 * 2 - 1) : "o"; }
                                     getB -> { return 2 * 3 == 6; }');

        $this->assertEquals("a2", $mock->getA());
        $this->assertEquals(true, $mock->getB());
    }

    public function testMethodCodeWithCreatingNewVariable()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> { $q = 10;
                                               $x = 7;
                                               return $q + $x; }');

        $this->assertEquals(17, $mock->getA());
    }

    public function testMethodCodeWithReturnAlias()
    {
        $mock = $this->getSMock('with  Mock\tests\ResourceClasses\X as $a
                                 get Mock\tests\ResourceClasses\X
                                     a    -> $a
                                     getA -> { return $a; }');

        $this->assertEquals($mock->a(), $mock->getA());
    }

    public function testMethodCodeWithMethodCall()
    {
        $mock = $this->getSMock('with  Mock\tests\ResourceClasses\A(8, 5) as $a
                                 get Mock\tests\ResourceClasses\X
                                     +getSum -> { return $a->getA() + $a->getB(); }
                                     +getDiff -> { return $a->getA() - $a->getB(); }');

        $this->assertEquals(13, $mock->getSum());
        $this->assertEquals(3, $mock->getDiff());
    }

    public function testMethodCodeWithArray()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> { return array(1, 2, 3, 4);}
                                     getB -> { return array("a" => 1, 1 => "a"); }
                                     getC -> { return array(); }');

        $this->assertEquals(array(1, 2, 3, 4), $mock->getA());
        $this->assertEquals(array("a" => 1, 1 => "a"), $mock->getB());
        $this->assertEquals(array(), $mock->getC());
    }

    public function testMethodCodeWithFor()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> { $q = array();
                                               for($i = 0; $i < 5; $i++) {
                                                   $q[] = $i;
                                               };
                                               return $q; }');

        $this->assertEquals(array(0, 1, 2, 3, 4), $mock->getA());
    }

    public function testMethodCodeIf()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     +toBool (*) -> { if ($args[0]) {
                                                          return true;
                                                      }
                                                      return false; }');

        $this->assertFalse($mock->toBool(0));
        $this->assertFalse($mock->toBool(null));
        $this->assertTrue($mock->toBool(5));
        $this->assertTrue($mock->toBool("qwert"));
    }

    public function testMethodCodeNewClass()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     +abs (*) -> { if (!is_integer($args[0]))
                                                       throw new InvalidArgumentException();
                                                   else if ($args[0] > 0)
                                                       return $args[0];
                                                   else
                                                       return -$args[0]; }');

        $this->assertEquals(5, $mock->abs(5));
        $this->assertEquals(5, $mock->abs(-5));

        $this->setExpectedException('InvalidArgumentException');
        $mock->abs("str");
    }

    public function testClassAliasInMethodCode()
    {
        $mock = $this->getSMock('use Mock\tests\ResourceClasses\X as ResourceX
                                 use Mock\tests\ResourceClasses\A
                                 Mock\tests\ResourceClasses\X
                                     getA -> { return new A(1, 3); }
                                     getX -> { return new ResourceX(); }
                                     +getStd -> { return new stdClass(); }');

        $this->assertTrue($mock->getA() instanceof A);
        $this->assertTrue($mock->getX() instanceof X);
        $this->assertTrue($mock->getStd() instanceof stdClass);
    }

    public function testMethodCodeDataAccess()
    {
        $data = array('a' => 'erdsfdtsdf', 'b' => '5shsf9df2fh');

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> { return $a; }
                                     getB -> { return $b; }', $data);

        $this->assertEquals($data['a'], $mock->getA());
        $this->assertEquals($data['b'], $mock->getB());
    }

    public function testMethodCodeAccessToProtectedDataReading()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A(5, 8)
                                     +getSum -> { return $this->a + $this->b; }');

        $this->assertEquals(13, $mock->getSum());
    }

    public function testMethodCodeAccessToProtectedDataWriting()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A(5, 8)
                                     +reset -> { $this->a = 0;
                                                 $this->b = 0;
                                                 return $this; }');

        $this->assertEquals(5, $mock->getA());
        $this->assertEquals(8, $mock->getB());
        $this->assertEquals($mock, $mock->reset());
        $this->assertEquals(0, $mock->getA());
        $this->assertEquals(0, $mock->getB());
    }

    public function testMethodCodeAccessToProtectedDataMethodInvoke()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> { return $this->protectedMethod(); }');

        $this->assertEquals("undefined", $mock->getA());
    }

    public function testMethodCodeAccessToPrivateDataMethodInvoke()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A(5, 8)
                                     concat -> { return $this->concatWithSeparator(","); }');

        $this->assertEquals("5,8", $mock->concat());
    }

    public function testMethodCodeThisMethodMustReferToOverridedVersion()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A("a", "b")
                                     getA -> "c"
                                     concat -> { return $this->getB() . $this->getA(); }');

        $this->assertEquals("bc", $mock->concat());
    }

    public function testMethodCodeWithChangedResultTypeInRuntime()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     tInt -> { return "abc"; }');

        $this->setExpectedException('Mock\MockBuildException');
        $mock->tInt();
    }

    public function testMethodCodeWithCorrectResultTypeInRuntime()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     tInt -> { return 123; }');

        $mock->tInt();
    }

    public function testMethodWithExpectedParamNotationCanContainX()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA (x > 10) -> ">10"
                                          (x <= 0) -> "<=0"
                                          (x === 3)-> "3"
                                                   -> ""');

        $this->assertEquals(">10", $mock->getA(11));
        $this->assertEquals("", $mock->getA(10));
        $this->assertEquals("", $mock->getA(1));
        $this->assertEquals("<=0", $mock->getA(0));
        $this->assertEquals("<=0", $mock->getA(-1));
        $this->assertEquals("3", $mock->getA(3));
        $this->assertEquals("", $mock->getA("3"));
    }

    public function testMethodWithExpectedParamLogicOperators()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     default : false
                                     getA (x > 10 || x < 0) -> true
                                     getB (x < 10 && x > 5) -> true');

        $this->assertFalse($mock->getA(5));
        $this->assertTrue($mock->getA(20));
        $this->assertTrue($mock->getA(-5));
        $this->assertFalse($mock->getB(20));
        $this->assertFalse($mock->getB(0));
        $this->assertTrue($mock->getB(7));
    }

    public function testMethodWithExpectedParamLogicOperatorsExecutedInCorrectOrder()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     default : false
                                     getA (x == 0 || x < 10 && x > 5) -> true
                                     getB (x < 10 && x > 5 || x == 0) -> true');

        $this->assertFalse($mock->getA(20));
        $this->assertFalse($mock->getA(1));
        $this->assertTrue($mock->getA(7));
        $this->assertTrue($mock->getA(0));

        $this->assertFalse($mock->getB(20));
        $this->assertFalse($mock->getB(1));
        $this->assertTrue($mock->getB(7));
        $this->assertTrue($mock->getB(0));
    }

    public function testMethodCanUseBracketsInExpectedParam()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA ((x == 20 || x < 10 || x == 0) && x > 5) -> true
                                                                                   -> false');

        $this->assertFalse($mock->getA(1));
        $this->assertFalse($mock->getA(0));
        $this->assertTrue($mock->getA(7));
        $this->assertFalse($mock->getA(15));
        $this->assertTrue($mock->getA(20));
    }

    public function testMethodWithExpectedParamType()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     +getType (bool)  -> "bool"
                                              (array) -> "array"
                                              (string)-> "string"');

        $this->assertEquals("bool", $mock->getType(true));
        $this->assertEquals("bool", $mock->getType(false));
        $this->assertEquals("array", $mock->getType(array()));
        $this->assertEquals("string", $mock->getType(""));
        $this->assertEquals("string", $mock->getType("abc"));
    }

    public function testMethodWithExpectedParamClassType()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     +getClassName (Mock\tests\ResourceClasses\A) -> "A"
                                                   (Mock\tests\ResourceClasses\X) -> "X"
                                                   (*)                            -> "unknown class name"');

        $this->assertEquals("A", $mock->getClassName(new A(1,2)));
        $this->assertEquals("X", $mock->getClassName($mock));
        $this->assertEquals("unknown class name", $mock->getClassName($this)); // $this in getClassName is used as an arbitrary object
    }

    public function testMethodWithExpectedParamNotOperator()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA (!int)  -> null
                                          (!x > 10) -> true
                                          (! <= 10) -> false');

        $this->assertNull($mock->getA("0"));
        $this->assertFalse($mock->getA(11));
        $this->assertTrue($mock->getA(10));
    }

    public function testMethodWithExpectedParamArrayKey()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA (["a" : *]) -> true
                                                      -> false');

        $this->assertFalse($mock->getA(1));
        $this->assertFalse($mock->getA("a"));
        $this->assertFalse($mock->getA(array()));
        $this->assertFalse($mock->getA(array('b' => 1)));
        $this->assertTrue($mock->getA(array('a' => 2)));
        $this->assertTrue($mock->getA(array('a' => null)));
    }

    public function testLiteralKeyInArrayPatternAlwaysIsMatchedAsString()
    {
        //TODO:
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA ([a || b : *]) -> true
                                                         -> false');

        $this->assertFalse($mock->getA(1));
        $this->assertFalse($mock->getA("a"));
        $this->assertFalse($mock->getA(array()));
        $this->assertTrue($mock->getA(array('b' => 1)));
        $this->assertTrue($mock->getA(array('a' => 2)));
        $this->assertTrue($mock->getA(array('a' => null)));
        $this->assertFalse($mock->getA(array('c' => 1)));
    }

    public function testArrayPatternWithoutUnknownNodes()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA ([a : *]) -> true
                                                    -> false');

        $this->assertFalse($mock->getA(array('a' => 1, 'b' => 2)));
    }

    public function testArrayPatternWithUnknownNodes()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA ([a : *,]) -> true
                                                     -> false');

        $this->assertTrue($mock->getA(array('a' => 1, 'b' => 2)));
    }

    public function testMethodWithExpectedParamArrayValue()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA ([* :1,]) -> true
                                                    -> false');

        $this->assertFalse($mock->getA(1));
        $this->assertFalse($mock->getA("a"));
        $this->assertFalse($mock->getA(array()));
        $this->assertTrue($mock->getA(array(1)));
        $this->assertTrue($mock->getA(array("b" => 1)));
        $this->assertTrue($mock->getA(array(1, 2, 3)));
    }

    public function testMethodWithExpectedParamArrayCanContainOtherPatterns()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA ([/\d+/ : Mock\tests\ResourceClasses\X]) -> true
                                                                                   -> false');

        $this->assertFalse($mock->getA(1));
        $this->assertFalse($mock->getA(array()));
        $this->assertFalse($mock->getA(array(2 => new A(1, 2))));
        $this->assertTrue($mock->getA(array(3 => new X())));
        $this->assertTrue($mock->getA(array(1 => new X(), 2 => new X())));
        $this->assertFalse($mock->getA(array("g" => new X())));
        $this->assertFalse($mock->getA(array(4 => new A(1, 2), "b" => new X())));
        $this->assertFalse($mock->getA(array("a" => new A(1, 2), 5 => new X())));
    }

    public function testMethodWithExpectedParamSeveralArraySubpatterns()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA ([a : > 10, b : < 10]) -> true
                                                                 -> false');

        $this->assertFalse($mock->getA(1));
        $this->assertFalse($mock->getA(array()));
        $this->assertFalse($mock->getA(array('a' => 9, 'b' => 11)));
        $this->assertFalse($mock->getA(array('a' => 9, 'b' => 9)));
        $this->assertFalse($mock->getA(array('a' => 11, 'b' => 11)));
        $this->assertTrue($mock->getA(array('a' => 11, 'b' => 9)));
        $this->assertFalse($mock->getA(array('a' => 11)));
        $this->assertFalse($mock->getA(array('b' => 10)));
    }

    public function testMatchRegexInFunctionParam()
    {
        $data['a'] = '/^a+b*$/';
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA (match $a) -> true
                                                     -> false', $data);

        $this->assertFalse($mock->getA('cc'));
        $this->assertTrue($mock->getA('aaab'));
    }

    public function testMatchCallbackInFunctionParam()
    {
        $data['a'] = function ($x) {
            return $x > 0;
        };

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA (match $a) -> true
                                                     -> false', $data);

        $this->assertTrue($mock->getA(1));
        $this->assertFalse($mock->getA(-1));
    }

    public function testMatchConstraintInFunctionParam()
    {
        $data['a'] = $this->equalTo(12);

        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA (match $a) -> true
                                                     -> false', $data);

        $this->assertTrue($mock->getA(12));
        $this->assertFalse($mock->getA(1));
    }

    public function testMatchObjectWithPropertyConstrain()
    {
        $mock = $this->getSMock('use Mock\tests\ResourceClasses\A
                                 get A
                                     getA (A[c : 34]) -> true
                                                      -> false');

        $this->assertFalse($mock->getA(1));
        $x = new X();
        $x->q = 34;
        $this->assertFalse($mock->getA($x));

        $x = new A(1, 1);
        $x->c = 3;
        $this->assertFalse($mock->getA($x));

        $x = new A(1, 1);
        $x->q = 34;
        $this->assertFalse($mock->getA($x));

        $x = new A(1, 1);
        $x->c = 34;
        $this->assertTrue($mock->getA($x));
    }

    public function testMatchObjectWithPrivatePropertyConstrain()
    {
        $mock = $this->getSMock('use Mock\tests\ResourceClasses\A
                                 get A
                                     getA (A[b : 34]) -> true
                                                      -> false');

        $this->assertFalse($mock->getA(new A(1, 2)));
        $this->assertTrue($mock->getA(new A(1, 34)));
    }

    public function testMatchObjectWithMethodConstrain()
    {
        $mock = $this->getSMock('use Mock\tests\ResourceClasses\A
                                 get A
                                     getA (A[concat : "ab"]) -> true
                                                             -> false');

        $this->assertFalse($mock->getA(new A("a", "bb")));
        $this->assertTrue($mock->getA(new A("a", "b")));
        $this->assertTrue($mock->getA(new A("", "ab")));
    }

    protected $testCaseProtectedProperty = "protected property";

    public function testThisTestCasePropertyAccessibleInData()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A
                                     getA -> $testCaseProtectedProperty');

        $this->assertEquals($this->testCaseProtectedProperty, $mock->getA());
    }

    public function testExtraDataOverridesDataFromTestCase()
    {
        $data = array('testCaseProtectedProperty' => 'value from array');

        $mock = $this->getSMock('Mock\tests\ResourceClasses\A
                                     getA -> $testCaseProtectedProperty', $data);

        $this->assertEquals($data['testCaseProtectedProperty'], $mock->getA());
    }

    protected function getTestCaseProtectedThing()
    {
        return "protected things";
    }

    public function testThisTestCaseGetterAccessibleInData()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A
                                     getA -> $testCaseProtectedThing');

        $this->assertEquals($this->getTestCaseProtectedThing(), $mock->getA());
    }

    public function testRandomValueWithRegex()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A
                                     getA -> /[a-c]{2}-00\d/');

        $values = array();
        for($i = 0; $i <= 70; $i++) {
            $newValue = $mock->getA();
            $this->assertRegexp('/^[a-f]{2}-00\d$/', $newValue);
            $this->assertArrayNotHasKey($newValue, $values);
            $values[$newValue] = true;
        }
    }

    public function testRandomValueWithUnique()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> /\d/
                                     getB -> unique:"2"
                                     getC -> unique:"4"
                                     getX -> unique:"9"');

        $values = array(2 => true, 4 => true, 9 => true);
        for($i = 1; $i <= 7; $i++) {
            $newValue = $mock->getA();
            $this->assertRegexp('/^\d$/', $newValue);
            $this->assertArrayNotHasKey($newValue, $values);
            $values[$newValue] = true;
        }
    }

    public function testRandomValueProduceFailIfCantGenerateValue()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\X
                                     getA -> /\d/');

        for($i = 1; $i <= 10; $i++) {
            $mock->getA();
        }

        $this->setExpectedException('PHPUnit_Framework_AssertionFailedError');
        $mock->getA();
    }

    public function testConsecutive()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A(10, 20)
                                     getA -> consecutive(1, "b", self, origin, throw BadMethodCallException)');

        $this->assertEquals(1, $mock->getA());
        $this->assertEquals("b", $mock->getA());
        $this->assertEquals($mock, $mock->getA());
        $this->assertEquals(10, $mock->getA());
        $this->setExpectedException('BadMethodCallException');
        $mock->getA();
    }

    public function testConsecutiveLastItemIsDefault()
    {
        $mock = $this->getSMock('Mock\tests\ResourceClasses\A(10, 20)
                                     getA -> consecutive(1, 2, null)');

        $this->assertEquals(1, $mock->getA());
        $this->assertEquals(2, $mock->getA());
        $this->assertEquals(null, $mock->getA());
        $this->assertEquals(null, $mock->getA());
        $this->assertEquals(null, $mock->getA());
    }
}