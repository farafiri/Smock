<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 17.12.13
 * Time: 13:23
 */

namespace Mock\Utils\TypeMatcher;


class TypeMatcherBuilder
{
    protected $cache = array();
    protected $parser;
    protected $docNameToTypeMatcherClassName = array(
        'int' => 'Mock\Utils\TypeMatcher\TInteger',
        'integer' => 'Mock\Utils\TypeMatcher\TInteger',
        'bool' => 'Mock\Utils\TypeMatcher\TBoolean',
        'boolean' => 'Mock\Utils\TypeMatcher\TBoolean',
        'float' => 'Mock\Utils\TypeMatcher\TFloat',
        'real' => 'Mock\Utils\TypeMatcher\TFloat',
        'double' => 'Mock\Utils\TypeMatcher\TFloat',
        'string' => 'Mock\Utils\TypeMatcher\TString',
        'resource' => 'Mock\Utils\TypeMatcher\TResource',
        'callable' => 'Mock\Utils\TypeMatcher\TCallable',
        'array' => 'Mock\Utils\TypeMatcher\TArray',
        'object' => 'Mock\Utils\TypeMatcher\TObject',
        'mixed' => 'Mock\Utils\TypeMatcher\TMixed'
    );

    public function __construct()
    {
        $this->parser = new \ParserGenerator\Parser('
            type :simple=> /integer|int|boolean|bool|float|real|double|string|resource|callable|array|object|mixed/
                 :class=> /[A-Za-z_\\\\][A-Za-z_0-9\\\\]*/
                 :alternative=> "(" type+"|" ")"
                 :collection=> type "[]".
        ');
    }

    public function get($docType)
    {
        $fullDocType = '(' . $docType . ')';
        if (isset($this->cache[$fullDocType])) {
            return $this->cache[$fullDocType];
        }

        $node = $this->parser->parse($fullDocType, 'type');
        return $this->getFromNode($node);
    }

    public function getFromNode($docTypeNode)
    {
        $docType = (string) $docTypeNode;
        if (!isset($this->cache[$docType])) {
            switch ($docTypeNode->getDetailType()) {
                case "simple":
                    $typeMatcherClassName = $this->docNameToTypeMatcherClassName[(string) $docTypeNode];
                    $typeMatcher = new $typeMatcherClassName();
                    break;
                case "class":
                    $className = (string) $docTypeNode;
                    $className = substr($className, 0, 1) == '\\' ? substr($className, 1) : $className;
                    $typeMatcher = new IsInstanceOf($className);
                    break;
                case "alternative":
                    $alternatives = array();
                    foreach($docTypeNode->getSubnode(1)->getMainNodes() as $subDocTypeNode) {
                        $alternatives[] = $this->getFromNode($subDocTypeNode);
                    }

                    $typeMatcher = (count($alternatives) == 1) ? $alternatives[0] : new Alternative($alternatives);
                    break;
                case "collection":
                    $typeMatcher = new ArrayOf($this->getFromNode($docTypeNode->getSubnode(0)));
                    break;
            }

            $this->cache[$docType] = $typeMatcher;
            return $typeMatcher;
        }

        return $this->cache[$docType];
    }
} 