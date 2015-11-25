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

class ParameterReflection extends Reflection
{
    protected $method;
    protected $byRef;
    protected $modifiers;
    protected $default;
    protected $subParams = [];

    public function __toString()
    {
        return $this->method.'#'.$this->name;
    }

    public function getClass()
    {
        return $this->method->getClass();
    }

    public function setModifiers($modifiers)
    {
        $this->modifiers = $modifiers;
    }

    public function setByRef($boolean)
    {
        $this->byRef = $boolean;
    }

    public function isByRef()
    {
        return $this->byRef;
    }

    public function setDefault($default)
    {
        $this->default = $default;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod(MethodReflection $method)
    {
        $this->method = $method;
    }

    public function setSubParams(array $params)
    {
        foreach ($params as $name => $param) {
            if (isset($param['documented']) && $param['documented'] === false) {
                continue;
            }
            $this->subParams[$name] = new SubParamReflection($param, $name);
        }
    }

    public function getSubParams()
    {
        return $this->subParams;
    }

    public function toArray()
    {
        return array(
            'name'       => $this->name,
            'line'       => $this->line,
            'short_desc' => $this->shortDesc,
            'long_desc'  => $this->longDesc,
            'hint'       => $this->hint,
            'tags'       => $this->tags,
            'modifiers'  => $this->modifiers,
            'default'    => $this->default,
            'is_by_ref'  => $this->byRef,
            'sub_params' => $this->subParams,
        );
    }

    public static function fromArray(Project $project, $array)
    {
        $parameter = new self($array['name'], $array['line'], $array['file']);
        $parameter->shortDesc = $array['short_desc'];
        $parameter->longDesc  = $array['long_desc'];
        $parameter->hint      = $array['hint'];
        $parameter->tags      = $array['tags'];
        $parameter->modifiers = $array['modifiers'];
        $parameter->default   = $array['default'];
        $parameter->byRef     = $array['is_by_ref'];

        return $parameter;
    }

    public function longestPropertyName()
    {
        $count = 0;

        if (empty($this->subParams)) {
            return $count;
        }

        foreach ($this->subParams as $property) {
            $length = strlen($property->getName());
            $count = $length > $count ? $length : $count;
        }

        return $count;
    }
}
