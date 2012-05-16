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

/**
 * Searches for "@example <filename> <description>" tags and replaces them with
 * an example .php file by filename with or without the .php file extension.
 */
class ExampleClassVisitor implements ClassVisitorInterface
{
    /**
     * @var string Example path
     */
    protected $path;

    /**
     * Construct a new ExampleClassVisitor
     *
     * @param  string $path Full path to where your examples are stored
     *
     * @throws InvalidArgumentException if the directory is invalid
     */
    public function __construct($path)
    {
        $this->path = $path;

        // Remove trailing slashes
        if (substr($this->path, -1, 1) == DIRECTORY_SEPARATOR) {
            $this->path = substr($this->path, 0, -1);
        }

        // Ensure the path exists and is a directory
        if (!is_dir($this->path)) {
            throw new \InvalidArgumentException("Could not find {$this->path}");
        }
    }

    /**
     * Replaces @example with the contents of an example file
     *
     * @param  ClassReflection $class Class to reflect
     *
     * @return bool
     *
     * @throws InvalidArgumentException If the example cannot be found
     */
    public function visit(ClassReflection $class)
    {
        $modified = false;

        foreach ($class->getMethods() as $name => $method) {

            $tags = $method->getTags('example');
            if (empty($tags)) {
                continue;
            }

            foreach ($tags as &$example) {

                // Ensure that the path was provided
                if (empty($example) || empty($example[0])) {
                    throw new \InvalidArgumentException('No path was provided'
                        . ' for the @example tag');
                }

                $path = $this->path . DIRECTORY_SEPARATOR . $example[0];
                // Add a .php extension if it was not set
                if (!strpos($path, '.php')) {
                    $path .= '.php';
                }

                if (!is_readable($path)) {
                    throw new \InvalidArgumentException("Unable to read {$path}");
                }

                // Load the contents of the example and update the doc comment
                $example = array(
                    $example[0],
                    isset($example[1]) ? implode(' ', array_slice($example, 1)) : null,
                    file_get_contents($path)
                );
            }

            $method->setTag('example', $tags);
        }

        return true;
    }
}
