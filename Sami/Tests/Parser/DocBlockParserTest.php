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

use Sami\Parser\DocBlockParser;
use Sami\Parser\Node\DocBlockNode;

class DocBlockParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getParseTests
     */
    public function testParse($comment, $expected)
    {
        $parser = new DocBlockParser();

        $this->assertEquals($this->createDocblock($expected), $parser->parse($comment));
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
                array('shortdesc' => 'The short desc on two lines.'),
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
                array('shortdesc' => 'The short desc on two lines.', 'longdesc' => "And a long desc on\nseveral lines too.\n\nWith another paragraph."),
            ),
            array('
                /**
                 * The short desc with a @tag embedded. And the long desc with a @tag embedded too.
                 */
                ',
                array('shortdesc' => 'The short desc with a @tag embedded.', 'longdesc' => 'And the long desc with a @tag embedded too.'),
            ),
            array('
                /**
                 * @see http://symfony.com/
                 */
                ',
                array('tags' => array('see' => 'http://symfony.com/')),
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
}
