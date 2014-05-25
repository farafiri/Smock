<?php

namespace Mock\tests\ResourceClasses;

class Filter
{
    public function filter($input)
	{
	    $result = array();
		foreach($input as $item) {
		    if ($this->check($item)) {
			    $result[] = $item;
			}
		}
		
		return $result;
	}
	
	protected function check($item)
	{
	    return $item > 0;
	}
}