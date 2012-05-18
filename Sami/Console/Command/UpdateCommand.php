<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{
    /**
     * @see Command
     */
    protected function configure()
    {
        parent::configure();

        $this->getDefinition()->addOption(new InputOption('force', '', InputOption::VALUE_NONE, 'Forces to rebuild from scratch', null));

        $this
            ->setName('update')
            ->setDescription('Parses then renders a project')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command parses and renders a project:

    <info>php %command.full_name% config/symfony.php parses</info>

This command is the same as running the parse command followed
by the render command.
EOF
            );
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When the target directory does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<bg=cyan;fg=white> Updating project </>');

        $this->update($this->sami['project']);
    }
}
