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

        $this->port = $config['port'] ?? '8100';
        $this->host = $config['host'] ?? '127.0.0.1';

        $this->loop = $loop;
        $this->output = $output;

        $psm = ProcessStateManager::getInstance($output,$loop);

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

            if(str_starts_with($route, "/api/log/")) {

                $name = explode('/',substr($route,1),3)[2];

                if($name==="") {
                    return new Response(404,[
                        'Content-Type' => 'text/json'
                    ], json_encode(['error' => true, 'msg' => 'Invalid name'], JSON_THROW_ON_ERROR));
                }

                return new Response(200,[
                    'Content-Type' => 'text/json'
                ], json_encode([
                    'name' => $name,
                    'log' => $psm->log($name)
                ], JSON_THROW_ON_ERROR));
            }

            return new Response(404,[
                'Content-Type' => 'text/plain'
            ],'Unknown');

        });
    }

    public function run(): void
    {
        $socket = new \React\Socket\Server($this->host.":".$this->port,$this->loop);
        $this->output->writeln('<info>Listening on '.$this->host.":".$this->port.'</info>');
        $this->server->listen($socket);
    }
}