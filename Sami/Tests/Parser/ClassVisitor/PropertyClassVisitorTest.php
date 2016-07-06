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

use Sami\Parser\ClassVisitor\PropertyClassVisitor;

class PropertyClassVisitorTest extends \PHPUnit_Framework_TestCase
{
    public function testAddsProperties()
    {
        $class = $this->getMock('Sami\Reflection\ClassReflection', array('getTags'), array('Mock', 1));

        $property = array(
            array(
                array(),
                'animal',
                'Your favourite animal',
            ),
            array(
                array(
                    'string',
                    null,
                ),
                'color',
                'Your favourite color',
            ),
            array(
                array(),
                'enigma',
                null,
            ),
        );
        $class->expects($this->any())->method('getTags')->with($this->equalTo('property'))->will($this->returnValue($property));

        $context = $this->prophesize('Sami\Parser\ParserContext');

        $visitor = new PropertyClassVisitor($context->reveal());
        $visitor->visit($class);

        $this->assertTrue(array_key_exists('color', $class->getProperties()));
        $this->assertTrue(array_key_exists('animal', $class->getProperties()));
        $this->assertTrue(array_key_exists('enigma', $class->getProperties()));
    }
}
