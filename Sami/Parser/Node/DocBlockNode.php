<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Parser\Node;

class DocBlockNode
{
    protected $shortDesc;
    protected $longDesc;
    protected $tags = array();
    protected $errors = array();

    public function addTag($key, $value)
    {
        $this->tags[$key][] = $value;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function getOtherTags()
    {
        $tags = $this->tags;
        unset($tags['param'], $tags['return'], $tags['var'], $tags['throws']);

        foreach ($tags as $name => $values) {
            foreach ($values as $i => $value) {
                $tags[$name][$i] = explode(' ', $value);
            }
        }

        return $tags;
    }

    public function getTag($key)
    {
        return isset($this->tags[$key]) ? $this->tags[$key] : array();
    }

    public function getShortDesc()
    {
        return $this->shortDesc;
    }

    public function getLongDesc()
    {
        return $this->longDesc;
    }

    public function setShortDesc($shortDesc)
    {
        $this->shortDesc = $shortDesc;
    }

    public function setLongDesc($longDesc)
    {
        $this->longDesc = $longDesc;
    }

    public function getDesc()
    {
        return $this->shortDesc."\n\n".$this->longDesc;
    }

    public function addError($error)
    {
        $this->errors[] = $error;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
