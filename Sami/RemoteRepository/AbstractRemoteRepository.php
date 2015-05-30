<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\RemoteRepository;

abstract class AbstractRemoteRepository
{
    protected $name;
    protected $localPath;

    public function __construct($name, $localPath)
    {
        $this->name = $name;
        $this->localPath = $localPath;
    }

    abstract public function getFileUrl($projectVersion, $relativePath, $line);

    public function getRelativePath($file)
    {
        $replacementCount = 0;
        $filePath = str_replace($this->localPath, '', $file, $replacementCount);

        if (1 === $replacementCount) {
            return $filePath;
        }

        return '';
    }
}
