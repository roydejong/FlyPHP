<?php

namespace FlyPHP\Server\Timers;

use FlyPHP\Runtime\Timer;
use FlyPHP\Server\Server;

/**
 * Timer that ticks server transactions.
 * This is needed to perform connection timeouts, cleanup, etc.
 */
class TransactionTicker extends Timer
{
    /**
     * @var Server
     */
    private $server;

    /**
     * Initializes a new transaction ticker timer.
     *
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        parent::__construct(1, true, $this);

        $this->server = $server;
    }

    /**
     * Magic method for when $this is called.
     * Acts as a callback for the timer.
     */
    public function __invoke()
    {
        $transactions = $this->server->getTransactions();

        foreach ($transactions as $transaction) {
            $transaction->tick();
        }
    }
}