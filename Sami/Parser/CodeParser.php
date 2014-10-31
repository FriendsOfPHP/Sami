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

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\Parser as PhpParser;

class CodeParser
{
    protected $parser;
    protected $traverser;
    protected $context;

    public function __construct(ParserContext $context, PhpParser $parser, NodeTraverser $traverser)
    {
        $this->context = $context;
        $this->parser = $parser;
        $this->traverser = $traverser;

        // with big fluent interfaces it can happen that PHP-Parser's Traverser
        // exceeds the 100 recursions limit; we set it to 10000 to be sure.
        ini_set('xdebug.max_nesting_level', 10000);
    }

    public function getContext()
    {
        return $this->context;
    }

    public function parse($code)
    {
        try {
            $this->traverser->traverse($this->parser->parse($code));
        } catch (Error $e) {
            $this->context->addError($this->context->getFile(), 0, $e->getMessage());
        }
    }
}
