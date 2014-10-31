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

use Sami\Project;

class ClassTraverser
{
    protected $visitors;

    public function __construct(array $visitors = array())
    {
        $this->visitors = array();
        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }
    }

    public function addVisitor(ClassVisitorInterface $visitor)
    {
        $this->visitors[] = $visitor;
    }

    public function traverse(Project $project)
    {
        // parent classes/interfaces are visited before their "children"
        $classes = $project->getProjectClasses();
        $modified = array();
        while ($class = array_shift($classes)) {
            // re-push the class at the end if parent class/interfaces have not been visited yet
            if (($parent = $class->getParent()) && isset($classes[$parent->getName()])) {
                $classes[$class->getName()] = $class;

                continue;
            }

            foreach ($class->getInterfaces() as $interface) {
                if (isset($classes[$interface->getName()])) {
                    $classes[$class->getName()] = $class;

                    continue 2;
                }
            }

            $isModified = false;
            foreach ($this->visitors as $visitor) {
                $isModified = $visitor->visit($class) || $isModified;
            }

            if ($isModified) {
                $modified[] = $class;
            }
        }

        return $modified;
    }
}
