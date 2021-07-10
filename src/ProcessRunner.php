<?php

namespace Xantios\Maple;

use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessRunner {

    private array $config;

    private LoopInterface $loop;
    private OutputInterface $output;
    private ProcessStateManager $manager;

    public function __construct($config,LoopInterface $loop,OutputInterface $output,ProcessStateManager $manager) {
        $this->config = $config;
        $this->loop = $loop;
        $this->output = $output;
        $this->manager = $manager;
    }

    public function run(): void
    {
        // Add all available tasks
        foreach($this->config['tasks'] as $task) {
            $this->manager->add($task,$this->manager);
        }

        // Run all auto run
        $this->manager->autorun();
    }
}