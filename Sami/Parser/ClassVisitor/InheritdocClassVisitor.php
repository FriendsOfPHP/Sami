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

            if ('{@inheritdoc}' == strtolower(trim($method->getShortDesc())) || !$method->getDocComment()) {
                $method->setShortDesc($parentMethod->getShortDesc());
                $method->setLongDesc($parentMethod->getLongDesc());
                $method->setExceptions($parentMethod->getRawExceptions());

                $modified = true;
            }
        }

        return $modified;
    }
}
