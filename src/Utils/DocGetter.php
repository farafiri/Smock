<?php
/**
 * Created by PhpStorm.
 * User: Rafał
 * Date: 03.12.13
 * Time: 12:24
 */

namespace Mock\Utils;

class DocGetter
{
    const R_PROPERTY = 'PROPERTY';
    const R_METHOD = 'METHOD';
    const R_CLASS = 'CLASS';

    protected $cache = array();

    /**
     * @param string $className
     * @param string $name      name of property or method
     * @param string $src       source (DocGetter::R_PROPERTY | DocGetter::R_METHOD | DocGetter::R_CLASS)
     * @param string $doc       name of doc property
     *
     * @return array with (value => value of doc, source => source class)
     */
    public function fullGet($className, $name, $src, $doc)
    {
        if ($src == self::R_CLASS) {
            $name = '';
        }
        $id = implode(':', array($className, $name, $src, $doc));
        if (!array_key_exists($id, $this->cache)) {
            try {
                if ($src == self::R_PROPERTY) {
                    $m = new \ReflectionProperty($className, $name);
                } elseif ($src == self::R_METHOD) {
                    $m = new \ReflectionMethod($className, $name);
                } else {
                    $m = new \ReflectionClass($className);
                }
                $docComment = str_replace(array("\r\n", "\n\r", "\r"), "\n", $m->getDocComment());
            } catch (\ReflectionException $e) {
                $docComment = '';
            }
            if (preg_match('/\* @' . $doc . '\s((.|\n|\r)+?)(\n\s*)?(\* @|\*\/|$)/', $docComment, $match)) {
                $doc = preg_replace('/\n\s*\*/', "\n", $match[1]);
                $this->cache[$id] = array('value' => $doc, 'source' => $className);
            } else {
                $this->cache[$id] = null;

                $parentClass = get_parent_class($className);
                if (($src !== self::R_CLASS) && $parentClass) {
                    $this->cache[$id] = $this->fullGet($parentClass, $name, $src, $doc);
                }

                if ($src === self::R_METHOD && empty($this->cache[$id])) {
                    foreach(class_implements($className) as $classInterface) {
                        $interfaceValue = $this->fullGet($classInterface, $name, $src, $doc);
                        if ($interfaceValue) {
                            $this->cache[$id]= $interfaceValue;
                            break;
                        }
                    }
                }
            }
        }

        return $this->cache[$id];
    }

    public function get($className, $name, $src, $doc)
    {
        $full = $this->fullGet($className, $name, $src, $doc);
        return $full['value'];
    }
}