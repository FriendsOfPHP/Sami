<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Parser;

use Sami\Parser\Node\DocBlockNode;

class DocBlockParser
{
    const TAG_REGEX = '@([^ ]+)(?:\s+(.*?))?(?=(\n[ \t]*@|\s*$))';

    protected $position;
    protected $comment;
    protected $lineno;
    protected $cursor;

    public function parse($comment)
    {
        // remove comment characters and normalize
        $comment = preg_replace(array('#^/\*\*\s*#', '#\s*\*/$#', '#^\s*\*#m'), '', trim($comment));
        $comment = "\n".preg_replace('/(\r\n|\r)/', "\n", $comment);

        $this->position = 'desc';
        $this->comment = $comment;
        $this->lineno = 1;
        $this->cursor = 0;

        $doc = new DocBlockNode();
        while ($this->cursor < strlen($this->comment)) {
            switch ($this->position) {
                case 'desc':
                    list($short, $long) = $this->parseDesc();
                    $doc->setShortDesc($short);
                    $doc->setLongDesc($long);
                    break;

                case 'tag':
                    try {
                        list($type, $values) = $this->parseTag();
                        $doc->addTag($type, $values);
                    } catch (\LogicException $e) {
                        $doc->addError($e->getMessage());
                    }
                    break;
            }

            if (preg_match('/\s*$/As', $this->comment, $match, null, $this->cursor)) {
                $this->cursor = strlen($this->comment);
            }
        }

        return $doc;
    }

    protected function parseDesc()
    {
        if (preg_match('/(.*?)(\n[ \t]*'.self::TAG_REGEX.'|$)/As', $this->comment, $match, null, $this->cursor)) {
            $this->move($match[1]);

            $short = trim($match[1]);
            $long = '';

            // short desc ends at the first dot or when \n\n occurs
            if (preg_match('/(.*?)(\.\s|\n\n|$)/s', $short, $match)) {
                $long = trim(substr($short, strlen($match[0])));
                $short = trim($match[0]);
            }

            // remove single lead space
            $short = preg_replace('/^ /', '', $short);
            $long = preg_replace('/^ /m', '', $long);
        }

        $this->position = 'tag';

        return array(str_replace("\n", '', $short), $long);
    }

    protected function parseTag()
    {
        if (preg_match('/\n\s*'.self::TAG_REGEX.'/As', $this->comment, $match, null, $this->cursor)) {
            $this->move($match[0]);

            switch ($type = $match[1]) {
                case 'param':
                    if (!preg_match('/^([^\s]*)\s*(?:(?:\$|\&\$)([^\s]+))?\s*(.*)$/s', $match[2], $m)) {
                        throw new \LogicException(sprintf('Unable to parse "@%s" tag " %s"', $type, $match[2]));

                        return;
                    }

                    return array($type, array($this->parseHint(trim($m[1])), trim($m[2]), $this->normalizeString($m[3])));

                case 'return':
                case 'var':
                    if (!preg_match('/^([^\s]+)\s*(.*)$/s', $match[2], $m)) {
                        throw new \LogicException(sprintf('Unable to parse "@%s" tag "%s"', $type, $match[2]));

                        return;
                    }

                    return array($type, array($this->parseHint(trim($m[1])), $this->normalizeString($m[2])));

                case 'throws':
                    if (!preg_match('/^([^\s]+)\s*(.*)$/s', $match[2], $m)) {
                        throw new \LogicException(sprintf('Unable to parse "@%s" tag "%s"', $type, $match[2]));

                        return;
                    }

                    return array($type, array(trim($m[1]), $this->normalizeString($m[2])));

                default:
                    return array($type, $this->normalizeString($match[2]));
            }
        } else {
            // skip
            $this->cursor = strlen($this->comment);

            throw new \LogicException(sprintf('Unable to parse block comment near "... %s ...".', substr($this->comment, max(0, $this->cursor - 15), 15)));
        }
    }

    protected function parseHint($hint)
    {
        $hints = array();
        foreach (explode('|', $hint) as $hint) {
            if ('[]' == substr($hint, -2)) {
                $hints[] = array(substr($hint, 0, -2), true);
            } else {
                $hints[] = array($hint, false);
            }
        }

        return $hints;
    }

    protected function normalizeString($str)
    {
        return preg_replace('/\s*\n\s*/', ' ', trim($str));
    }

    protected function move($text)
    {
        $this->lineno += substr_count($text, "\n");
        $this->cursor += strlen($text);
    }
}
