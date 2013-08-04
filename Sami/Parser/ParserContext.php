<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Parser;

use Sami\Reflection\ClassReflection;
use Sami\Parser\Filter\FilterInterface;
use Sami\Parser\DocBlockParser;

class ParserContext
{
    protected $filter;
    protected $docBlockParser;
    protected $prettyPrinter;
    protected $errors;
    protected $namespace;
    protected $aliases;
    protected $class;
    protected $file;
    protected $hash;
    protected $classes;

    public function __construct(FilterInterface $filter, DocBlockParser $docBlockParser, $prettyPrinter)
    {
        $this->filter = $filter;
        $this->docBlockParser = $docBlockParser;
        $this->prettyPrinter = $prettyPrinter;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function getDocBlockParser()
    {
        return $this->docBlockParser;
    }

    public function getPrettyPrinter()
    {
        return $this->prettyPrinter;
    }

    public function addAlias($alias, $name)
    {
        $this->aliases[$alias] = $name;
    }

    public function getAliases()
    {
        return $this->aliases;
    }

    public function enterFile($file, $hash)
    {
        $this->file = $file;
        $this->hash = $hash;
        $this->errors = array();
        $this->classes = array();
    }

    public function leaveFile()
    {
        $this->hash = null;
        $this->file = null;
        $this->errors = array();

        return $this->classes;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function addErrors($name, $line, array $errors)
    {
        foreach ($errors as $error) {
            $this->addError($name, $line, $error);
        }
    }

    public function addError($name, $line, $error)
    {
        $this->errors[] = sprintf('An error occurred while parsing "%s" line "%d": %s', $name, $line, $error);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function enterClass(ClassReflection $class)
    {
        $this->class = $class;
    }

    public function leaveClass()
    {
        $this->classes[] = $this->class;
        $this->class = null;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function enterNamespace($namespace)
    {
        $this->namespace = $namespace;
        $this->aliases = array();
    }

    public function leaveNamespace()
    {
        $this->namespace = null;
        $this->aliases = array();
    }

    public function getNamespace()
    {
        return $this->namespace;
    }
}
