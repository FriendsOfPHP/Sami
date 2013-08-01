<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Parser\ClassVisitor;

use Sami\Parser\Node\DocBlockNode;
use Sami\Reflection\ClassReflection;
use Sami\Parser\ClassVisitorInterface;

class InheritdocClassVisitor implements ClassVisitorInterface
{
    public function visit(ClassReflection $class)
    {
        $modified = false;
        foreach ($class->getMethods() as $name => $method) {
            if (!$parentMethod = $class->getParentMethod($name)) {
                continue;
            }

            foreach ($method->getParameters() as $name => $parameter) {
                if (!$parentParameter = $parentMethod->getParameter($name)) {
                    continue;
                }

                if (!$parameter->getShortDesc()) {
                    $parameter->setShortDesc($parentParameter->getShortDesc());
                    $modified = true;
                }

                if (!$parameter->getHint()) {
                    // FIXME: should test for a raw hint from tags, not the one from PHP itself
                    $parameter->setHint($parentParameter->getRawHint());
                    $modified = true;
                }
            }

            if (!$method->getHint()) {
                $method->setHint($parentMethod->getRawHint());
                $modified = true;
            }

            if (!$method->getHintDesc()) {
                $method->setHintDesc($parentMethod->getHintDesc());
                $modified = true;
            }

            if ($this->stringIsInheritdoc($method->getShortDesc())) {
                $inheritedModified = $this->findInherited($method, $class);
                if (!$modified) $modified = $inheritedModified;
            }
        }
        return $modified;
    }

    protected function stringIsInheritdoc($string)
    {
        return '{@inheritdoc}' == strtolower(trim($string));
    }

    protected function findInherited($method, $class)
    {
        $parents = $class->getParent(true);
        $shortDesc = $method->getShortDesc();
        $parents = array_merge($parents, $class->getInterfaces(true));

        foreach ($parents as $parent) {
            if (!$parentMethod = $parent->getMethod($method->getName())) {
                continue;
            }
            foreach ($parent->getMethods(true) as $name => $parentMethod) {
                if ($name != $method->getName()) {
                    continue;
                }

                if ($this->stringIsInheritdoc($parentMethod->getShortDesc())) {
                    $this->findInherited($parentMethod, $parent);
                }

                $method->setShortDesc($parentMethod->getShortDesc());
                $method->setLongDesc($parentMethod->getLongDesc());
                $method->setExceptions($parentMethod->getRawExceptions());
                if ($shortDesc != $method->getShortDesc()) {
                    return true;
                }
            }
        }

        return false;
    }
}
