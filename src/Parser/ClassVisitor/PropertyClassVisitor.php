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

use Sami\Reflection\ClassReflection;
use Sami\Parser\ClassVisitorInterface;
use Sami\Reflection\PropertyReflection;
use Sami\Reflection\ParameterReflection;

/**
 * Looks for @property tags on classes in the format of:
 * @property [<type>] [name] [<description>]
 */
class PropertyClassVisitor implements ClassVisitorInterface
{
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
     * @return Boolean
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

        $parts = preg_split('/(?:\s+)/Su', $tag, 3, PREG_SPLIT_DELIM_CAPTURE);
        if (isset($parts[1])) {
            if ('$' !== $parts[0][0]) {
                $type = $parts[0];
                $propertyName = substr($parts[1], 1);
            } elseif ('$' !== $parts[1][0]) {
                $type = $parts[1];
                $propertyName = substr($parts[0], 1);
            }
        } elseif (isset($parts[0])) {
            $propertyName = substr($parts[0], 1);
        } else {
            return array();
        }

        $description = implode('', $parts);
        $property = array('name' => $propertyName);
        if (isset($type)) {
            $property['hint'] = $type;
        }
        if (isset($description)) {
            $property['description'] = $description;
        }

        return $property;
    }
}
