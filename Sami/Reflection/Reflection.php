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

abstract class Reflection
{
    const MODIFIER_PUBLIC    =  1;
    const MODIFIER_PROTECTED =  2;
    const MODIFIER_PRIVATE   =  4;
    const MODIFIER_STATIC    =  8;
    const MODIFIER_ABSTRACT  = 16;
    const MODIFIER_FINAL     = 32;

    protected $name;
    protected $line;
    protected $shortDesc;
    protected $longDesc;
    protected $hint;
    protected $hintDesc;
    protected $tags;
    protected $docComment;

    public function __construct($name, $line)
    {
        $this->name = $name;
        $this->line = $line;
        $this->tags = array();
    }

    abstract public function getClass();

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function setLine($line)
    {
        $this->line = $line;
    }

    public function getShortDesc()
    {
        return $this->shortDesc;
    }

    public function setShortDesc($shortDesc)
    {
        $this->shortDesc = $shortDesc;
    }

    public function getLongDesc()
    {
        return $this->longDesc;
    }

    public function setLongDesc($longDesc)
    {
        $this->longDesc = $longDesc;
    }

    public function getHint()
    {
        if (!$this->hint) {
            return array();
        }

        $hints = array();
        $project = $this->getClass()->getProject();
        foreach ($this->hint as $hint) {
            $hints[] = new HintReflection(Project::isPhpTypeHint($hint[0]) ? $hint[0] : $project->getClass($hint[0]), $hint[1]);
        }

        return $hints;
    }

    public function getHintAsString()
    {
        $str = array();
        foreach ($this->getHint() as $hint) {
            $str[] = ($hint->isClass() ? $hint->getName()->getShortName() : $hint->getName()).($hint->isArray() ? '[]' : '');
        }

        return implode('|', $str);
    }

    public function hasHint()
    {
        return $this->hint ? true : false;
    }

    public function setHint($hint)
    {
        $this->hint = $hint;
    }

    public function getRawHint()
    {
        return $this->hint;
    }

    public function setHintDesc($desc)
    {
        $this->hintDesc = $desc;
    }

    public function getHintDesc()
    {
        return $this->hintDesc;
    }

    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    public function getTags($name)
    {
        return isset($this->tags[$name]) ? $this->tags[$name] : array();
    }

    // not serialized as it is only useful when parsing
    public function setDocComment($comment)
    {
        $this->docComment = $comment;
    }

    public function getDocComment()
    {
        return $this->docComment;
    }
}
