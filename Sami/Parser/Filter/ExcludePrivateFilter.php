<?php

/*
 * This filter allow all the methods and property except the private ones.
 */

use Sami\Parser\Filter\TrueFilter;
use Sami\Reflection\MethodReflection;
use Sami\Reflection\PropertyReflection;

/**
 * ExcludePrivateFilter
 *
 * @author Gabriele Martini <gabrielemartini1990@gmail.com>
 */
class ExcludePrivateFilter extends TrueFilter
{
    public function acceptMethod(MethodReflection $method)
    {
        return !$method->isPrivate();
    }
    public function acceptProperty(PropertyReflection $property)
    {
        return !$property->isPrivate();
    }
}
