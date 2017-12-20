<?php

/*
 * This file is part of the Sami library.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Tests\Parser\ClassVisitor;

use PHPUnit\Framework\TestCase;
use Sami\Parser\ClassVisitor\MethodClassVisitor;

class MethodClassVisitorTest extends TestCase
{
    public function testAddsMethods()
    {
        $class = $this->getMockBuilder('Sami\Reflection\ClassReflection')
            ->setMethods(array('getTags'))
            ->setConstructorArgs(array('Mock', 1))
            ->getMock();
        $property = array(
            explode(' ', 'string askQuestion() Ask 3 questions'),
        );
        $class->expects($this->any())
                ->method('getTags')
                ->with($this->equalTo('method'))
                ->will($this->returnValue($property));

        $visitor = new MethodClassVisitor();
        $visitor->visit($class);

        $this->assertArrayHasKey('askQuestion', $class->getMethods());
    }
}
