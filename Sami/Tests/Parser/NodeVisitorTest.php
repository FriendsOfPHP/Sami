<?php

namespace Sami\Tests\Parser;

use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Name\Relative;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter\Standard;
use Sami\Parser\DocBlockParser;
use Sami\Parser\Filter\TrueFilter;
use Sami\Parser\NodeVisitor;
use Sami\Parser\ParserContext;
use Sami\Project;
use Sami\Reflection\ClassReflection;
use Sami\Reflection\MethodReflection;
use Sami\Reflection\ParameterReflection;
use Sami\Store\ArrayStore;

/**
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class NodeVisitorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getMethodTypehints
     */
    public function testMethodTypehints(ClassReflection $classReflection, ClassMethod $method, array $expectedHints)
    {
        $parserContext = new ParserContext(new TrueFilter(), new DocBlockParser(), new Standard());
        $parserContext->enterClass($classReflection);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeVisitor($parserContext));
        $traverser->traverse(array($method));

        /* @var $method MethodReflection */
        $reflMethod = $classReflection->getMethod($method->name);

        $this->assertEquals(count($expectedHints), count($reflMethod->getParameters()));
        foreach ($reflMethod->getParameters() as $key => $parameter) {
            /* @var $parameter ParameterReflection */
            $this->assertArrayHasKey($key, $expectedHints);
            $hint = $parameter->getHint();
            $this->assertCount(1, $hint);
            $this->assertEquals($expectedHints[$key], (string) $hint[0]);
        }
    }

    /**
     * @return array
     */
    public function getMethodTypehints()
    {
        return array(
            'primitive' => $this->methodTypehintsPrimiteveParameters(),
            'class' => $this->methodTypehintsClassParameters(),
            'subnamespacedclass' => $this->methodTypehintsSubNamespacedClassParameters(),
        );
    }

    private function methodTypehintsPrimiteveParameters()
    {
        $classReflection = new ClassReflection('C1', 1);
        $method = new ClassMethod('testMethod', array(
            'params' => array(
                new Param('param1', null, 'int'),
                new Param('param2', null, 'string'),
            ),
        ));

        $classReflection->setMethods(array($method));
        $store = new ArrayStore();
        $store->setClasses(array($classReflection));

        $project = new Project($store);

        $project->loadClass('C1');

        return array(
            $classReflection,
            $method,
            array(
                'param1' => 'int',
                'param2' => 'string',
            ),
        );
    }

    private function methodTypehintsClassParameters()
    {
        $classReflection = new ClassReflection('C1', 1);
        $paramClassReflection = new ClassReflection("Test\Class", 1);
        $method = new ClassMethod('testMethod', array(
            'params' => array(
                new Param('param1', null, new FullyQualified('Test\\Class')),
            ),
        ));

        $classReflection->setMethods(array($method));
        $store = new ArrayStore();
        $store->setClasses(array($classReflection, $paramClassReflection));

        $project = new Project($store);

        $project->loadClass('C1');
        $project->loadClass('Test\\Class');

        return array(
            $classReflection,
            $method,
            array(
                'param1' => 'Test\Class',
            ),
        );
    }

    private function methodTypehintsSubNamespacedClassParameters()
    {
        $classReflection = new ClassReflection("Test\Class", 1);
        $paramClassReflection = new ClassReflection("Test\Sub\Class", 1);
        $method = new ClassMethod('testMethod', array(
            'params' => array(
                new Param('param1', null, new Relative('Sub\\Class')),
            ),
        ));

        $classReflection->setMethods(array($method));
        $store = new ArrayStore();
        $store->setClasses(array($classReflection, $paramClassReflection));

        $project = new Project($store);

        $project->loadClass('Test\\Class');
        $project->loadClass('Test\\Sub\\Class');

        return array(
            $classReflection,
            $method,
            array(
                'param1' => 'Sub\Class',
            ),
        );
    }
}
