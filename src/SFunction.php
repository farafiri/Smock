<?php

namespace Mock;

class SFunction
{
    static protected $functions = array();
    protected $params;
	protected $body;
	protected $_use;
	protected $functionId = null;
	
	public function __construct($body, $params, $_use = array())
	{
	    $this->params = $params;
		$this->body = $body;
		$this->_use = $_use;
	}
	
	public function getBody()
	{
	    return $this->body;
    }
	
	public function getParams()
	{
	    return $this->params;
    }
	
	public function getUse()
	{
	    return $this->params;
    }
	
	public function __invoke()
	{
	    if ($this->functionId === null) {
		    $this->functionId = spl_object_hash($this);
		    extract($this->_use);
		    static::$functions[$this->functionId] = eval('return ' . $this . ';');
		}
		
		return call_user_func_array(static::$functions[$this->functionId], func_get_args());
	}
	
	public function __dectruct()
	{
	    unset(static::$functions[$this->functionId]);
		$this->functionId = null;
	}
	
	public function __toString()
	{
	    $use = '';
		foreach($this->_use as $name => $value) {
		    $use .= ($use ? ',' : '') . '$' . $name;
		}
		if ($use) {
		  $use = 'use(' . $use . ')';
		}
		
		$params = '';
		foreach($this->params as $param) {
		    $params .= ($params ? ',' : '') . '$' . $param;
		}
		
	    return 'function(' . $params . ') ' . $use . ' {' . $this->body . '}';
	}
	
	public function __sleep() {
	    return array('params', 'body', '_use');
	}
	
	static public function __set_state($data)
	{
	    return new static($data['params'], $data['body'], $data['_use']);
	}
}