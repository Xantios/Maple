<?php

namespace Xantios\Maple;

use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ManagedProcess {

    private OutputInterface $output;
    private LoopInterface $loop;
    private Process $process;
    private ProcessStateManager $psm;

    private string $prefix;

    public string $name;
    public string $status = ProcessStateManager::CREATED;
    public string $started_at;
    public string $updated_at;
    public string $command = '';

    public bool $autostart = false;

    public int $retries = 0;
    public int $currentRetry = 0;

    private array $log = [];
    private int $sizeLimit = 128;

    // Run after this task if defined
    public string $afterName = '';

    public function __construct(array $config,OutputInterface $output,LoopInterface $loop,ProcessStateManager $psm) {

        $this->loop         = $loop;
        $this->output       = $output;
        $this->psm          = $psm;

        $this->started_at   = '';
        $this->updated_at   = '';

        $this->autostart    = $config['autostart'] ?? false;
        $this->retries      = $config['retries'] ?? 0;

        $this->command      = $config['cmd'];
        $this->afterName    = $config['after'] ?? '';

        $this->name = $this->safeName($config['name']);
        $this->prefix = str_pad(substr($this->name,0,25),26,' ');

        $this->process = new Process($this->command);
    }

    public function autostart() :bool {

        if($this->autostart !== true) {
            return false;
        }

        return $this->run();
    }

    public function log(): array {
        return $this->log;
    }

    public function run() :bool
    {
        try {

            if($this->process->isRunning()) {
                $this->logMessage('Process is already running');
                return false;
            }

            $this->process->start($this->loop);
            $this->logMessage('Started');

        } catch (\Exception $e) {
            $this->output->writeln("<warn>We're having some trouble running {$this->name} ($this->command) message from OS: " . $e->getMessage()."</warn>");
        }

        // First attempt should be obvious
        if($this->currentRetry > 0) {
            $this->logMessage('Running (Retry ' . $this->currentRetry . ')');
        }

        $this->status = ProcessStateManager::RUNNING;
        $this->started_at = (string)((new \DateTime())->getTimestamp()*1000); // JS uses ms instead of secs

        $this->process->on('exit',function($code) {

            if($code !== 0) {
                $this->status = ProcessStateManager::CRASHED;
            } else {
                $this->status = ProcessStateManager::FINISHED;
            }

            // Clean up this process
            $this->kill();
            $this->process = new Process($this->command);

            if($this->afterName) {

                $afterInstance = $this->psm->get($this->safeName($this->afterName));

                $this->logMessage('Exited Next => '.$afterInstance->name);

                $afterInstance->run();

                return;
            }

            $this->logMessage('Exited with status '.$code);

            if($this->retries === -1) {
                $this->currentRetry++;
                $this->loop->addTimer(1,function() {
                    $this->run();
                });
                return;
            }

            if($this->retries > 0 && $this->retries > $this->currentRetry) {
                $this->currentRetry++;
                $this->loop->addTimer(2,function() {
                    $this->run();
                });
            }
        });

        $this->process->stdout->on('data',fn($chunk) => $this->logMessage($chunk,'stdout'));
        $this->process->stderr->on('data',fn($chunk) => $this->logMessage($chunk,'stderr'));

        $this->process->stdout->on('error',function(\Exception $e) {
            print "Exception in execution: ".$e->getMessage();
        });

        return true;
    }

    public function stop($signal = SIGTERM) :void {
        // Nuke it from orbit
        if(isset($this->process)) {

            foreach ($this->process->pipes as $pipe) {
                $pipe->close();
            }

            $this->process->terminate($signal);
        }
    }

    public function kill() :void {
        $this->stop(SIGKILL);
    }

    private function logMessage($chunk,$channel = 'system') :void {

        $channels = [
            'stdout' => '<info>stdout</info>',
            'stderr' => '<err>stderr</info>',
            'system' => '<info>system</info>'
        ];

        // Trim down before adding
        if( count($this->log) >= $this->sizeLimit) {
            while (count($this->log) >= $this->sizeLimit) {
                array_shift($this->log);
            }
        }

        $lines = explode(PHP_EOL,$chunk);

        foreach($lines as $line) {
            $this->log[] = [
                'channel' => $channel,
                'msg' => $line
            ];

            $this->output->writeln($this->prefix .$channels[$channel].' :: ' . $line);
        }
    }

    private function safeName(string $name) :string {
        return strtolower(str_replace(' ','-',$name));
    }
}