<?php

namespace Sami\Tests\Parser;

use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use Sami\Parser\DocBlockParser;
use Sami\Parser\Filter\TrueFilter;
use Sami\Parser\ParserContext;
use Sami\Reflection\ClassReflection;

class ParserContextTest extends \PHPUnit_Framework_TestCase
{
    public function testLeaveClassBeforeEnter()
    {
        $filter = new TrueFilter();
        $docBlockParser = new DocBlockParser();
        $prettyPrinter = new PrettyPrinter();

        $context = new ParserContext($filter, $docBlockParser, $prettyPrinter);
        $class = new ClassReflection('C1', 1);

        $context->enterFile(null, null);

        // Leave a class before entering it
        $context->leaveClass();

        // Genuinely enter and leave a class
        $context->enterClass($class);
        $context->leaveClass();

        $classes = $context->leaveFile();

        $this->assertContainsOnlyInstancesOf('Sami\Reflection\ClassReflection', $classes);
    }
}
