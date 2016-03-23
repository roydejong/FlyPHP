<?php

namespace FlyPHP\Server;

use FlyPHP\Config\ServerConfigSection;
use FlyPHP\Http\TransactionHandler;
use FlyPHP\Runtime\Loop;
use FlyPHP\Runtime\Timer;
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
     * @param ServerConfigSection $configuration
     * @throws ListenerException
     */
    public function start(ServerConfigSection $configuration)
    {
        $this->registerSignals();

        $this->output->writeln("Starting server on port {$configuration->port}...");

        $this->listener = new Listener($configuration->port, $configuration->address, $configuration->backlog);

        $that = $this;
        $debugTimer = new Timer(3, true, function () use ($that) {
            $statistics = $that->loop->getStatistics();
            $statistics['memoryUsage'] = round(memory_get_usage(true) / 1000000, 2) . 'mb';

            echo PHP_EOL . http_build_query($statistics, '', ', ') . PHP_EOL;
        });
        $debugTimer->start($this->loop);

        $this->listener->listen($this->loop)
            ->then(function (Connection $connection) {
                $this->output->writeln('');
                $this->output->writeln('------------------------------------------- Incoming connection: ' . $connection);
                $this->output->writeln('');

                $handler = new TransactionHandler($connection);
                $handler->handle();
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