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
use phpDocumentor\Reflection\DocBlock;

class DocBlockParser
{
    public function parse($comment, ParserContext $context)
    {
        $docBlock = null;
        $errorMessage = '';

        try {
            $docBlockContext = new DocBlock\Context($context->getNamespace(), $context->getAliases() ?: array());
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
        $tagClass = get_class($tag);

        if ('phpDocumentor\Reflection\DocBlock\Tag\VarTag' === $tagClass) {
            /** @var DocBlock\Tag\VarTag $tag */

            return array(
                $this->parseHint($tag->getTypes()),
                $tag->getDescription(),
            );
        }

        if ('phpDocumentor\Reflection\DocBlock\Tag\ParamTag' === $tagClass) {
            /** @var DocBlock\Tag\ParamTag $tag */

            return array(
                $this->parseHint($tag->getTypes()),
                ltrim($tag->getVariableName(), '$'),
                $tag->getDescription(),
            );
        }

        if ('phpDocumentor\Reflection\DocBlock\Tag\ThrowsTag' === $tagClass) {
            /** @var DocBlock\Tag\ThrowsTag $tag */

            return array(
                $tag->getType(),
                $tag->getDescription(),
            );
        }

        if ('phpDocumentor\Reflection\DocBlock\Tag\ReturnTag' === $tagClass) {
            /** @var DocBlock\Tag\ReturnTag $tag */

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
