<?php

namespace Sami\Tests\Reflection;

use PHPUnit\Framework\TestCase;
use Sami\Reflection\ClassReflection;

class ClassReflectionTest extends TestCase
{
    public function testIsPhpClass()
    {
        // an internal class
        $class = new ClassReflection('stdClass', 1);
        $this->assertTrue($class->isPhpClass());

        // an internal class uppercased
        $class = new ClassReflection('STDCLASS', 1);
        $this->assertTrue($class->isPhpClass());

        // a class that does not exist
        $class = new ClassReflection('FooBarDoesNotExistAsAClass', 1);
        $this->assertFalse($class->isPhpClass());

        // a class that is already loaded
        $class = new ClassReflection('Sami\Tests\Reflection\ClassReflectionTest', 1);
        $this->assertFalse($class->isPhpClass());

        // a class that exists but is not already loaded
        $class = new ClassReflection('Sami\Console\Application', 1);
        $this->assertFalse($class->isPhpClass());
    }
}
