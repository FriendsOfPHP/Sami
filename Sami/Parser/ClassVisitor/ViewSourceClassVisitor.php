<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Parser\ClassVisitor;

use Sami\Reflection\ClassReflection;
use Sami\Parser\ClassVisitorInterface;
use Sami\RemoteRepository\AbstractRemoteRepository;

class ViewSourceClassVisitor implements ClassVisitorInterface
{
    /** @var AbstractRemoteRepository */
    protected $remoteRepository;

    public function __construct(AbstractRemoteRepository $remoteRepository)
    {
        $this->remoteRepository = $remoteRepository;
    }

    public function visit(ClassReflection $class)
    {
        $filePath = $this->remoteRepository->getRelativePath($class->getFile());

        if ($class->getRelativeFilePath() != $filePath) {
            $class->setRelativeFilePath($filePath);

            return true;
        }

        return false;
    }
}
