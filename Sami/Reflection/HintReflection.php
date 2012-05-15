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

class HintReflection
{
    protected $name;
    protected $array;

    public function __construct($name, $array)
    {
        $this->name = $name;
        $this->array = $array;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function isClass()
    {
        return $this->name instanceof ClassReflection;
    }

    public function isArray()
    {
        return $this->array;
    }

    public function setArray($boolean)
    {
        $this->array = (Boolean) $boolean;
    }
}
