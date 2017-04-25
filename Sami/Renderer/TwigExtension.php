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

use Michelf\MarkdownExtra;
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
            new \Twig_SimpleFilter('desc', array($this, 'parseDesc'), array('needs_context' => true, 'is_safe' => array('html'))),
            new \Twig_SimpleFilter('snippet', array($this, 'getSnippet')),
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
            new \Twig_SimpleFunction('namespace_path', array($this, 'pathForNamespace'), array('needs_context' => true, 'is_safe' => array('all'))),
            new \Twig_SimpleFunction('class_path', array($this, 'pathForClass'), array('needs_context' => true, 'is_safe' => array('all'))),
            new \Twig_SimpleFunction('method_path', array($this, 'pathForMethod'), array('needs_context' => true, 'is_safe' => array('all'))),
            new \Twig_SimpleFunction('property_path', array($this, 'pathForProperty'), array('needs_context' => true, 'is_safe' => array('all'))),
            new \Twig_SimpleFunction('path', array($this, 'pathForStaticFile'), array('needs_context' => true)),
            new \Twig_SimpleFunction('abbr_class', array($this, 'abbrClass'), array('is_safe' => array('all'))),
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

    public function abbrClass($class, $absolute = false)
    {
        if ($class instanceof ClassReflection) {
            $short = $class->getShortName();
            $class = $class->getName();

            if ($short === $class && !$absolute) {
                return $class;
            }
        } else {
            $parts = explode('\\', $class);

            if (1 == count($parts) && !$absolute) {
                return $class;
            }

            $short = array_pop($parts);
        }

        return sprintf('<abbr title="%s">%s</abbr>', $class, $short);
    }

    public function parseDesc(array $context, $desc, ClassReflection $class)
    {
        if (!$desc) {
            return $desc;
        }

        if (null === $this->markdown) {
            $this->markdown = new MarkdownExtra();
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
}
