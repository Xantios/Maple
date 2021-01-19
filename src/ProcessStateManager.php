<?php

namespace Maple;

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

    private function __construct(OutputInterface $output,LoopInterface $loop)
    {
        $this->items = new Collection();

        $this->output = $output;
        $this->loop = $loop;
    }

    public static function getInstance($output = null,$loop = null): ProcessStateManager
    {
        if(!self::$instance) {
            self::$instance = new ProcessStateManager($output,$loop);
        }

        return self::$instance;
    }

    public function add(array $item) :bool {

        // Check for duplicates
        if($this->items->where('name',$item['name'])->count() > 0) {
            throw new \RuntimeException('Duplicate entry for '.$item['name'].' Please check your config file');
        }

        $this->items->push(new ManagedProcess($item,$this->output,$this->loop));
        $this->output->writeln('<info>Added task '.$item['name'].'</info>');

        return true;
    }

    public function all() :array {
        return $this->items->all();
    }

    public function get(string $name) {
        return $this->items->where('name',$name)->first();
    }

    public function autorun(): void
    {
        $items = $this->items->where('autostart',true);

        $items->each(function(ManagedProcess $process) {

            $run = $process->autostart();

            if($run) {
                $this->output->writeLn('<info>[OK]</info>  Autostarting '.$process->name);
            } else {
                $this->output->writeln('<error>[ERR]</error>Autostarting '.$process->name);
            }
        });
    }

}