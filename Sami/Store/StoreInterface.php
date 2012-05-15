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

interface StoreInterface
{
    function readClass(Project $project, $name);

    function writeClass(Project $project, ClassReflection $class);

    function removeClass(Project $project, $name);

    function readProject(Project $project);

    function flushProject(Project $project);
}
