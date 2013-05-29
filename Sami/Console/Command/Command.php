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

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Sami\Sami;
use Sami\Project;
use Sami\Parser\Transaction;
use Sami\Renderer\Diff;
use Sami\Message;

abstract class Command extends BaseCommand
{
    protected $sami;
    protected $version;
    protected $started;
    protected $diffs = array();
    protected $transactions = array();
    protected $errors = array();
    protected $input;
    protected $output;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this->getDefinition()->addArgument(
            new InputArgument('config', InputArgument::REQUIRED, 'The configuration'),
            new InputOption('version', '', InputOption::VALUE_REQUIRED, 'The version to build')
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $config = $input->getArgument('config');
        $filesystem = new Filesystem();

        if (!$filesystem->isAbsolutePath($config)) {
            $config = getcwd().'/'.$config;
        }

        if (!file_exists($config)) {
            throw new \InvalidArgumentException(sprintf('Configuration file "%s" does not exist.', $config));
        }

        $this->sami = require $config;

        if ($input->getOption('version')) {
            $this->sami['version'] = $input->getOption('version');
        }

        if (!$this->sami instanceof Sami) {
            throw new \RuntimeException(sprintf('Configuration file "%s" must return a Sami instance.', $config));
        }
    }

    public function update(Project $project)
    {
        $callback = $this->output->isDecorated() ? array($this, 'messageCallback') : null;

        $project->update($callback, $this->input->getOption('force'));

        $this->displayParseSummary();
        $this->displayRenderSummary();
    }

    public function parse(Project $project)
    {
        $callback = $this->output->isDecorated() ? array($this, 'messageCallback') : null;

        $project->parse($callback, $this->input->getOption('force'));

        $this->displayParseSummary();
    }

    public function render(Project $project)
    {
        $callback = $this->output->isDecorated() ? array($this, 'messageCallback') : null;

        $project->render($callback, $this->input->getOption('force'));

        $this->displayRenderSummary();
    }

    public function messageCallback($message, $data)
    {
        switch ($message) {
            case Message::PARSE_CLASS:
                list($progress, $class) = $data;
                $this->displayParseProgress($progress, $class);
                break;
            case Message::PARSE_ERROR:
                $this->errors = array_merge($this->errors, $data);
                break;
            case Message::SWITCH_VERSION:
                $this->version = $data;
                $this->errors = array();
                $this->started = false;
                $this->displaySwitch();
                break;
            case Message::PARSE_VERSION_FINISHED:
                $this->transactions[(string) $this->version] = $data;
                $this->displayParseEnd($data);
                $this->started = false;
                break;
            case Message::RENDER_VERSION_FINISHED:
                $this->diffs[(string) $this->version] = $data;
                $this->displayRenderEnd($data);
                $this->started = false;
                break;
            case Message::RENDER_PROGRESS:
                list ($section, $message, $progression) = $data;
                $this->displayRenderProgress($section, $message, $progression);
                break;
        }
    }

    public function renderProgressBar($percent, $length)
    {
        return
            str_repeat('#', floor($percent / 100 * $length))
            .sprintf(' %d%%', $percent)
            .str_repeat(' ', $length - floor($percent / 100 * $length))
        ;
    }

    public function displayParseProgress($progress, $class)
    {
        if ($this->started) {
            $this->output->write("\033[2A");
        }
        $this->started = true;

        $this->output->write(sprintf(
            "  Parsing <comment>%s</comment>%s\033[K\n          %s\033[K\n",
            $this->renderProgressBar($progress, 50), count($this->errors) ? ' <fg=red>'.count($this->errors).' error'.(1 == count($this->errors) ? '' : 's').'</>' : '', $class->getName())
        );
    }

    public function displayRenderProgress($section, $message, $progression)
    {
        if ($this->started) {
            $this->output->write("\033[2A");
        }
        $this->started = true;

        $this->output->write(sprintf(
            "  Rendering <comment>%s</comment>\033[K\n            <info>%s</info> %s\033[K\n",
            $this->renderProgressBar($progression, 50), $section, $message
        ));
    }

    public function displayParseEnd(Transaction $transaction)
    {
        if (!$this->started) {
            return;
        }

        $this->output->write(sprintf("\033[2A<info>  Parsing   done</info>\033[K\n\033[K\n\033[1A", count($this->errors) ? ' <fg=red>'.count($this->errors).' errors</>' : ''));

        if ($this->input->getOption('verbose') && count($this->errors)) {
            foreach ($this->errors as $error) {
                $this->output->write(sprintf("<fg=red>ERROR</>: "));
                $this->output->writeln($error, OutputInterface::OUTPUT_RAW);
            }
            $this->output->writeln('');
        }
    }

    public function displayRenderEnd(Diff $diff)
    {
        if (!$this->started) {
            return;
        }

        $this->output->write("\033[2A<info>  Rendering done</info>\033[K\n\033[K\n\033[1A");
    }

    public function displayParseSummary()
    {
        if (count($this->transactions) <= 0) {
            return;
        }
        
        $this->output->writeln('');
        $this->output->writeln('<bg=cyan;fg=white> Version </>  <bg=cyan;fg=white> Updated C </>  <bg=cyan;fg=white> Removed C </>');

        foreach ($this->transactions as $version => $transaction) {
            $this->output->writeln(sprintf('%9s  %11d  %11d', $version, count($transaction->getModifiedClasses()), count($transaction->getRemovedClasses())));
        }
        $this->output->writeln('');
    }

    public function displayRenderSummary()
    {
        if (count($this->diffs) <= 0) {
            return;
        }
        
        $this->output->writeln('<bg=cyan;fg=white> Version </>  <bg=cyan;fg=white> Updated C </>  <bg=cyan;fg=white> Updated N </>  <bg=cyan;fg=white> Removed C </>  <bg=cyan;fg=white> Removed N </>');

        foreach ($this->diffs as $version => $diff) {
            $this->output->writeln(sprintf('%9s  %11d  %11d  %11d  %11d', $version,
                count($diff->getModifiedClasses()),
                count($diff->getModifiedNamespaces()),
                count($diff->getRemovedClasses()),
                count($diff->getRemovedNamespaces())
            ));
        }
        $this->output->writeln('');
    }

    public function displaySwitch()
    {
        $this->output->writeln(sprintf("\n<fg=cyan>Version %s</>", $this->version));
    }
}
