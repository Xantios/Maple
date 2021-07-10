<?php

namespace Xantios\Maple;

use Illuminate\Support\Collection;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessStateManager {

    private static ?ProcessStateManager $instance = null;

    private Collection $items;
    private OutputInterface $output;
    private LoopInterface $loop;

    public const CREATED = 'Created';
    public const RUNNING = 'Running';
    public const CRASHED = 'Crashed';
    public const FINISHED = 'Finished';

    public function __construct(OutputInterface $output,LoopInterface $loop)
    {
        $this->items = new Collection();

        $this->output = $output;
        $this->loop = $loop;
    }

    public function add(array $item,ProcessStateManager $processStateManagerInstance) :ManagedProcess {

        // Check for a name key
        if(!isset($item['name'])) {
            throw new \RuntimeException('Missing name');
        }

        // Check for command
        if(!isset($item['cmd'])) {
            throw new \RuntimeException('Missing command');
        }

        // Check for duplicates
        if($this->items->where('name',$item['name'])->count() > 0) {
            throw new \LogicException('Duplicate entry for '.$item['name'].' Please check your config file');
        }

        $process = new ManagedProcess($item,$this->output,$this->loop,$processStateManagerInstance);
        $this->items->push($process);
        $this->output->writeln('<info>Added task '.$item['name'].'</info>');

        return $process;
    }

    public function all() :array {
        return $this->items->all();
    }

    public function get(string $name) :ManagedProcess|null {
        return $this->items->where('name',$name)->first();
    }

    public function log(string $name) :array {
        $item = $this->items->where('name',$name)->first();
        return $item->log();
    }

    public function autorun(): void
    {
        $items = $this->items->where('autostart',true);

        $items->each(function(ManagedProcess $process) {

            $run = $process->autostart();

            if(!$run) {
                $this->output->writeln('<error>[ERR]</error>Autostarting '.$process->name);
                return;
            }

            $this->output->writeLn('<info>[OK]</info>  Autostarting '.$process->name);
        });
    }

}