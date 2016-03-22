<?php

namespace FlyPHP\Server;

/**
 * A timer, either with a set inteval or on a fixed timeout, that triggers a callback.
 */
class Timer
{
    /**
     * The amount of seconds in a microsecond.
     */
    const MICROSECONDS_PER_SECOND = 1000000;

    /**
     * The callback that will be invoked on timer completion.
     *
     * @var callable
     */
    private $callback;

    /**
     * Timer start timestamp.
     * Unix timestamp as a float, accurate to the nearest microsecond.
     *
     * @var float
     */
    private $startTime;

    /**
     * The timer run time or interval, in seconds.
     *
     * @var float
     */
    private $runTime;

    /**
     * Flag indicating whether the timer is currently running.
     *
     * @var bool
     */
    private $started;

    /**
     * Flag indicating whether the timer is a repeating interval timer.
     *
     * @var bool
     */
    private $repeating;

    /**
     * Timer constructor.
     *
     * @param callable $callback The callback.
     * @param float $interval Timer runtime or interval.
     * @param bool $repeating If true, set to a repeating interval timer. If false, a single runtime.
     */
    public function __construct(callable $callback, float $interval, bool $repeating = false)
    {
        $this->callback = $callback;
        $this->runTime = $interval;
        $this->repeating = $repeating;
    }

    /**
     * Ticks the timer, checking and triggering as needed.
     */
    public function tick()
    {
        if (!$this->started) {
            return;
        }

        $timeNow = microtime(true);
        $timeSince = $timeNow - $this->startTime;

        if ($timeSince >= $this->runTime) {
            // The run time has been exceeded, trigger the timer
            $this->trigger();

            if ($this->repeating) {
                // Repeating timer: reset the start time, accounting for any difference in time
                $extraTime = $timeSince - $this->runTime;
                $this->startTime = microtime(true) - $extraTime;
            } else {
                // Stop timer (single use)
                $this->stop();
            }
        }
    }

    /**
     * Starts, or restarts the timer.
     */
    public function start()
    {
        $this->started = true;
        $this->startTime = microtime(true);
    }

    /**
     * Stops the timer.
     */
    public function stop()
    {
        $this->started = false;
    }

    /**
     * Triggers the timer's completion event.
     */
    public function trigger()
    {
        call_user_func($this->callback, $this);
    }
}