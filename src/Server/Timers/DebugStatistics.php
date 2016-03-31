<?php

namespace FlyPHP\Server\Timers;

use FlyPHP\Runtime\Timer;
use FlyPHP\Server\Server;

/**
 * A timer that periodically prints debug information such as loop statistics, memory usage and transaction count.
 */
class DebugStatistics extends Timer
{
    /**
     * @var Server
     */
    private $server;

    /**
     * Initializes a new debug statistics timer.
     *
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        parent::__construct(3, true, $this);

        $this->server = $server;
    }

    /**
     * Magic method for when $this is called.
     * Acts as a callback for the timer.
     */
    public function __invoke()
    {
        $statistics = $this->server->getLoop()->getStatistics();
        $statistics['memoryUsage'] = round(memory_get_usage(true) / 1000000, 2) . 'mb';
        $statistics['transactions'] = count($this->server->getTransactions());

        echo  PHP_EOL . '[Stats] ' . http_build_query($statistics, '', ', ') . PHP_EOL;
    }
}