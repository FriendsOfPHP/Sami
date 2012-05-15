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

class ConstantReflection extends Reflection
{
    protected $class;

    public function __toString()
    {
        return $this->class.'::'.$this->name;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setClass(ClassReflection $class)
    {
        $this->class = $class;
    }

    public function toArray()
    {
        return array(
            'name'       => $this->name,
            'line'       => $this->line,
            'short_desc' => $this->shortDesc,
            'long_desc'  => $this->longDesc,
        );
    }

    static public function fromArray(Project $project, $array)
    {
        $constant = new self($array['name'], $array['line']);
        $constant->shortDesc = $array['short_desc'];
        $constant->longDesc  = $array['long_desc'];

        return $constant;
    }
}
