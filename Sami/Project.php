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

use Sami\Store\StoreInterface;
use Sami\Reflection\ClassReflection;
use Sami\Reflection\LazyClassReflection;
use Sami\Parser\Parser;
use Sami\Renderer\Renderer;
use Sami\Version\Version;
use Sami\Version\VersionCollection;
use Sami\Version\SingleVersionCollection;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Project represents an API project.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Project
{
    protected $versions;
    protected $store;
    protected $parser;
    protected $renderer;
    protected $classes;
    protected $namespaceClasses;
    protected $namespaceInterfaces;
    protected $namespaceExceptions;
    protected $namespaces;
    protected $simulatedNamespaces;
    protected $config;
    protected $version;
    protected $filesystem;

    public function __construct(StoreInterface $store, VersionCollection $versions = null, array $config = array())
    {
        if (null === $versions) {
            $versions = new SingleVersionCollection(new Version('master'));
        }
        $this->versions = $versions;
        $this->store = $store;
        $this->config = array_merge(array(
            'build_dir' => sys_get_temp_dir().'sami/build',
            'cache_dir' => sys_get_temp_dir().'sami/cache',
            'simulate_namespaces' => false,
            'include_parent_data' => true,
            'theme' => 'enhanced',
        ), $config);
        $this->filesystem = new Filesystem();

        if (count($this->versions) > 1) {
            foreach (array('build_dir', 'cache_dir') as $dir) {
                if (false === strpos($this->config[$dir], '%version%')) {
                    throw new \LogicException(sprintf('The "%s" setting must have the "%%version%%" placeholder as the project has more than one version.', $dir));
                }
            }
        }

        $this->initialize();
    }

    public function setRenderer(Renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function getConfig($name, $default = null)
    {
        return isset($this->config[$name]) ? $this->config[$name] : $default;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getVersions()
    {
        return $this->versions->getVersions();
    }

    public function update($callback = null, $force = false)
    {
        $previousParse = null;
        $previousRender = null;
        foreach ($this->versions as $version) {
            $this->switchVersion($version, $callback);

            $this->parseVersion($version, $previousParse, $callback, $force);
            $this->renderVersion($version, $previousRender, $callback, $force);

            $previousParse = $this->getCacheDir();
            $previousRender = $this->getBuildDir();
        }
    }

    public function parse($callback = null, $force = false)
    {
        $previous = null;
        foreach ($this->versions as $version) {
            $this->switchVersion($version, $callback);

            $this->parseVersion($version, $previous, $callback, $force);

            $previous = $this->getCacheDir();
        }
    }

    public function render($callback = null, $force = false)
    {
        $previous = null;
        foreach ($this->versions as $version) {
            $this->switchVersion($version, $callback);

            $this->renderVersion($version, $previous, $callback, $force);

            $previous = $this->getBuildDir();
        }
    }

    public function switchVersion(Version $version, $callback = null)
    {
        if (null !== $callback) {
            call_user_func($callback, Message::SWITCH_VERSION, $version);
        }

        $this->version = $version;
        $this->read();
    }

    public function hasNamespaces()
    {
        // if there is only one namespace and this is the global one, it means that there is no namespace in the project
        return array('') != array_keys($this->namespaces);
    }

    public function hasNamespace($namespace)
    {
        return array_key_exists($namespace, $this->namespaces);
    }

    public function getNamespaces()
    {
        ksort($this->namespaces);

        return array_keys($this->namespaces);
    }

    public function getSimulatedNamespaces()
    {
        ksort($this->simulatedNamespaces);

        return array_keys($this->simulatedNamespaces);
    }

    public function getSimulatedNamespaceAllClasses($namespace)
    {
        if (!isset($this->simulatedNamespaces[$namespace])) {
            return array();
        }

        ksort($this->simulatedNamespaces[$namespace]);

        return $this->simulatedNamespaces[$namespace];
    }

    public function getNamespaceAllClasses($namespace)
    {
        $classes = array_merge(
            $this->getNamespaceExceptions($namespace),
            $this->getNamespaceInterfaces($namespace),
            $this->getNamespaceClasses($namespace)
        );

        ksort($classes);

        return $classes;
    }

    public function getNamespaceExceptions($namespace)
    {
        if (!isset($this->namespaceExceptions[$namespace])) {
            return array();
        }

        ksort($this->namespaceExceptions[$namespace]);

        return $this->namespaceExceptions[$namespace];
    }

    public function getNamespaceClasses($namespace)
    {
        if (!isset($this->namespaceClasses[$namespace])) {
            return array();
        }

        ksort($this->namespaceClasses[$namespace]);

        return $this->namespaceClasses[$namespace];
    }

    public function getNamespaceInterfaces($namespace)
    {
        if (!isset($this->namespaceInterfaces[$namespace])) {
            return array();
        }

        ksort($this->namespaceInterfaces[$namespace]);

        return $this->namespaceInterfaces[$namespace];
    }

    public function addClass(ClassReflection $class)
    {
        $this->classes[$class->getName()] = $class;
        $class->setProject($this);

        if ($class->isProjectClass()) {
            $this->updateCache($class);
        }
    }

    public function removeClass(ClassReflection $class)
    {
        unset($this->classes[$class->getName()]);
        unset($this->interfaces[$class->getName()]);
        unset($this->namespaceClasses[$class->getNamespace()][$class->getName()]);
        unset($this->namespaceInterfaces[$class->getNamespace()][$class->getName()]);
        unset($this->namespaceExceptions[$class->getNamespace()][$class->getName()]);
    }

    public function getProjectInterfaces()
    {
        $interfaces = array();
        foreach ($this->interfaces as $interface) {
            if ($interface->isProjectClass()) {
                $interfaces[$interface->getName()] = $interface;
            }
        }
        ksort($interfaces);

        return $interfaces;
    }

    public function getProjectClasses()
    {
        $classes = array();
        foreach ($this->classes as $name => $class) {
            if ($class->isProjectClass()) {
                $classes[$name] = $class;
            }
        }
        ksort($classes);

        return $classes;
    }

    public function getClass($name)
    {
        $name = ltrim($name, '\\');

        if (isset($this->classes[$name])) {
            return $this->classes[$name];
        }

        $this->addClass($class = new LazyClassReflection($name));

        return $class;
    }

    // this must only be used in LazyClassReflection to get the right values
    public function loadClass($name)
    {
        $name = ltrim($name, '\\');

        if ($this->getClass($name) instanceof LazyClassReflection) {
            try {
                $this->addClass($this->store->readClass($this, $name));
            } catch (\InvalidArgumentException $e) {
                // probably a PHP built-in class
                return null;
            }
        }

        return $this->classes[$name];
    }

    public function initialize()
    {
        $this->namespaces = array();
        $this->simulatedNamespaces = array();
        $this->interfaces = array();
        $this->classes = array();
        $this->namespaceClasses = array();
        $this->namespaceInterfaces = array();
        $this->namespaceExceptions = array();
    }

    public function read()
    {
        $this->initialize();

        foreach ($this->store->readProject($this) as $class) {
            $this->addClass($class);
        }
    }

    public function getBuildDir()
    {
        return $this->prepareDir($this->config['build_dir']);
    }

    public function getCacheDir()
    {
        return $this->prepareDir($this->config['cache_dir']);
    }

    public function flushDir($dir)
    {
        $this->filesystem->remove($dir);
        $this->filesystem->mkdir($dir);
        file_put_contents($dir.'/SAMI_VERSION', Sami::VERSION);
        file_put_contents($dir.'/PROJECT_VERSION', $this->version);
    }

    public function seedCache($previous, $current)
    {
        $this->filesystem->remove($current);
        $this->filesystem->mirror($previous, $current);
        $this->read();
    }

    static public function isPhpTypeHint($hint)
    {
        return in_array(strtolower($hint), array('', 'scalar', 'object', 'boolean', 'bool', 'int', 'integer', 'array', 'string', 'mixed', 'void', 'null', 'resource', 'double', 'float', 'callable'));
    }

    protected function updateCache(ClassReflection $class)
    {
        $name = $class->getName();

        $this->namespaces[$class->getNamespace()] = $class->getNamespace();
        // add sub-namespaces
        $namespace = $class->getNamespace();
        while ($namespace = substr($namespace, 0, strrpos($namespace, '\\'))) {
            $this->namespaces[$namespace] = $namespace;
        }

        if ($class->isException()) {
            $this->namespaceExceptions[$class->getNamespace()][$name] = $class;
        } elseif ($class->isInterface()) {
            $this->namespaceInterfaces[$class->getNamespace()][$name] = $class;
            $this->interfaces[$name] = $class;
        } else {
            $this->namespaceClasses[$class->getNamespace()][$name] = $class;
        }

        if ($this->getConfig('simulate_namespaces')) {
            if (false !== $pos = strrpos($name, '_')) {
                $this->simulatedNamespaces[$namespace = str_replace('_', '\\', substr($name, 0, $pos))][$name] = $class;
                // add sub-namespaces
                while ($namespace = substr($namespace, 0, strrpos($namespace, '\\'))) {
                    if (!isset($this->simulatedNamespaces[$namespace])) {
                        $this->simulatedNamespaces[$namespace] = array();
                    }
                }
            } else {
                $this->simulatedNamespaces[''][$name] = $class;
            }
        }
    }

    protected function prepareDir($dir)
    {
        $dir = $this->replaceVars($dir);

        if (!is_dir($dir)) {
            $this->flushDir($dir);
        }

        $samiVersion = null;
        if (file_exists($dir.'/SAMI_VERSION')) {
            $samiVersion = file_get_contents($dir.'/SAMI_VERSION');
        }

        if ($samiVersion !== Sami::VERSION) {
            $this->flushDir($dir);
        }

        return $dir;
    }

    protected function replaceVars($pattern)
    {
        return str_replace('%version%', $this->version, $pattern);
    }

    protected function parseVersion(Version $version, $previous, $callback = null, $force = false)
    {
        if (null === $this->parser) {
            throw new \LogicException('You must set a parser.');
        }

        if ($version->isFrozen() && count($this->classes) > 0) {
            return;
        }

        if ($force) {
            $this->store->flushProject($this);
        }

        if ($previous && 0 === count($this->classes)) {
            $this->seedCache($previous, $this->getCacheDir());
        }

        $transaction = $this->parser->parse($this, $callback);

        if (null !== $callback) {
            call_user_func($callback, Message::PARSE_VERSION_FINISHED, $transaction);
        }
    }

    protected function renderVersion(Version $version, $previous, $callback = null, $force = false)
    {
        if (null === $this->renderer) {
            throw new \LogicException('You must set a renderer.');
        }

        $frozen = $version->isFrozen() && $this->renderer->isRendered($this) && $this->version === file_get_contents($this->getBuildDir().'/PROJECT_VERSION');

        if ($force && !$frozen) {
            $this->flushDir($this->getBuildDir());
        }

        if ($previous && !$this->renderer->isRendered($this)) {
            $this->seedCache($previous, $this->getBuildDir());
        }

        $diff = $this->renderer->render($this, $callback);

        if (null !== $callback) {
            call_user_func($callback, Message::RENDER_VERSION_FINISHED, $diff);
        }
    }
}
