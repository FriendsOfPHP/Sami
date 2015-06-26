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

class GitHubRemoteRepository extends AbstractRemoteRepository
{
    public function getFileUrl($projectVersion, $relativePath, $line)
    {
        $url = 'https://github.com/'.$this->name.'/blob/'.str_replace('\\', '/', $projectVersion.$relativePath);

        if (null !== $line) {
            $url .= '#L'.(int) $line;
        }

        return $url;
    }
}
