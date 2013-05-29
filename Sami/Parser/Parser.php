<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Parser;

use Sami\Store\StoreInterface;
use Sami\Reflection\LazyClassReflection;
use Sami\Parser\Transaction;
use Sami\Project;
use Sami\Message;
use Symfony\Component\Finder\Finder;

class Parser
{
    protected $store;
    protected $iterator;
    protected $parser;
    protected $traverser;

    public function __construct($iterator, StoreInterface $store, CodeParser $parser, ClassTraverser $traverser)
    {
        $this->iterator = $this->createIterator($iterator);
        $this->store = $store;
        $this->parser = $parser;
        $this->traverser = $traverser;
    }

    public function parse(Project $project, $callback = null)
    {
        $step = 0;
        $steps = iterator_count($this->iterator);
        $context = $this->parser->getContext();
        $transaction = new Transaction($project);
        foreach ($this->iterator as $file) {
            ++$step;

            $code = file_get_contents($file);
            $hash = sha1($code);
            if ($transaction->hasHash($hash)) {
                continue;
            }

            $context->enterFile((string) $file, $hash);

            $this->parser->parse($code);

            if (null !== $callback) {
                call_user_func($callback, Message::PARSE_ERROR, $context->getErrors());
            }

            foreach ($context->leaveFile() as $class) {
                if (null !== $callback) {
                    call_user_func($callback, Message::PARSE_CLASS, array(floor($step / $steps * 100), $class));
                }

                $project->addClass($class);
                $transaction->addClass($class);
                $this->store->writeClass($project, $class);
            }
        }

        // cleanup
        foreach ($transaction->getRemovedClasses() as $class) {
            $project->removeClass(new LazyClassReflection($class));
            $this->store->removeClass($project, $class);
        }

        // visit each class for stuff that can only be done when all classes are parsed
        $modified = $this->traverser->traverse($project);
        foreach ($modified as $class) {
            $this->store->writeClass($project, $class);
        }

        return $transaction;
    }

    private function createIterator($iterator)
    {
        if (is_string($iterator)) {
            $it = new Finder();
            $it->files()->name('*.php')->in($iterator);

            return $it;
        } elseif (!$iterator instanceof \Traversable) {
            throw new \InvalidArgumentException('The iterator must be a directory name or a Finder instance.');
        }

        return $iterator;
    }
}
