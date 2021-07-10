<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use Symfony\Component\Console\Output\ConsoleOutput;
use Xantios\Maple\ProcessStateManager;

final class ProcessStateManagerTest extends TestCase {

    private ProcessStateManager $manager;
    private $loop;

    public static function setUpBeforeClass() :void {
        require __DIR__.'/util/testOut.php';
        testOut('Loaded output util');
    }

    public function setUp() :void {

        $this->loop = Factory::create();
        $output = new ConsoleOutput();

        $this->manager = new ProcessStateManager($output,$this->loop);
    }

    public function test_singleton_functionality() :void {
        $this->assertIsArray($this->manager->all());
        $this->assertCount(0, $this->manager->all());
    }

    public function test_adding_a_new_process_without_name_fails() :void {
        $this->expectException('RuntimeException');
        $this->manager->add([],$this->manager);
    }

    public function test_adding_a_new_process_without_cmd_fails() :void {
        $this->expectException('RuntimeException');
        $this->manager->add(['name'=>'example'],$this->manager);
    }

    public function test_adding_a_new_process_twice_fails() :void {
        $this->expectException('LogicException');
        $this->manager->add(['name'=> 'unit_test','cmd' => "sleep 1"],$this->manager);
        $this->manager->add(['name'=> 'unit_test','cmd' => "sleep 1"],$this->manager);
    }

    public function test_completing_a_task() :void {

        $this->loop->futureTick(function() {

            $item = $this->manager->add([
                'name' => 'finish-test',
                'cmd' => ' sleep 2',
            ],$this->manager);

            $item->run();
        });

        $this->loop->addTimer(3,function() {
            $this->loop->stop();
            $this->assertEquals(ProcessStateManager::FINISHED,$this->manager->get('finish-test')->status);
        });

        // Run loop
        $this->loop->run();
    }
}