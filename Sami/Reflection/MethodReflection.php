<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Reflection;

use Sami\Project;

class MethodReflection extends Reflection
{
    protected $class;
    protected $parameters = array();
    protected $byRef;
    protected $modifiers;
    protected $exceptions = array();
    protected $errors = array();

    public function __toString()
    {
        return $this->class.'::'.$this->name;
    }

    public function setByRef($boolean)
    {
        $this->byRef = $boolean;
    }

    public function isByRef()
    {
        return $this->byRef;
    }

    public function setModifiers($modifiers)
    {
        $this->modifiers = $modifiers;
    }

    public function isPublic()
    {
        return self::MODIFIER_PUBLIC === (self::MODIFIER_PUBLIC & $this->modifiers);
    }

    public function isProtected()
    {
        return self::MODIFIER_PROTECTED === (self::MODIFIER_PROTECTED & $this->modifiers);
    }

    public function isPrivate()
    {
        return self::MODIFIER_PRIVATE === (self::MODIFIER_PRIVATE & $this->modifiers);
    }

    public function isStatic()
    {
        return self::MODIFIER_STATIC === (self::MODIFIER_STATIC & $this->modifiers);
    }

    public function isAbstract()
    {
        return self::MODIFIER_ABSTRACT === (self::MODIFIER_ABSTRACT & $this->modifiers);
    }

    public function isFinal()
    {
        return self::MODIFIER_FINAL === (self::MODIFIER_FINAL & $this->modifiers);
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setClass(ClassReflection $class)
    {
        $this->class = $class;
    }

    public function addParameter(ParameterReflection $parameter)
    {
        $this->parameters[$parameter->getName()] = $parameter;
        $parameter->setMethod($this);
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getParameter($name)
    {
        if (ctype_digit((string) $name)) {
            $tmp = array_values($this->parameters);

            return isset($tmp[$name]) ? $tmp[$name] : null;
        }

        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /*
     * Can be any iterator (so that we can lazy-load the parameters)
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    public function setExceptions($exceptions)
    {
        $this->exceptions = $exceptions;
    }

    public function getExceptions()
    {
        $exceptions = array();
        foreach ($this->exceptions as $exception) {
            $exception[0] = $this->class->getProject()->getClass($exception[0]);
            $exceptions[] = $exception;
        }

        return $exceptions;
    }

    public function getRawExceptions()
    {
        return $this->exceptions;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

    public function toArray()
    {
        return array(
            'name'       => $this->name,
            'line'       => $this->line,
            'short_desc' => $this->shortDesc,
            'long_desc'  => $this->longDesc,
            'hint'       => $this->hint,
            'hint_desc'  => $this->hintDesc,
            'tags'       => $this->tags,
            'modifiers'  => $this->modifiers,
            'is_by_ref'  => $this->byRef,
            'exceptions' => $this->exceptions,
            'errors'     => $this->errors,
            'parameters' => array_map(function ($parameter) { return $parameter->toArray(); }, $this->parameters),
        );
    }

    static public function fromArray(Project $project, $array)
    {
        $method = new self($array['name'], $array['line']);
        $method->shortDesc  = $array['short_desc'];
        $method->longDesc   = $array['long_desc'];
        $method->hint       = $array['hint'];
        $method->hintDesc   = $array['hint_desc'];
        $method->tags       = $array['tags'];
        $method->modifiers  = $array['modifiers'];
        $method->byRef      = $array['is_by_ref'];
        $method->exceptions = $array['exceptions'];
        $method->errors = $array['errors'];

        foreach ($array['parameters'] as $parameter) {
            $parameter = ParameterReflection::fromArray($project, $parameter);
            $method->addParameter($parameter);
        }

        return $method;
    }
}
