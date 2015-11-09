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

class GitLabRemoteRepository extends AbstractRemoteRepository
{
    protected $url = 'https://gitlab.com/';

    public function __construct($name, $localPath, $url = '')
    {
        if (!empty($url)) {
            $this->url = $url;
        }

        parent::__construct($name, $localPath);
    }

    public function getFileUrl($projectVersion, $relativePath, $line)
    {
        $url = $this->url.$this->name.'/blob/'.str_replace('\\', '/', $projectVersion.$relativePath);

        if (null !== $line) {
            $url .= '#L'.(int) $line;
        }

        return $url;
    }
}
