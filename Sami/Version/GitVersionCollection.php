<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Version;

use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Process;
use Symfony\Component\Finder\Glob;

class GitVersionCollection extends VersionCollection
{
    protected $sorter;
    protected $filter;
    protected $repo;
    protected $gitPath;

    public function __construct($repo)
    {
        $this->repo = $repo;
        $this->filter = function ($version) {
            foreach (array('PR', 'RC', 'BETA', 'ALPHA') as $str) {
                if (strpos($version, $str)) {
                    return false;
                }
            }

            return true;
        };
        $this->sorter = function ($a, $b) {
            return version_compare($a, $b, '>');
        };
        $this->gitPath = 'git';
    }

    protected function switchVersion(Version $version)
    {
        $process = new Process('git status --porcelain | grep -v "??" | wc -l', $this->repo);
        $process->run();
        if (!$process->isSuccessful() || (int) $process->getOutput() > 0) {
            throw new \RuntimeException(sprintf('Unable to switch to version "%s" as the repository is not clean.', $version));
        }

        $this->execute(array('checkout', '-qf', (string) $version));
    }

    public function setGitPath($path)
    {
        $this->gitPath = $path;
    }

    public function setFilter(\Closure $filter)
    {
        $this->filter = $filter;
    }

    public function setSorter(\Closure $sorter)
    {
        $this->sorter = $sorter;
    }

    public function addFromTags($filter = null)
    {
        $tags = array_filter(explode("\n", $this->execute(array('tag'))));

        $versions = array_filter($tags, $this->filter);
        if (null !== $filter) {
            if (!$filter instanceof \Closure) {
                $regexes = array();
                foreach ((array) $filter as $f) {
                    $regexes[] = Glob::toRegex($f);
                }
                $filter = function ($version) use ($regexes) {
                    foreach ($regexes as $regex) {
                        if (preg_match($regex, $version)) {
                            return true;
                        }
                    }

                    return false;
                };
            }

            $versions = array_filter($versions, $filter);
        }
        usort($versions, $this->sorter);

        foreach ($versions as $version) {
            $version = new Version($version);
            $version->setFrozen(true);
            $this->add($version);
        }

        return $this;
    }

    protected function execute($arguments)
    {
        array_unshift($arguments, $this->gitPath);

        $builder = new ProcessBuilder($arguments);
        $builder->setWorkingDirectory($this->repo);
        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('Unable to run the command (%s).', $process->getErrorOutput()));
        }

        return $process->getOutput();
    }
}
