<?php

namespace FlyPHP\Server\Timers;

use FlyPHP\Runtime\Loop;
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
    }

    /**
     * @inheritdoc
     */
    public function start(Loop $loop)
    {
        if (!$this->isSupported())
        {
            $this->server->getOutput()->writeln('<comment>Warning: Your system does not support pcntl signals.</comment>');
            return;
        }

        parent::start($loop);
    }

    /**
     * Gets whether the local system supports pcntl signal handlers.
     * Some platforms, e.g. Windows lack this functionality.
     * Additionally, the pcntl module is needed on other systems that do support it.
     *
     * @return bool
     */
    public function isSupported()
    {
        return function_exists('pcntl_signal');
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