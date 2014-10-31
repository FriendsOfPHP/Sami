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

class PropertyReflection extends Reflection
{
    protected $class;
    protected $modifiers;
    protected $default;
    protected $errors = array();

    public function __toString()
    {
        return $this->class.'::$'.$this->name;
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

    public function isFinal()
    {
        return self::MODIFIER_FINAL === (self::MODIFIER_FINAL & $this->modifiers);
    }

    public function setDefault($default)
    {
        $this->default = $default;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setClass(ClassReflection $class)
    {
        $this->class = $class;
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
            'default'    => $this->default,
            'errors'     => $this->errors,
        );
    }

    static public function fromArray(Project $project, $array)
    {
        $property = new self($array['name'], $array['line']);
        $property->shortDesc = $array['short_desc'];
        $property->longDesc  = $array['long_desc'];
        $property->hint      = $array['hint'];
        $property->hintDesc  = $array['hint_desc'];
        $property->tags      = $array['tags'];
        $property->modifiers = $array['modifiers'];
        $property->default   = $array['default'];
        $property->errors    = $array['errors'];

        return $property;
    }
}
