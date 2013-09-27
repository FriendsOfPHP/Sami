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
    protected $passCode;

    public function __construct(ParserContext $context, \PHPParser_Parser $parser, \PHPParser_NodeTraverser $traverser)
    {
        $this->context = $context;
        $this->parser = $parser;
        $this->traverser = $traverser;

        // hack for a BC break between PHPParser 0.9.1 and 0.9.2
        // the Parser::parse() method argument changed
        $m = new \ReflectionMethod($this->parser, 'parse');
        $parameters = $m->getParameters();
        $this->passCode = null === $parameters[0]->getClass();
    }

    public function getContext()
    {
        return $this->context;
    }

    public function parse($code)
    {
        try {
            if ($this->passCode) {
                $this->traverser->traverse($this->parser->parse($code));
            } else {
                $this->traverser->traverse($this->parser->parse(new \PHPParser_Lexer($code)));
            }
        } catch (\PHPParser_Error $e) {
            $this->context->addError($this->context->getFile(), 0, $e->getMessage());
        }
    }
}
