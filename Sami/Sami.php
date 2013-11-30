<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami;

use Sami\Tree;
use Sami\Indexer;
use Sami\Parser\CodeParser;
use Sami\Parser\Parser;
use Sami\Parser\NodeVisitor;
use Sami\Parser\ParserContext;
use Sami\Parser\DocBlockParser;
use Sami\Parser\Filter\DefaultFilter;
use Sami\Parser\ClassTraverser;
use Sami\Parser\ClassVisitor;
use Sami\Store\JsonStore;
use Sami\Renderer\Renderer;
use Sami\Renderer\ThemeSet;
use Sami\Renderer\TwigExtension;
use Sami\Version\Version;
use Sami\Version\SingleVersionCollection;

class Sami extends \Pimple
{
    const VERSION = '1.3';

    public function __construct($iterator = null, array $config = array())
    {
        $sc = $this;

        if (null !== $iterator) {
            $this['files'] = $iterator;
        }

        $this['_versions'] = $this->share(function () use ($sc) {
            $versions = isset($sc['versions']) ? $sc['versions'] : $sc['version'];

            if (is_string($versions)) {
                $versions = new Version($versions);
            }

            if ($versions instanceof Version) {
                $versions = new SingleVersionCollection($versions);
            }

            return $versions;
        });

        $this['project'] = $this->share(function () use ($sc) {
            $project = new Project($sc['store'], $sc['_versions'], array(
                'build_dir' => $sc['build_dir'],
                'cache_dir' => $sc['cache_dir'],
                'simulate_namespaces' => $sc['simulate_namespaces'],
                'include_parent_data' => $sc['include_parent_data'],
                'default_opened_level' => $sc['default_opened_level'],
                'theme' => $sc['theme'],
            ));
            $project->setRenderer($sc['renderer']);
            $project->setParser($sc['parser']);

            return $project;
        });

        $this['parser'] = $this->share(function () use ($sc) {
            return new Parser($sc['files'], $sc['store'], $sc['code_parser'], $sc['traverser']);
        });

        $this['indexer'] = $this->share(function () use ($sc) {
            return new Indexer();
        });

        $this['tree'] = $this->share(function () use ($sc) {
            return new Tree();
        });

        $this['parser_context'] = $this->share(function () use ($sc) {
            return new ParserContext($sc['filter'], $sc['docblock_parser'], $sc['pretty_printer']);
        });

        $this['docblock_parser'] = $this->share(function () use ($sc) {
            return new DocBlockParser();
        });

        $this['php_parser'] = $this->share(function () {
            return new \PHPParser_Parser(new \PHPParser_Lexer());
        });

        $this['php_traverser'] = $this->share(function () use ($sc) {
            $traverser = new \PHPParser_NodeTraverser();
            $traverser->addVisitor(new \PHPParser_NodeVisitor_NameResolver());
            $traverser->addVisitor(new NodeVisitor($sc['parser_context']));

            return $traverser;
        });

        $this['code_parser'] = $this->share(function () use ($sc) {
            return new CodeParser($sc['parser_context'], $sc['php_parser'], $sc['php_traverser']);
        });

        $this['pretty_printer'] = $this->share(function () use ($sc) {
            return new \PHPParser_PrettyPrinter_Zend();
        });

        $this['filter'] = $this->share(function () use ($sc) {
            return new DefaultFilter();
        });

        $this['store'] = $this->share(function () use ($sc) {
            return new JsonStore();
        });

        $this['renderer'] = $this->share(function () use ($sc) {
            return new Renderer($sc['twig'], $sc['themes'], $sc['tree'], $sc['indexer']);
        });

        $this['traverser'] = $this->share(function () use ($sc) {
            $visitors = array(
                new ClassVisitor\InheritdocClassVisitor(),
                new ClassVisitor\MethodClassVisitor(),
                new ClassVisitor\PropertyClassVisitor(),
            );

            return new ClassTraverser($visitors);
        });

        $this['themes'] = $this->share(function () use ($sc) {
            $templates = $sc['template_dirs'];
            $templates[] = __DIR__.'/Resources/themes';

            return new ThemeSet($templates);
        });

        $this['twig'] = $this->share(function () use ($sc) {
            $twig = new \Twig_Environment(new \Twig_Loader_Filesystem(array('/')), array(
                'strict_variables' => true,
                'debug'            => true,
                'auto_reload'      => true,
                'cache'            => false,
            ));
            $twig->addExtension(new TwigExtension());

            return $twig;
        });

        $this['theme'] = 'enhanced';
        $this['title'] = 'API';
        $this['version'] = 'master';
        $this['template_dirs'] = array();
        $this['build_dir'] = getcwd().'/build';
        $this['cache_dir'] = getcwd().'/cache';

        // simulate namespaces for projects based on the PEAR naming conventions
        $this['simulate_namespaces'] = false;

        // include parent properties and methods on class pages
        $this['include_parent_data'] = true;

        foreach ($config as $key => $value) {
            $this[$key] = $value;
        }
    }
}
