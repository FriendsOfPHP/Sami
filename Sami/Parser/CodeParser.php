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

class CodeParser
{
    protected $parser;
    protected $traverser;
    protected $context;

    public function __construct(ParserContext $context, \PHPParser_Parser $parser, \PHPParser_NodeTraverser $traverser)
    {
        $this->context = $context;
        $this->parser = $parser;
        $this->traverser = $traverser;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function parse($code)
    {
        try {
            $this->traverser->traverse($this->parser->parse($code));
        } catch (\PHPParser_Error $e) {
            $this->context->addError($this->context->getFile(), 0, $e->getMessage());
        }
    }
}
