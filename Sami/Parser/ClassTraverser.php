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
        $modified = new \SplObjectStorage();
        while ($class = array_shift($classes)) {
            // re-push the class at the end if parent class/interfaces have not been visited yet
            if (($parent = $class->getParent()) && isset($classes[$parent->getName()])) {
                $classes[$class->getName()] = $class;

                continue;
            }

            foreach ($interfaces = $class->getInterfaces() as $interface) {
                if (isset($classes[$interface->getName()])) {
                    $classes[$class->getName()] = $class;

                    continue 2;
                }
            }

            // only visits classes not coming from the cache
            // and for which parent/interfaces also come from the cache
            $visit = !$class->isFromCache() || ($parent && !$parent->isFromCache());
            foreach ($interfaces as $interface) {
                if (!$interface->isFromCache()) {
                    $visit = true;

                    break;
                }
            }

            if (!$visit) {
                continue;
            }

            $isModified = false;
            foreach ($this->visitors as $visitor) {
                $isModified = $visitor->visit($class) || $isModified;
            }

            if ($isModified) {
                $modified->attach($class);
            }
        }

        return $modified;
    }
}
