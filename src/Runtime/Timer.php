<?php

namespace FlyPHP\Runtime;

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
    protected $callback;

    /**
     * Timer start timestamp.
     * Unix timestamp as a float, accurate to the nearest microsecond.
     *
     * @var float
     */
    protected $startTime;

    /**
     * The timer run time or interval, in seconds.
     *
     * @var float
     */
    protected $runTime;

    /**
     * Flag indicating whether the timer is currently running.
     *
     * @var bool
     */
    protected $started;

    /**
     * Flag indicating whether the timer is a repeating interval timer.
     *
     * @var bool
     */
    protected $repeating;

    /**
     * The loop instance this timer is running on.
     *
     * @var Loop
     */
    protected $loop;

    /**
     * Timer constructor.
     *
     * @param float $interval Timer runtime or interval.
     * @param bool $repeating If true, set to a repeating interval timer. If false, a single runtime.
     * @param callable $callback The callback.
     */
    public function __construct(float $interval, bool $repeating, callable $callback)
    {
        $this->runTime = $interval;
        $this->repeating = $repeating;
        $this->callback = $callback;
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
     *
     * @param Loop $loop The loop to run this timer on.
     */
    public function start(Loop $loop)
    {
        $this->started = true;
        $this->startTime = microtime(true);

        $this->loop = $loop;
        $this->loop->addTimer($this);
    }

    /**
     * Stops the timer.
     */
    public function stop()
    {
        if ($this->started)
        {
            $this->started = false;
            $this->loop->removeTimer($this);
        }

    }

    /**
     * Triggers the timer's completion event.
     */
    public function trigger()
    {
        call_user_func($this->callback, $this);
    }
}