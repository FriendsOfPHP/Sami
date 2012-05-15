<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Sami\Console\Command\UpdateCommand;
use Sami\Console\Command\ParseCommand;
use Sami\Console\Command\RenderCommand;
use Sami\Sami;
use Sami\ErrorHandler;

class Application extends BaseApplication
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        error_reporting(-1);
        ErrorHandler::register();

        parent::__construct('Sami', Sami::VERSION);

        $this->add(new UpdateCommand());
        $this->add(new ParseCommand());
        $this->add(new RenderCommand());
    }

    public function getLongVersion()
    {
        return parent::getLongVersion().' by <comment>Fabien Potencier</comment>';
    }
}
