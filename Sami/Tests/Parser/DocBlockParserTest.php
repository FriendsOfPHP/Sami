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
use Sami\Parser\DocBlockParser;
use Sami\Parser\Node\DocBlockNode;

class DocBlockParserTest extends TestCase
{
    /**
     * @dataProvider getParseTests
     */
    public function testParse($comment, $expected)
    {
        $parser = new DocBlockParser();

        $this->assertEquals($this->createDocblock($expected), $parser->parse($comment, $this->getContextMock()));
    }

    public function getParseTests()
    {
        return array(
            array('
                /**
                 */
                ',
                array(),
            ),
            array('
                /**
                 * The short desc.
                 */
                ',
                array('shortdesc' => 'The short desc.'),
            ),
            array('/** The short desc. */',
                array('shortdesc' => 'The short desc.'),
            ),
            array('
                /**
                 * The short desc on two
                 * lines.
                 */
                ',
                array('shortdesc' => "The short desc on two\nlines."),
            ),
            array('
                /**
                 * The short desc.
                 *
                 * And a long desc.
                 */
                ',
                array('shortdesc' => 'The short desc.', 'longdesc' => 'And a long desc.'),
            ),
            array('
                /**
                 * The short desc on two
                 * lines.
                 *
                 * And a long desc on
                 * several lines too.
                 *
                 * With another paragraph.
                 */
                ',
                array('shortdesc' => "The short desc on two\nlines.", 'longdesc' => "And a long desc on\nseveral lines too.\n\nWith another paragraph."),
            ),
            array('
                /**
                 * The short desc with a @tag embedded. And the short desc continues after dot on same line.
                 */
                ',
                array('shortdesc' => 'The short desc with a @tag embedded. And the short desc continues after dot on same line.'),
            ),
            array('
                /**
                 * @see http://symfony.com/ This is a link description.
                 */
                ',
                array('tags' => array('see' => array(array('http://symfony.com/ This is a link description.', 'http://symfony.com/', 'This is a link description.')))),
            ),
            array('
                /**
                 * @author fabien@example.com
                 */
                ',
                array('tags' => array('author' => 'fabien@example.com')),
            ),
            array('
                /**
                 * @author Fabien <fabien@example.com>
                 * @author Thomas <thomas@example.com>
                 */
                ',
                array('tags' => array('author' => array('Fabien <fabien@example.com>', 'Thomas <thomas@example.com>'))),
            ),
            array('
                /**
                 * @var SingleClass|\MultipleClass[] Property Description
                 */
                ',
                array(
                    'tags' => array(
                        'var' => array( // Array from found tags.
                            array( // First found tag.
                                array(array('\SingleClass', false), array('\MultipleClass', true)), // Array from data types.
                                'Property Description',
                            ),
                        ),
                    ),
                ),
            ),
            array('
                /**
                 * @param SingleClass|\MultipleClass[] $paramName Param Description
                 */
                ',
                array(
                    'tags' => array(
                        'param' => array( // Array from found tags.
                            array( // First found tag.
                                array(array('\SingleClass', false), array('\MultipleClass', true)), // Array from data types.
                                'paramName',
                                'Param Description',
                            ),
                        ),
                    ),
                ),
            ),
            array('
                /**
                 * @throw SingleClass1 Exception Description One
                 * @throws SingleClass2 Exception Description Two
                 */
                ',
                array(
                    'tags' => array(
                        'throw' => array( // Array from found tags.
                            array( // First found tag.
                                '\SingleClass1',
                                'Exception Description One',
                            ),
                        ),
                        'throws' => array( // Array from found tags.
                            array( // Second found tag.
                                '\SingleClass2',
                                'Exception Description Two',
                            ),
                        ),
                    ),
                ),
            ),
            array('
                /**
                 * @return SingleClass|\MultipleClass[] Return Description
                 */
                ',
                array(
                    'tags' => array(
                        'return' => array( // Array from found tags.
                            array( // First found tag.
                                array(array('\SingleClass', false), array('\MultipleClass', true)), // Array from data types.
                                'Return Description',
                            ),
                        ),
                    ),
                ),
            ),
            array('
               /**
                * @author Author Name
                * @covers SomeClass::SomeMethod
                * @deprecated 1.0 for ever
                * @todo Something needs to be done
                * @example Description
                * @link http://www.google.com
                * @method void setInteger(integer $integer)
                * @property-read string $myProperty
                * @property string $myProperty
                * @property-write string $myProperty
                * @see SomeClass::SomeMethod This is a description.
                * @since 1.0.1 First time this was introduced.
                * @source 2 1 Check that ensures lazy counting.
                * @uses MyClass::$items to retrieve the count from.
                * @version 1.0.1
                * @unknown any text
                */
               ',
                array(
                    'tags' => array(
                        'author' => array('Author Name'),
                        'covers' => array('SomeClass::SomeMethod'),
                        'deprecated' => array('1.0 for ever'),
                        'todo' => array('Something needs to be done'),
                        'example' => array('Description'),
                        'link' => array('http://www.google.com'),
                        'method' => array('void setInteger(integer $integer)'),
                        'property-read' => array(   // array of all properties
                            array(                  // array of one property
                                array(              // array of all typehints of one property
                                    array(          // array of one typehint
                                        'string',   // the typehint
                                        null,       // whether or not the typehint is an array
                                    ),
                                ),
                                'myProperty',       // property name
                                '',                  // property description
                            ),
                        ),
                        'property' => array(        // see above
                            array(
                                array(
                                    array(
                                        'string',
                                        null,
                                    ),
                                ),
                                'myProperty',
                                '',
                            ),
                        ),
                        'property-write' => array(  // see above
                            array(
                                array(
                                    array(
                                        'string',
                                        null,
                                    ),
                                ),
                                'myProperty',
                                '',
                            ),
                        ),
                        'see' => array(array('SomeClass::SomeMethod This is a description.', 'SomeClass::SomeMethod', 'This is a description.')),
                        'since' => array('1.0.1 First time this was introduced.'),
                        'source' => array('2 1 Check that ensures lazy counting.'),
                        'uses' => array('MyClass::$items to retrieve the count from.'),
                        'version' => array('1.0.1'),
                        'unknown' => array('any text'),
                    ),
                ),
           ),
        );
    }

    private function createDocblock(array $elements)
    {
        $docblock = new DocBlockNode();
        foreach ($elements as $key => $value) {
            switch ($key) {
                case 'tags':
                    foreach ($value as $tag => $value) {
                        if (!is_array($value)) {
                            $value = array($value);
                        }
                        foreach ($value as $v) {
                            $docblock->addTag($tag, $v);
                        }
                    }
                    break;
                default:
                    $method = 'set'.$key;
                    $docblock->$method($value);
            }
        }

        return $docblock;
    }

    private function getContextMock()
    {
        $contextMock = $this->getMockBuilder('Sami\Parser\ParserContext')->disableOriginalConstructor()->getMock();
        $contextMock->expects($this->once())->method('getNamespace')->will($this->returnValue(''));
        $contextMock->expects($this->once())->method('getAliases')->will($this->returnValue(array()));

        return $contextMock;
    }
}
