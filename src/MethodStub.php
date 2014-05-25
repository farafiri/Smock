<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 06.12.13
 * Time: 22:19
 */

namespace Mock;


class MethodStub extends \PHPUnit_Framework_MockObject_Stub_ReturnCallback
{
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    public function invoke(\PHPUnit_Framework_MockObject_Invocation $invocation)
    {
        return call_user_func_array($this->callback, array($invocation));
    }
} 