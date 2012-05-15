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

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Message
{
    const PARSE_ERROR             = 1;
    const PARSE_CLASS             = 2;
    const PARSE_VERSION_FINISHED  = 3;
    const RENDER_PROGRESS         = 4;
    const RENDER_VERSION_FINISHED = 5;
    const SWITCH_VERSION          = 6;
}
