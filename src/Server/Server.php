<?php

namespace FlyPHP\Server;
use FlyPHP\Http\Response;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;

/**
 * Main class for the FlyPHP HTTP server.
 */
class Server
{
    /**
     * @var Loop
     */
    private $loop;

    /**
     * @var Listener
     */
    private $listener;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Initializes a new server process.
     */
    public function __construct()
    {
        $this->loop = new Loop();
        $this->output = new DummyOutput();
    }

    /**
     * Registers the output interface for this server process.
     *
     * @param OutputInterface $output
     */
    public function registerOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Starts the server, starts the loop, begins listening for connections, and
     *
     * @param int $port
     * @throws ListenerException
     */
    public function start($port = 8080)
    {
        $this->registerSignals();

        $this->output->writeln("Starting server on port {$port}...");

        $this->listener = new Listener($port);

        $this->listener->listen($this->loop)
            ->then(function (Connection $connection) {
                $this->output->writeln('Incoming connection: ' . $connection);

                $response = new Response();
                $response->setBody('Hello world!');
                $response->send($connection);

                $connection->disconnect();
            });

        $this->loop->run();
    }

    /**
     * Registers server process signals.
     */
    private function registerSignals()
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        // ...
    }

    /**
     * Attempts to stop the server.
     * This is an asynchronous operation that should result in the process shutting down within a few seconds.
     */
    public function stop()
    {
        $this->output->writeln("Shutting down server.");

        $this->loop->stop();
    }
}