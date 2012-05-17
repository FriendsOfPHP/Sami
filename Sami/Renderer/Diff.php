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

class Diff
{
    protected $project;
    protected $current;
    protected $versions;
    protected $filename;
    protected $alreadyRendered;
    protected $previousNamespaces;
    protected $currentNamespaces;

    public function __construct(Project $project, $filename)
    {
        $this->project = $project;
        $this->current = new Index($project);
        $this->filename = $filename;

        if (file_exists($filename)) {
            $this->alreadyRendered = true;
            if (false === $this->previous = @unserialize(file_get_contents($filename))) {
                $this->alreadyRendered = false;
                $this->previous = new Index();
            }
        } else {
            $this->alreadyRendered = false;
            $this->previous = new Index();
        }

        $this->previousNamespaces = $this->previous->getNamespaces();
        $this->currentNamespaces = $this->current->getNamespaces();
    }

    public function isEmpty()
    {
        return !$this->areVersionsModified() && (0 == count($this->getModifiedClasses()) + count($this->getRemovedClasses()));
    }

    public function save()
    {
        file_put_contents($this->filename, serialize($this->current));
    }

    public function isAlreadyRendered()
    {
        return $this->alreadyRendered;
    }

    public function areVersionsModified()
    {
        $versions = array();
        foreach ($this->project->getVersions() as $version) {
            $versions[] = (string) $version;
        }

        return $versions != $this->previous->getVersions();
    }

    public function getModifiedNamespaces()
    {
        return array_diff($this->currentNamespaces, $this->previousNamespaces);
    }

    public function getRemovedNamespaces()
    {
        return array_diff($this->previousNamespaces, $this->currentNamespaces);
    }

    public function getModifiedClasses()
    {
        $classes = array();
        foreach ($this->current->getClasses() as $class => $hash) {
            if ($hash !== $this->previous->getHash($class)) {
                $classes[] = $this->project->getClass($class);
            }
        }

        return $classes;
    }

    public function getRemovedClasses()
    {
        return array_diff(array_keys($this->previous->getClasses()), array_keys($this->current->getClasses()));
    }
}
