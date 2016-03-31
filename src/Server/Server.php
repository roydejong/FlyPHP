<?php

namespace FlyPHP\Server;

use FlyPHP\Config\ServerConfigSection;
use FlyPHP\Http\Compression\CompressionNegotiator;
use FlyPHP\Http\TransactionHandler;
use FlyPHP\Runtime\Loop;
use FlyPHP\Runtime\Timer;
use FlyPHP\Server\Timers\DebugStatistics;
use FlyPHP\Server\Timers\PcntlSignals;
use FlyPHP\Server\Timers\TransactionTicker;
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
     * @var CompressionNegotiator
     */
    private $compressionNegotiator;

    /**
     * Initializes a new server process.
     */
    public function __construct()
    {
        $this->loop = new Loop();
        $this->output = new DummyOutput();
        $this->transactions = [];
        $this->compressionNegotiator = new CompressionNegotiator();
    }

    /**
     * Gets the server event loop.
     *
     * @return Loop
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * Gets an array of currently active transactions.
     *
     * @return TransactionHandler[]
     */
    public function getTransactions()
    {
        return $this->transactions;
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
        // Load configuration and begin accepting incoming connections
        $this->reloadConfig($configuration);

        $this->output->writeln("Starting server on port {$configuration->port}...");

        $this->listener = new Listener($configuration->port, $configuration->address, $configuration->backlog);
        $this->listener->listen($this->loop)
            ->then(function (Connection $connection) {
                $this->handleIncomingConnection($connection);
            });

        // Start core timers
        (new DebugStatistics($this))->start($this->loop);
        (new TransactionTicker($this))->start($this->loop);
        (new PcntlSignals($this))->start($this->loop);

        // Finally, begin running our process loop
        $this->loop->run();

        $this->output->writeln("Event loop stopped.");
    }

    /**
     * (Re)loads the server configuration data.
     *
     * @param ServerConfigSection $configSection
     */
    public function reloadConfig(ServerConfigSection $configSection)
    {
        $this->configuration = $configSection;
        $this->compressionNegotiator->load($configSection);
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
        $transaction->setChunkedEncoding($this->configuration->chunkedEnabled, $this->configuration->chunkedMaxSize);
        $transaction->handle();

        $this->transactions[] = $transaction;
    }

    /**
     * @return CompressionNegotiator
     */
    public function getCompressionNegotiator()
    {
        return $this->compressionNegotiator;
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

    /**
     * Handles a PCNTL signal.
     *
     * @param int $signal The received pcntl signal
     */
    public function handleSignal($signal)
    {
        $this->output->writeln('');
        $this->output->writeln("<comment>Received signal {$signal}</comment>");

        switch ($signal) {
            case SIGKILL:
            case SIGINT:
            case SIGTERM:

                $this->stop();
                break;

            default:

                $this->output->writeln('<error>Unknown pctnl signal received</error>');
        }
    }
}