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
     * Parse the parts of an @property tag into an associative array
     *
     * @param string $tag Property tag contents
     *
     * @return array
     */
    protected function parseProperty($tag)
    {
      // Account for default array syntax
      $tag = str_replace('array()', 'array', $tag);

      $parts = preg_split(
        '/(\s+)/Su',
        $tag,
        3,
        PREG_SPLIT_DELIM_CAPTURE
      );

      // if the first item that is encountered is not a identifier; it is a type
      if (isset($parts[0])
        && (strlen($parts[0]) > 0)
        && ($parts[0][0] !== '$')
      ) {
        $type = array_shift($parts);
        array_shift($parts);
      }

      // if the next item starts with a $ it must be the property name
      if (isset($parts[0])
        && (strlen($parts[0]) > 0)
        && ($parts[0][0] == '$')
      ) {
        $propertyName = substr(array_shift($parts), 1);
        array_shift($parts);
      }

      $description = implode('', $parts);
      $property = array('name' => $propertyName);
      if (isset($type)){
        $property['hint'] = $type;
      }
      if (isset($description)){
        $property['description'] = $description;
      }
      return $property;
    }

    /**
     * Adds a new property to the class using an array of tokens
     *
     * @param ClassReflection $class       Class reflection
     * @param string          $propertyTag Property tag contents
     *
     * @return bool
     */
    protected function injectProperty(ClassReflection $class, $propertyTag)
    {
        $data = $this->parseProperty($propertyTag);

        // Bail if the property format is invalid
        if (!$data) {
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
}
