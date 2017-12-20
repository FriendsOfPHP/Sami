<?php

/*
 * This file is part of the Sami library.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Tests\Parser;

use PHPUnit\Framework\TestCase;
use Sami\Parser\ClassTraverser;
use Sami\Project;
use Sami\Reflection\ClassReflection;
use Sami\Store\ArrayStore;

class ClassTraverserTest extends TestCase
{
    /**
     * @dataProvider getTraverseOrderClasses
     */
    public function testTraverseOrder($interfaceName, $parentName, $className, $class, $parent, $interface)
    {
        $store = new ArrayStore();
        $store->setClasses(array($class, $parent, $interface));

        $project = new Project($store);

        $visitor = $this->getMockBuilder('Sami\Parser\ClassVisitorInterface')->getMock();
        $visitor->expects($this->at(0))->method('visit')->with($project->loadClass($interfaceName));
        $visitor->expects($this->at(1))->method('visit')->with($project->loadClass($parentName));
        $visitor->expects($this->at(2))->method('visit')->with($project->loadClass($className));

        $traverser = new ClassTraverser();
        $traverser->addVisitor($visitor);

        $traverser->traverse($project);
    }

    /**
     * @dataProvider getNamespaceDetectionClasses
     */
    public function testNamespaceDetection($interfaceName, $parentName, $className, $class, $parent, $interface, $expectedNamespaces)
    {
        $store = new ArrayStore();
        $store->setClasses(array($class, $parent, $interface));

        $project = new Project($store);

        $project->loadClass($interfaceName);
        $project->loadClass($parentName);
        $project->loadClass($className);

        $this->assertEquals($expectedNamespaces, $project->getNamespaces());
    }

    public function getTraverseOrderClasses()
    {
        // as classes are sorted by name in Project, we try all combinaison
        // by giving different names to the classes
        return array(
            $this->createClasses('C1', 'C2', 'C3'),
            $this->createClasses('C1', 'C3', 'C2'),
            $this->createClasses('C2', 'C1', 'C3'),
            $this->createClasses('C2', 'C3', 'C1'),
            $this->createClasses('C3', 'C1', 'C2'),
            $this->createClasses('C3', 'C2', 'C1'),
        );
    }

    public function getNamespaceDetectionClasses()
    {
        return array(
            array_merge($this->createClasses('C1', 'C2', 'C3'), array(array(''))),
            array_merge($this->createClasses('C1', 'C2', 'C3', 'Ns1'), array(array('', 'Ns1'))),
            array_merge($this->createClasses('C1', 'C2', 'C3', "Ns1\Ns2\Ns3"), array(array('', 'Ns1', "Ns1\Ns2", "Ns1\Ns2\Ns3"))),
        );
    }

    protected function createClasses($interfaceName, $parentName, $className, $namespaceName = null)
    {
        $interface = new ClassReflection($interfaceName, 1);

        $parent = new ClassReflection($parentName, 1);
        $parent->addInterface($interfaceName);

        $class = new ClassReflection($className, 1);
        $class->setParent($parentName);
        if ($namespaceName) {
            $class->setNamespace($namespaceName);
        }

        return array($interfaceName, $parentName, $className, $class, $parent, $interface);
    }
}
