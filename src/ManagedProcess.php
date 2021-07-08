<?php

namespace Xantios\Maple;

use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ManagedProcess {

    private OutputInterface $output;
    private LoopInterface $loop;
    private Process $process;

    private string $prefix;

    public string $name;
    public string $status = ProcessStateManager::CREATED;
    public string $started_at;
    public string $command = '';

    public bool $autostart = false;

    public int $retries = 0;
    public int $currentRetry = 0;

    private array $log = [];
    private int $sizeLimit = 128;

    // Run after this task if defined
    public string $afterName = '';

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

        $this->process = new Process($this->command);
    }

    public function autostart() :bool {

        if($this->autostart !== true) {
            return false;
        }

        return $this->run();
    }

    public function log(): array
    {
        return $this->log;
    }

    public function run() :bool
    {
        $this->process->start($this->loop);

        // First attempt should be obvious
        if($this->currentRetry > 0) {
            $this->output->writeln($this->prefix . ' :: Running (Retry ' . $this->currentRetry . ')');
        }

        $this->status = ProcessStateManager::RUNNING;
        $this->started_at = (string)((new \DateTime())->getTimestamp()*1000); // JS uses ms instead of secs

        $this->process->on('exit',function($code) {

            if($code !== 0) {
                $this->status = ProcessStateManager::CRASHED;
            } else {
                $this->status = ProcessStateManager::FINISHED;
            }

            if($this->afterName) {

                $psm = ProcessStateManager::getInstance();
                $afterInstance = $psm->get($this->safeName($this->afterName));

                $this->output->writeln($this->prefix.' :: Exited Next => '.$afterInstance->name);

                $afterInstance->run();

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

        $this->process->stdout->on('data',function($chunk) {
            $this->printStdMsg($chunk);
            $this->addLogMessage($chunk,'stdout');
        });

        $this->process->stdout->on('error',function(\Exception $e) {
            print "Exception in execution: ".$e->getMessage();
        });

        $this->process->stderr->on('data',function($chunk) {
            $this->printStdMsg($chunk);
            $this->addLogMessage($chunk,'stderr');
        });

        return true;
    }

    public function stop() :void {
        // Kill it with fire
        if(isset($this->process)) {
            $this->process->terminate(SIGTERM);
        }
    }

    public function kill() :void {
        // Nuke it from orbit
        if(isset($this->process)) {

            foreach ($this->process->pipes as $pipe) {
                $pipe->close();
            }

            $this->process->terminate(SIGKILL);
        }
    }

    private function addLogMessage($chunk,$channel) :void {
        
        if( count($this->log) >= $this->sizeLimit) {
            print "==> Trimming log for ".$this->name.PHP_EOL;
        }

        // Trim down before adding
        while(count($this->log) >= $this->sizeLimit) {
            array_shift($this->log);
        }

        $lines = explode(PHP_EOL,$chunk);

        foreach($lines as $line) {
            $this->log[] = [
                'channel' => $channel,
                'msg' => $line
            ];
        }
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