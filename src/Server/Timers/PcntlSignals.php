<?php

namespace FlyPHP\Server\Timers;

use FlyPHP\Runtime\Timer;
use FlyPHP\Server\Server;

/**
 * Timer that processes pcntl signals.
 */
class PcntlSignals extends Timer
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
        parent::__construct(0.25, true, $this);

        $this->server = $server;

        $this->registerSignals();
    }

    /**
     * Registers pcntl signals.
     */
    private function registerSignals()
    {
        $server = $this->server;

        $handler = function ($signal) use ($server) {
            $server->handleSignal($signal);
        };

        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGTERM, $handler);
    }

    /**
     * Magic method for when $this is called.
     * Acts as a callback for the timer.
     */
    public function __invoke()
    {
        // Call signal handlers for pending signals (force callback trigger)
        pcntl_signal_dispatch();
    }
}