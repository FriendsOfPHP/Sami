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

use Sami\Project;

class Indexer
{
    const TYPE_CLASS     = 1;
    const TYPE_METHOD    = 2;
    const TYPE_NAMESPACE = 3;

    public function getIndex(Project $project)
    {
        $index = array(
            'searchIndex' => array(),
            'info'        => array(),
        );

        foreach ($project->getNamespaces() as $namespace) {
            $index['searchIndex'][] = $this->getSearchString($namespace);
            $index['info'][] = array(self::TYPE_NAMESPACE, $namespace);
        }

        foreach ($project->getProjectClasses() as $class) {
            $index['searchIndex'][] = $this->getSearchString((string) $class);
            $index['info'][] = array(self::TYPE_CLASS, $class);
        }

        foreach ($project->getProjectClasses() as $class) {
            foreach ($class->getMethods() as $method) {
                $index['searchIndex'][] = $this->getSearchString((string) $method);
                $index['info'][] = array(self::TYPE_METHOD, $method);
            }
        }

        return $index;
    }

    protected function getSearchString($string)
    {
        return strtolower(preg_replace("/\s+/", '', $string));
    }
}
