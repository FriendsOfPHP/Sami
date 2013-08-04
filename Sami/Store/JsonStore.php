<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Store;

use Sami\Reflection\ClassReflection;
use Sami\Project;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class JsonStore implements StoreInterface
{
    const JSON_PRETTY_PRINT = 128;

    /**
     * @return ReflectionClass A ReflectionClass instance
     *
     * @throws \InvalidArgumentException if the class does not exist in the store
     */
    public function readClass(Project $project, $name)
    {
        if (!file_exists($this->getFilename($project, $name))) {
            throw new \InvalidArgumentException(sprintf('File "%s" for class "%s" does not exist.', $this->getFilename($project, $name), $name));
        }

        return ClassReflection::fromArray($project, json_decode(file_get_contents($this->getFilename($project, $name)), true));
    }

    public function removeClass(Project $project, $name)
    {
        if (!file_exists($this->getFilename($project, $name))) {
            throw new \RuntimeException(sprintf('Unable to remove the "%s" class.', $name));
        }

        unlink($this->getFilename($project, $name));
    }

    public function writeClass(Project $project, ClassReflection $class)
    {
        file_put_contents($this->getFilename($project, $class->getName()), json_encode($class->toArray(), self::JSON_PRETTY_PRINT));
    }

    public function readProject(Project $project)
    {
        $classes = array();
        foreach (Finder::create()->name('c_*.json')->in($this->getStoreDir($project)) as $file) {
            $classes[] = ClassReflection::fromArray($project, json_decode(file_get_contents($file), true));
        }

        return $classes;
    }

    public function flushProject(Project $project)
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->getStoreDir($project));
    }

    protected function getFilename($project, $name)
    {
        $dir = $this->getStoreDir($project);

        return $dir.'/c_'.md5($name).'.json';
    }

    protected function getStoreDir(Project $project)
    {
        $dir = $project->getCacheDir().'/store';

        if (!is_dir($dir)) {
            $filesystem = new Filesystem();
            $filesystem->mkdir($dir);
        }

        return $dir;
    }
}
