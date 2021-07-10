<?php

namespace Xantios\Maple;

use React\EventLoop\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command {

    protected static $defaultName = 'run';

    protected function configure() :void {
        $this
            ->setDescription('Run Maple task runner')
            ->addOption('config','c',InputOption::VALUE_OPTIONAL,'Path to config file','');
    }

    protected function execute(InputInterface $input, OutputInterface $output) :int
    {
        $configPath = $this->resolveConfigPath($input->getOption('config'));

        if(!$configPath) {
            throw new \Exception('Please make sure there is a maple-config.php file in '.getcwd().' or specify a config path','1');
        }

        $output->writeln("<comment>Found config at ${configPath} </comment>");
        $config = include $configPath;

        // Create React loop
        $loop = Factory::create();

        // Create ProcessStateManager
        $psm = new ProcessStateManager($output,$loop);

        // Setup ProcessRunner
        $proc = new ProcessRunner($config,$loop,$output,$psm);
        $proc->run();

        // Setup Http server
        $http = new HttpServer($config,$loop,$output,$psm);
        $http->run();

        // Run async loop manager thingy
        $loop->run();

        return 0;
    }

    private function resolveConfigPath(string $inputHint) :string|false {

        if($inputHint === '' && file_exists(getcwd().DIRECTORY_SEPARATOR.'maple-config.php')) {
            return getcwd().DIRECTORY_SEPARATOR.'maple-config.php';
        }

        if($inputHint !== '' && file_exists($inputHint)) {
            return $inputHint;
        }

        if(strlen($inputHint) > 2 && $inputHint[0]===DIRECTORY_SEPARATOR && file_exists(getcwd().$inputHint)) {
            return getcwd().$inputHint;
        }

        if(strlen($inputHint) >2 && file_exists(getcwd().DIRECTORY_SEPARATOR.$inputHint)) {
            return getcwd().DIRECTORY_SEPARATOR.$inputHint;
        }

        return false;
    }
}