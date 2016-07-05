<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Parser\ClassVisitor;

use phpDocumentor\Reflection\DocBlock\Tag\PropertyTag;
use Sami\Parser\ClassVisitorInterface;
use Sami\Parser\ParserContext;
use Sami\Reflection\ClassReflection;
use Sami\Reflection\PropertyReflection;

/**
 * Looks for @property tags on classes in the format of:.
 *
 * @property [<type>] [name] [<description>]
 */
class PropertyClassVisitor implements ClassVisitorInterface
{
    protected $context;

    public function __construct(ParserContext $context)
    {
        $this->context = $context;
    }

    public function visit(ClassReflection $class)
    {
        $modified = false;
        $properties = $class->getTags('property');
        if (!empty($properties)) {
            foreach ($properties as $propertyTag) {
                if ($this->injectProperty($class, implode(' ', $propertyTag))) {
                    $modified = true;
                }
            }
        }

        return $modified;
    }

    /**
     * Adds a new property to the class using an array of tokens.
     *
     * @param ClassReflection $class       Class reflection
     * @param string          $propertyTag Property tag contents
     *
     * @return bool
     */
    protected function injectProperty(ClassReflection $class, $propertyTag)
    {
        if (!$data = $this->parseProperty($propertyTag)) {
            return false;
        }

        $property = new PropertyReflection($data['name'], $class->getLine());
        $property->setDocComment($data['description']);
        $property->setShortDesc($data['description']);

        if (isset($data['hint'])) {
            $property->setHint(array(array($data['hint'], null)));
        }

        $class->addProperty($property);

        return true;
    }

    /**
     * Parses the parts of an @property tag into an associative array.
     *
     * @param string $tag Property tag contents
     *
     * @return array
     */
    protected function parseProperty($tag)
    {
        // Account for default array syntax
        $tag = str_replace('array()', 'array', $tag);

        /** @var PropertyTag $propertyTag */
        $propertyTag = $this->context->getDocBlockParser()->getTag('@property '.$tag);
        $propertyName = $propertyTag->getVariableName();

        if (!$propertyName) {
            return array();
        }

        $type = $propertyTag->getType();

        $description = $propertyTag->getDescription();
        $property = array('name' => substr($propertyName, 1));
        if (strlen($type)) {
            $property['hint'] = $type;
        }
        $property['description'] = $description;

        return $property;
    }
}
