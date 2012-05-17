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

use Sami\Project;

class Index implements \Serializable
{
    protected $classes;
    protected $versions;
    protected $namespaces;

    public function __construct(Project $project = null)
    {
        $this->classes = array();
        if (null !== $project) {
            foreach ($project->getProjectClasses() as $class) {
                $this->classes[$class->getName()] = $class->getHash();
            }
        }

        $this->versions = array();
        if (null !== $project) {
            foreach ($project->getVersions() as $version) {
                $this->versions[] = (string) $version;
            }
        }

        $this->namespaces = array();
        if (null !== $project) {
            $this->namespaces = $project->getConfig('simulate_namespaces') ? $project->getSimulatedNamespaces() : $project->getNamespaces();
        }
    }

    public function getVersions()
    {
        return $this->versions;
    }

    public function getClasses()
    {
        return $this->classes;
    }

    public function getNamespaces()
    {
        return $this->namespaces;
    }

    public function getHash($class)
    {
        return isset($this->classes[$class]) ? $this->classes[$class] : false;
    }

    public function serialize()
    {
        return serialize(array($this->classes, $this->versions, $this->namespaces));
    }

    public function unserialize($data)
    {
        list($this->classes, $this->versions, $this->namespaces) = unserialize($data);
    }
}
