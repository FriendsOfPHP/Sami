<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Parser\Filter;

use Sami\Reflection\ClassReflection;
use Sami\Reflection\MethodReflection;

class SymfonyFilter extends DefaultFilter
{
    public function acceptClass(ClassReflection $class)
    {
        return $class->getDocBlock()->getTag('api');
    }

    public function acceptMethod(MethodReflection $method)
    {
        return parent::acceptMethod($method) && $method->getDocBlock()->getTag('api');
    }
}
