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
     * A list of currently active transactions.
     *
     * @var TransactionHandler[]
     */
    private $transactions;

    /**
     * The server configuration settings.
     *
     * @var ServerConfigSection
     */
    private $configuration;

    /**
     * Initializes a new server process.
     */
    public function __construct()
    {
        $this->loop = new Loop();
        $this->output = new DummyOutput();
        $this->transactions = [];
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
        $that = $this;

        // Register process control signals (CTRL+C etc) to handle graceful shutdown
        $this->registerSignals();

        // Load configuration and begin accepting incoming connections
        $this->configuration = $configuration;
        $this->output->writeln("Starting server on port {$configuration->port}...");

        $this->listener = new Listener($configuration->port, $configuration->address, $configuration->backlog);
        $this->listener->listen($this->loop)
            ->then(function (Connection $connection) {
                $this->handleIncomingConnection($connection);
            });

        // Start the debug timer, using this during development to make sure we're not being too leaky
        $debugTimer = new Timer(3, true, function () use ($that) {
            $statistics = $that->loop->getStatistics();
            $statistics['memoryUsage'] = round(memory_get_usage(true) / 1000000, 2) . 'mb';
            $statistics['transactions'] = count($that->transactions);

            echo PHP_EOL . http_build_query($statistics, '', ', ') . PHP_EOL;
        });
        $debugTimer->start($this->loop);

        // Start the transaction ticker, which handles transaction cleanup and timeouts
        $transactionTicker = new Timer(1, true, function () use ($that) {
            foreach ($that->transactions as $transaction) {
                $transaction->tick();
            }
        });
        $transactionTicker->start($this->loop);

        // Finally, begin running our process loop
        $this->loop->run();
        $that->output->writeln("The server process loop has ended.");
    }

    /**
     * Handles a new incoming connection.
     *
     * @param Connection $connection
     */
    private function handleIncomingConnection(Connection $connection)
    {
        $this->output->writeln('');
        $this->output->writeln('------------------------------------------- Incoming connection: ' . $connection);
        $this->output->writeln('');

        $transaction = new TransactionHandler($this, $connection);
        $transaction->setKeepAlive($this->configuration->keepAliveTimeout > 0, $this->configuration->keepAliveTimeout,
            $this->configuration->keepAliveLimit);
        $transaction->handle();

        $this->transactions[] = $transaction;
    }

    /**
     * Removes a $transaction from the managed pool.
     *
     * @param TransactionHandler $transaction
     */
    public function endTransaction(TransactionHandler $transaction)
    {
        $key = array_search($transaction, $this->transactions);

        if (isset($this->transactions[$key])) {
            unset($this->transactions[$key]);
        }
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

        // Shut down the main event loop, so no more read/write operations or timers will be fired
        $this->loop->stop();

        // End all transactions and disconnect all clients
        foreach ($this->transactions as $transaction) {
            $transaction->getConnection()->disconnect();
            $transaction->tick();
        }

        // Hopefully we've shut down okay
    }
}