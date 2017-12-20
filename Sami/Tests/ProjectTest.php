<?php

namespace Sami\Tests;

use PHPUnit\Framework\TestCase;
use Sami\Project;
use Sami\Reflection\ClassReflection;
use Sami\Store\ArrayStore;
use Sami\Version\Version;

class ProjectTest extends TestCase
{
    public function testSwitchVersion()
    {
        // Dummy store and classes
        $class1 = new ClassReflection('C1', 1);
        $class2 = new ClassReflection('C21\\C2', 1);
        $class3 = new ClassReflection('C31\\C32\\C3', 1);
        $class2->setNamespace('C21');
        $class3->setNamespace('C31\\C32');
        $store = new ArrayStore();
        $store->setClasses(array($class1, $class2, $class3));
        $project = new Project($store);

        // Load version 1
        $project->switchVersion(new Version('1'), null, true);

        $project->loadClass('C1');
        $project->loadClass('C21\\C2');

        $this->assertEquals(
            array(
                'C1' => $class1,
                'C21\\C2' => $class2,
            ),
            $project->getProjectClasses()
        );

        // Load version 2
        $project->switchVersion(new Version('2'), null, true);
        $project->loadClass($class2);
        $project->loadClass($class3);

        $this->assertEquals(
            array(
                'C21\\C2' => $class2,
                'C31\\C32\\C3' => $class3,
             ),
            $project->getProjectClasses()
        );
    }
}
