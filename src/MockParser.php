<?php

namespace Mock;

use ParserGenerator\Parser;

class MockParser extends Parser
{
    public static $mockGrammar = "
	    start :=> value.
		value : expr  => exprValue
		      : get   => (classAlias|template)* classGet
			  : class => (classAlias|template)* class
			  : regex => regex
			  : uniq  => 'unique:' STRING.
        template :=> 'template' WHITESPACE className ':' class.
	    exprValue :simple => simpleValue
		          :array => array
		          :classConst => className !WHITESPACE '::' !WHITESPACE /[a-zA-Z0-9_]+/
				  :classVar => className !WHITESPACE '::$' !WHITESPACE /[a-zA-Z0-9_]+/
		          :ref    => /\\\$[a-zA-Z0-9_]+/.
		array :=> '[' arrayValue*',' ']'.
		arrayValue :simple=> value
		           :keya=> /[a-zA-Z0-9_]+/ ':' value
				   :keyb=> exprValue ':' value.
	    simpleValue : string  => STRING
		            : numeric => -inf..inf
			        : null => 'null'
			        : boolean => /true|false/.
	    class :=> className constructorParams? defaultMethodBehaviour? mockedMethod* setProperty* '.'?.
	    setProperty :=> /\\\$[a-zA-Z0-9_]+/ '->' value.
	    classAlias :as=> 'use' WHITESPACE className 'as' WHITESPACE className
	               :default=> 'use' WHITESPACE className.
		className :=> /[A-Za-z_][A-Za-z0-9_\\\\]*/ not is ('self'| 'origin'| 'true'| 'false'| 'null'| 'with'| 'as'| 'get'| 'throw'| 'add'| 'fail'| 'call'| 'once'| 'use').
		classGet :named=> ('' 'with' WHITESPACE value 'as' WHITESPACE alias)* 'get' WHITESPACE value 'as' WHITESPACE alias
		         :unnamed=> ('' 'with' WHITESPACE value 'as' WHITESPACE alias)* 'get' WHITESPACE value.
		alias :=> /\\\$[a-zA-Z0-9_]+/.
		constructorParams :=> '(' value*',' ')'.
		mockedMethod :undefined=> methodOverrideMode methodSelector '->' 'undefined'
		             :cases=> methodOverrideMode methodSelector methodCase+.
		methodSelector:name=> methodName
		              :all=> '*'
		              :regex=> regex.
		methodName :=> /[A-Za-z_][A-Za-z0-9_]*/.
		methodCase :=> methodParams? methodCondition* '->' methodExecute.
		methodOverrideMode :override=> ''
						   :add=> ('+' | '(add)') !WHITESPACE.
		methodParams :=> '(' pCondition*',' ')'.
		pCondition :logicOp=> pCondition ('||' | 'or' | '&&' | 'and') pCondition
		           :bracket=> '(' pCondition ')'
		           :comparison => /((x\\s)?(!==|!=|===|==|<=|>=|<|>))|/ exprValue
		           :array => '[' (pCondition ':' pCondition)+',' /,?/ ']'
		           :match=> 'match' exprValue
		           :type => phpTypes
		           :class => className ('[' (/[A-Za-z_][A-Za-z0-9_]*/ ':' pCondition)+',' ']')?
				   :regex      => regex
				   :not        => '!' pCondition
		           :anything   => '*'.
		regex :=> /\\/([^\\\\\\/]|\\\\.)+\\/\w*/ .
		defaultMethodBehaviour :=> 'default' ':' methodExecute.
		methodCondition :=> ''.
		methodExecute : this  => 'self'
		              : origin => 'origin'
		              : consecutive => 'consecutive' '(' methodExecute+',' ')'
		              : once => 'once' WHITESPACE value
					  : call  => 'call' exprValue
					  : phpUnit => 'phpUnit' WHITESPACE exprValue
		              : args  => /\\\$args\[\d+\]/
		              : value => value
		              : throw => 'throw' className
					  : fail  => 'fail' STRING?
					  : phpCode => phpCode.
	    phpCode :=> '{' phpStatement+ '}'.
	    phpStatement :=> 'if' '(' phpExpr ')' phpStatement 'else' phpStatement ';'?
	                 :=> 'if' '(' phpExpr ')' phpStatement ';'?
	                 :return=> 'return' WHITESPACE phpExpr ';'
	                 :=> 'throw' WHITESPACE phpExpr ';'
	                 :=> 'foreach' '(' phpExpr 'as' (phpVar '=>')? phpVar ')' phpStatement ';'?
	                 :=> 'for' '(' phpExpr ';' phpExpr ';' phpExpr ')' phpStatement ';'?
	                 :=> 'while' '(' phpExpr ')' phpStatement ';'?
	                 :=> 'do' phpStatement 'while' '(' phpExpr ')' ';'?
	                 :=> phpExpr ';'
	                 :=> phpCode ';'?.
	    phpExpr :assign=> phpExprElement /[\+\-\*\.]?=/ phpExpr
	            :=> phpExpr '?' phpExpr ':' phpExpr
	            :=> phpExprElement+phpBinaryOperator.
	    phpExprElement:=> '(' phpTypes ')' phpExprElement
	                  :=> '!' phpExprElement
	                  :=> '(' phpExpr ')'
	                  :=> '++' phpExprElement
	                  :=> '--' phpExprElement
	                  :=> '-' phpExprElement
	                  :=> phpExprElement '++'
	                  :=> phpExprElement '--'
	                  :method=> phpExprElement '->' phpName '(' phpExpr*',' ')'
	                  :property=> phpExprElement '->' phpName
	                  :=> phpExprElement '[' phpExpr? ']'
	                  :=> 'array' '(' phpArrayElement*',' ')'
	                  :=> 'new' WHITESPACE className '(' phpExpr*',' ')'
	                  :=> phpName '(' phpExpr*',' ')'
	                  :=> className '::' phpName
	                  :=> className '::$' phpName
	                  :=> className '::' phpName '(' phpExpr*',' ')'
	                  :var=> phpVar
	                  :=> simpleValue.
	    phpArrayElement :=> (phpExprElement '=>')? phpExpr.
	    phpName :=> /[A-Za-z_][A-Za-z0-9_]*/ .
	    phpVar :=> '$' phpName.
	    phpTypes :=> /boolean|bool|int|double|float|real|string|array|object/.
	    phpBinaryOperator :=> ('||'| '&&'| '+'| '.'| '-'| '/'| '%'| '*'| '|'| '&'| '<'| '>'| '<='| '>='| '==='| '=='| '!=='| '!='| 'instanceof').
	";
	
	public static $mockOptions = array('ignoreWhitespaces' => true);

    public function __construct()
	{
        $q = microtime(true);
        parent::__construct(static::$mockGrammar, static::$mockOptions);
        var_dump(microtime(true) - $q);
        //var_dump(serialize($this));
        //die();
    }	
}