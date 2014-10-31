<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Renderer;

use Michelf\Markdown;
use Sami\Reflection\ClassReflection;
use Sami\Reflection\MethodReflection;
use Sami\Reflection\PropertyReflection;

class TwigExtension extends \Twig_Extension
{
    protected $markdown;
    protected $project;
    protected $currentDepth;

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        return array(
            'desc'    => new \Twig_Filter_Method($this, 'parseDesc', array('needs_context' => true, 'is_safe' => array('html'))),
            'snippet' => new \Twig_Filter_Method($this, 'getSnippet'),
        );
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'namespace_path' => new \Twig_Function_Method($this, 'pathForNamespace', array('needs_context' => true)),
            'class_path'     => new \Twig_Function_Method($this, 'pathForClass', array('needs_context' => true)),
            'method_path'    => new \Twig_Function_Method($this, 'pathForMethod', array('needs_context' => true)),
            'property_path'  => new \Twig_Function_Method($this, 'pathForProperty', array('needs_context' => true)),
            'path'           => new \Twig_Function_Method($this, 'pathForStaticFile', array('needs_context' => true)),
            'abbr_class'     => new \Twig_Function_Method($this, 'abbrClass', array('is_safe' => array('html'))),
        );
    }

    public function setCurrentDepth($depth)
    {
        $this->currentDepth = $depth;
    }

    public function pathForClass(array $context, ClassReflection $class)
    {
        return $this->relativeUri($this->currentDepth).str_replace('\\', '/', $class).'.html';
    }

    public function pathForNamespace(array $context, $namespace)
    {
        return $this->relativeUri($this->currentDepth).str_replace('\\', '/', $namespace).'.html';
    }

    public function pathForMethod(array $context, MethodReflection $method)
    {
        return $this->relativeUri($this->currentDepth).str_replace('\\', '/', $method->getClass()->getName()).'.html#method_'.$method->getName();
    }

    public function pathForProperty(array $context, PropertyReflection $property)
    {
        return $this->relativeUri($this->currentDepth).str_replace('\\', '/', $property->getClass()->getName()).'.html#property_'.$property->getName();
    }

    public function pathForStaticFile(array $context, $file)
    {
        return $this->relativeUri($this->currentDepth).$file;
    }

    public function abbrClass($class)
    {
        if ($class instanceof ClassReflection) {
            $short = $class->getShortName();
            $class = $class->getName();
        } else {
            $parts = explode('\\', $class);

            if (1 == count($parts)) {
                return $class;
            }

            $short = array_pop($parts);
        }

        return sprintf("<abbr title=\"%s\">%s</abbr>", $class, $short);
    }

    public function parseDesc(array $context, $desc, ClassReflection $class)
    {
        if (null === $this->markdown) {
            $this->markdown = new Markdown();
        }

        // FIXME: the @see argument is more complex than just a class (Class::Method, local method directly, ...)
        $that = $this;
        $desc = preg_replace_callback('/@see ([^ ]+)/', function ($match) use ($that, $context, $class) {
            return 'see '.$match[1];
        }, $desc);

        return preg_replace(array('#^<p>\s*#s', '#\s*</p>\s*$#s'), '', $this->markdown->transform($desc));
    }

    public function getSnippet($string)
    {
        if (preg_match('/^(.{50,}?)\s.*/m', $string, $matches)) {
            $string = $matches[1];
        }

        return str_replace(array("\n", "\r"), '', strip_tags($string));
    }

    protected function relativeUri($value)
    {
        if (!$value) {
            return '';
        }

        return rtrim(str_repeat('../', $value), '/').'/';
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'sami';
    }
}
