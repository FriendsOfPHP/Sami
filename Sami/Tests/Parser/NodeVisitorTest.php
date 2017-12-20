<?php

namespace Sami\Tests\Parser;

use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Name\Relative;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;
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
class NodeVisitorTest extends TestCase
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

        $this->assertCount(count($expectedHints), $reflMethod->getParameters());
        foreach ($reflMethod->getParameters() as $paramKey => $parameter) {
            /* @var $parameter ParameterReflection */
            $this->assertArrayHasKey($paramKey, $expectedHints);
            $hint = $parameter->getHint();
            $this->assertCount(count($expectedHints[$paramKey]), $hint);
            foreach ($expectedHints[$paramKey] as $hintKey => $hintVal) {
                $this->assertEquals($hintVal, (string) $hint[$hintKey]);
            }
        }
    }

    /**
     * @return array
     */
    public function getMethodTypehints()
    {
        return array(
            'primitive' => $this->getMethodTypehintsPrimiteveParameters(),
            'class' => $this->getMethodTypehintsClassParameters(),
            'subnamespacedclass' => $this->getMethodTypehintsSubNamespacedClassParameters(),
            'docblockclass' => $this->getMethodTypehintsDocblockClassParameters(),
            'docblockmixedclass' => $this->getMethodTypehintsDocblockMixedClassParameters(),
        );
    }

    private function getMethodTypehintsPrimiteveParameters()
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
                'param1' => array('int'),
                'param2' => array('string'),
            ),
        );
    }

    private function getMethodTypehintsClassParameters()
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
                'param1' => array('Test\Class'),
            ),
        );
    }

    private function getMethodTypehintsSubNamespacedClassParameters()
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
                'param1' => array('Sub\Class'),
            ),
        );
    }

    private function getMethodTypehintsDocblockClassParameters()
    {
        $classReflection = new ClassReflection('C1', 1);
        $paramClassReflection = new ClassReflection("Test\Class", 1);
        $method = new ClassMethod('testMethod', array(
            'params' => array(
                new Param('param1'),
            ),
        ));
        $method->setDocComment(new \PhpParser\Comment\Doc('/** @param Test\\Class $param1 */'));

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
                'param1' => array('Test\Class'),
            ),
        );
    }

    private function getMethodTypehintsDocblockMixedClassParameters()
    {
        $classReflection = new ClassReflection('C1', 1);
        $paramClassReflection = new ClassReflection("Test\Class", 1);
        $method = new ClassMethod('testMethod', array(
            'params' => array(
                new Param('param1'),
            ),
        ));
        $method->setDocComment(new \PhpParser\Comment\Doc('/** @param Test\\Class|string $param1 */'));

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
                'param1' => array('Test\Class', 'string'),
            ),
        );
    }
}
