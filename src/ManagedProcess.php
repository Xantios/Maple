<?php

namespace Maple;

use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ManagedProcess {

    private OutputInterface $output;
    private Process $process;
    private LoopInterface $loop;

    private string $prefix;

    public string $name;
    public string $status = ProcessStateManager::CREATED;
    public string $started_at;
    public string $command = '';

    public bool $autostart = false;

    public int $retries = 0;
    public int $currentRetry = 0;

    // Run after this task if defined
    public string $afterName = '';
    public ManagedProcess $afterInstance;

    public function __construct(array $config,OutputInterface $output,LoopInterface $loop) {

        $this->loop = $loop;
        $this->output = $output;

        $this->started_at = '';

        $this->autostart = $config['autostart'] ?? false;
        $this->retries = $config['retries'] ?? 0;

        $this->name = $this->safeName($config['name']);
        $this->prefix = str_pad(substr($this->name,0,25),26,' ');

        $this->command = $config['cmd'];
        $this->afterName = $config['after'] ?? '';
    }

    public function autostart() :bool {

        if($this->autostart !== true) {
            return false;
        }

        return $this->run();
    }

    public function run() :bool
    {
        $this->status = ProcessStateManager::CREATED;

        $this->process = new Process($this->command);
        $this->process->start($this->loop);

        // First attempt should be obvious
        if($this->currentRetry > 0) {
            $this->output->writeln($this->prefix . ' :: Running (Retry ' . $this->currentRetry . ')');
        }

        $this->status = ProcessStateManager::RUNNING;
        $this->started_at = (string)((new \DateTime())->getTimestamp()*1000); // JS uses ms instead of secs

        $this->process->stdout->on('data',function($chunk) {
            $this->printStdMsg($chunk);
        });

        $this->process->stderr->on('data',function($chunk) {
            $this->printStdMsg($chunk);
        });

        $this->process->on('exit',function($code,$termSig) {

            if($code !== 0) {
                $this->status = ProcessStateManager::CRASHED;
            } else {
                $this->status = ProcessStateManager::FINISHED;
            }

            // Find after hook when this process exits, because the runtime CAN change while running
            if($this->afterName) {

                $psm = ProcessStateManager::getInstance();
                $this->afterInstance = $psm->get($this->safeName($this->afterName));

                $this->output->writeln($this->prefix.' :: Exited Next => '.$this->afterInstance->name);

                $this->afterInstance->run();

                return;
            }

            $this->output->writeln($this->prefix.' :: Exited with status '.$code);

            if($this->retries === -1) {
                $this->currentRetry++;
                $this->run();
                return;
            }

            if($this->retries > 0 && $this->retries > $this->currentRetry) {
                $this->currentRetry++;
                $this->run();
            }
        });

        return true;
    }

    private function printStdMsg($chunk): void
    {
        $lines = explode(PHP_EOL,$chunk);

        foreach($lines as $line) {
            $this->output->writeln($this->prefix . ' :: ' . $line);
        }
    }

    private function safeName(string $name) :string {
        return strtolower(str_replace(' ','-',$name));
    }
}