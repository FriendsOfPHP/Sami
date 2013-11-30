<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Store;

use Sami\Reflection\ClassReflection;
use Sami\Project;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Stores classes in-memory.
 *
 * Mainly useful for unit tests.
 */
class ArrayStore implements StoreInterface
{
    private $classes = array();

    public function setClasses($classes)
    {
        foreach ($classes as $class) {
            $this->classes[$class->getName()] = $class;
        }
    }

    public function readClass(Project $project, $name)
    {
        if (!isset($this->classes[$name])) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $name));
        }

        return $this->classes[$name];
    }

    public function removeClass(Project $project, $name)
    {
        if (!isset($this->classes[$name])) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $name));
        }

        unset($this->classes[$name]);
    }

    public function writeClass(Project $project, ClassReflection $class)
    {
        $this->classes[$class->getName()] = $class;
    }

    public function readProject(Project $project)
    {
        return $this->classes;
    }

    public function flushProject(Project $project)
    {
        $this->classes = array();
    }
}
