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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ThemeSet
{
    protected $themes;

    public function __construct(array $dirs)
    {
        $this->discover($dirs);
    }

    public function getTheme($name)
    {
        if (!isset($this->themes[$name])) {
            throw new \InvalidArgumentException(sprintf('Theme "%s" does not exist.', $name));
        }

        return $this->themes[$name];
    }

    protected function discover(array $dirs)
    {
        $this->themes = array();
        $parents = array();
        foreach (Finder::create()->name('manifest.yml')->in($dirs) as $manifest) {
            $config = Yaml::parse($manifest);
            if (!isset($config['name'])) {
                throw new \InvalidArgumentException(sprintf('Theme manifest in "%s" must have a "name" entry.', $manifest));
            }

            $this->themes[$config['name']] = $theme = new Theme($config['name'], dirname($manifest));

            if (isset($config['parent'])) {
                $parents[$config['name']] = $config['parent'];
            }

            foreach (array('static', 'global', 'namespace', 'class') as $type) {
                if (isset($config[$type])) {
                    $theme->setTemplates($type, $config[$type]);
                }
            }
        }

        // populate parent
        foreach ($parents as $name => $parent) {
            if (!isset($this->themes[$parent])) {
                throw new \LogicException(sprintf('Theme "%s" inherits from an unknomwn "%s" theme.', $name, $parent));
            }

            $this->themes[$name]->setParent($this->themes[$parent]);
        }
    }
}
