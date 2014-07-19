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

use phpDocumentor\Reflection\DocBlock;
use Sami\Parser\Node\DocBlockNode;

class DocBlockParser
{
    public function parse($comment, ParserContext $context)
    {
        $docBlock = null;
        $errorMessage = '';

        try {
            $docBlockContext = new DocBlock\Context($context->getNamespace(), $context->getAliases());
            $docBlock = new DocBlock((string) $comment, $docBlockContext);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        $result = new DocBlockNode();

        if ($errorMessage) {
            $result->addError($errorMessage);

            return $result;
        }

        $result->setShortDesc($docBlock->getShortDescription());
        $result->setLongDesc((string) $docBlock->getLongDescription());

        foreach ($docBlock->getTags() as $tag) {
            $result->addTag($tag->getName(), $this->parseTag($tag));
        }

        return $result;
    }

    protected function parseTag(DocBlock\Tag $tag)
    {
        if ($tag instanceof DocBlock\Tag\VarTag) {
            return array(
                $this->parseHint($tag->getTypes()),
                $tag->getDescription(),
            );
        }

        if ($tag instanceof DocBlock\Tag\ParamTag) {
            return array(
                $this->parseHint($tag->getTypes()),
                ltrim($tag->getVariableName(), '$'),
                $tag->getDescription(),
            );
        }

        if ($tag instanceof DocBlock\Tag\ThrowsTag) {
            return array(
                $tag->getType(),
                $tag->getDescription(),
            );
        }

        if ($tag instanceof DocBlock\Tag\ReturnTag) {
            return array(
                $this->parseHint($tag->getTypes()),
                $tag->getDescription(),
            );
        }

        return $tag->getContent();
    }

    protected function parseHint($rawHints)
    {
        $hints = array();
        foreach ($rawHints as $hint) {
            if ('[]' == substr($hint, -2)) {
                $hints[] = array(substr($hint, 0, -2), true);
            } else {
                $hints[] = array($hint, false);
            }
        }

        return $hints;
    }
}
