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

use Sami\Parser\ClassVisitor\MethodClassVisitor;

class MethodClassVisitorTest extends \PHPUnit_Framework_TestCase
{
    public function testAddsMethods()
    {
        $class = $this->getMock('Sami\Reflection\ClassReflection', array('getTags'), array('Mock', 1));
        $property = array(
            explode(' ', 'string askQuestion() Ask 3 questions')
        );
        $class->expects($this->any())
                ->method('getTags')
                ->with($this->equalTo('method'))
                ->will($this->returnValue($property));

        $visitor = new MethodClassVisitor();
        $visitor->visit($class);

        $this->assertTrue(array_key_exists('askQuestion', $class->getMethods()));
    }
}
