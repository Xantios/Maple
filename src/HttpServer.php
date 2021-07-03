<?php

namespace Xantios\Maple;

use Psr\Http\Message\ServerRequestInterface;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Http\Message\Response;
use React\Http\Server;
use Symfony\Component\Console\Output\OutputInterface;

class HttpServer {

    private int $port;
    private string $host;

    private LoopInterface $loop;
    private Server $server;
    private OutputInterface $output;

    public function __construct(array $config,LoopInterface $loop,OutputInterface $output) {

        $this->port = (isset($config['port'])) ? $config['port'] : '8100';
        $this->host = (isset($config['host'])) ? $config['host'] : '127.0.0.1';

        $this->loop = $loop;
        $this->output = $output;

        $psm = ProcessStateManager::getInstance();

        $this->server = new Server($this->loop,function(ServerRequestInterface $request) use($psm) {

            $route = $request->getUri()->getPath();

            // Serve out dashboard
            if($route === "/") {
                return new Response(200,[
                    'Content-Type' => 'text/html'
                ],file_get_contents(__DIR__.'/ui/index.html'));
            }

            if($route === "/api/processes") {
                return new Response(200,[
                    'Content-Type' => 'text/json'
                ], json_encode($psm->all(), JSON_THROW_ON_ERROR));
            }

            return new Response(404,[
                'Content-Type' => 'text/plain'
            ],'Unknown');

        });
    }

    public function run(): void
    {
        $socket = new \React\Socket\Server($this->port,$this->loop);
        $this->output->writeln('<info>Listening on '.$this->host.":".$this->port.'</info>');
        $this->server->listen($socket);
    }

}