<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Parser\ClassVisitor;

use Sami\Reflection\ClassReflection;
use Sami\Parser\ClassVisitorInterface;
use Sami\Reflection\MethodReflection;
use Sami\Reflection\ParameterReflection;

/**
 * Looks for @method tags on classes in the format of:
 * @method [return type] [name]([type] [parameter], [...]) [<description>]
 */
class MethodClassVisitor implements ClassVisitorInterface
{
    public function visit(ClassReflection $class)
    {
        $modified = false;

        $methods = $class->getTags('method');
        if (!empty($methods)) {
            foreach ($methods as $methodTag) {
                if ($this->injectMethod($class, implode(' ', $methodTag))) {
                    $modified = true;
                }
            }
        }

        return $modified;
    }

    /**
     * Parse the parts of an @method tag into an associative array
     *
     * Original @method parsing by https://github.com/phpDocumentor/ReflectionDocBlock/blob/master/src/phpDocumentor/Reflection/DocBlock/Tag/MethodTag.php
     *
     * @param string $tag Method tag contents
     *
     * @return array
     */
    protected function parseMethod($tag)
    {
        // Account for default array syntax
        $tag = str_replace('array()', 'array', $tag);

        $matches = array();
        // 1. none or more whitespace
        // 2. optionally a word with underscores followed by whitespace : as
        //    type for the return value
        // 3. then optionally a word with underscores followed by () and
        //    whitespace : as method name as used by phpDocumentor
        // 4. then a word with underscores, followed by ( and any character
        //    until a ) and whitespace : as method name with signature
        // 5. any remaining text : as description
        $pattern = '/^[\s]*(?P<hint>([\w\|_\\\\]+)[\s]+)?(?:[\w_]+\(\)[\s]+)?(?P<method>[\w\|_\\\\]+)\((?P<args>[^\)]*)\)[\s]*(?P<description>.*)/u';
        if (!preg_match($pattern, $tag, $matches)) {
            return false;
        }

        // Parse arguments
        $args = array();
        if (isset($matches['args'])) {
            foreach (explode(',', $matches['args']) as $arg) {
                $parts = array();
                if (preg_match('/^[\s]*(?P<hint>([\w\|_\\\\]+)[\s]+)*[\s]*\$(?P<name>[\w\|_\\\\]+)?(?:[\s]*=[\s]*)?(?P<default>.*)/', $arg, $parts)) {
                    // Fix array default values
                    if ($parts['default'] == 'array') {
                        $parts['default'] = 'array()';
                    }
                    $args[$parts['name']] = array(
                        'hint'    => $parts['hint'],
                        'name'    => $parts['name'],
                        'default' => $parts['default']
                    );
                }
            }
        }

        return array(
            'hint'        => trim($matches['hint']),
            'name'        => $matches['method'],
            'args'        => $args,
            'description' => $matches['description']
        );
    }

    /**
     * Adds a new method to the class using an array of tokens
     *
     * @param ClassReflection $class     Class reflection
     * @param string          $methodTag Method tag contents
     *
     * @return bool
     */
    protected function injectMethod(ClassReflection $class, $methodTag)
    {
        $data = $this->parseMethod($methodTag);

        // Bail if the method format is invalid
        if (!$data) {
            return false;
        }

        $method = new MethodReflection($data['name'], $class->getLine());
        $method->setDocComment($data['description']);
        $method->setShortDesc($data['description']);

        if ($data['hint']) {
            $method->setHint(array(array($data['hint'], null)));
        }

        // Add arguments to the method
        foreach ($data['args'] as $name => $arg) {
            $param = new ParameterReflection($name, $class->getLine());
            if (!empty($arg['hint'])) {
                $param->setHint(array(array($arg['hint'], null)));
            }
            if (!empty($arg['default'])) {
                $param->setDefault($arg['default']);
            }
            $method->addParameter($param);
        }

        $class->addMethod($method);

        return true;
    }
}
