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

use Sami\Project;
use Sami\Reflection\ClassReflection;

interface StoreInterface
{
    public function readClass(Project $project, $name);

    public function writeClass(Project $project, ClassReflection $class);

    public function removeClass(Project $project, $name);

    public function readProject(Project $project);

    public function flushProject(Project $project);
}
