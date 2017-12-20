<?php

namespace Sami\Tests;

use PHPUnit\Framework\TestCase;
use Sami\Project;
use Sami\Reflection\ClassReflection;
use Sami\Store\ArrayStore;
use Sami\Tree;

/**
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class TreeTest extends TestCase
{
    public function testNamespaces()
    {
        $class1 = new ClassReflection('C1', 1);
        $class2 = new ClassReflection('C2', 1);
        $class3 = new ClassReflection('C3', 1);
        $class2->setNamespace('C21');
        $class3->setNamespace('C31\C32');

        $store = new ArrayStore();
        $store->setClasses(array($class1, $class2, $class3));

        $project = new Project($store);
        $project->loadClass($class1);
        $project->loadClass($class2);
        $project->loadClass($class3);

        $tree = new Tree();

        $generated = $tree->getTree($project);
        $this->assertCount(3, $generated);

        $this->assertEquals('[Global Namespace]', $generated[0][0]);
        $this->assertEquals('', $generated[0][1]);

        $this->assertEquals('C21', $generated[1][0]);
        $this->assertEquals('C21', $generated[1][1]);

        $this->assertEquals('C31', $generated[2][0]);
        $this->assertEquals('C31', $generated[2][1]);

        $this->assertCount(3, $generated[2]);
        $this->assertCount(1, $generated[2][2]);
        $this->assertEquals('C32', $generated[2][2][0][0]);
        $this->assertEquals("C31\C32", $generated[2][2][0][1]);
    }
}
