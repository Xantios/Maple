<?php

namespace Maple;

use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessRunner {

    private array $config;

    private LoopInterface $loop;
    private OutputInterface $output;

    public function __construct($config,LoopInterface $loop,OutputInterface $output) {
        $this->config = $config;
        $this->loop = $loop;
        $this->output = $output;
    }

    public function run(): void
    {
        $manager = ProcessStateManager::getInstance($this->output,$this->loop);

        if($manager === null) {
            throw new \RuntimeException('Cant initialize ProcessStageManager');
        }

        // Add all available tasks
        foreach($this->config['tasks'] as $task) {
            $manager->add($task);
        }

        // Run all auto run
        $manager->autorun();
    }

}