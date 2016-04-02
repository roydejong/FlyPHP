<?php

namespace FlyPHP\Runtime;

/**
 * An asynchronous loop based on event polling, which allows the server to operate in a synchronous, non-blocking way.
 * This implementation is based on stream select.
 */
class Loop
{
    /**
     * Flag that indicates whether the loop is currently running or not.
     *
     * @var bool
     */
    private $running = false;

    /**
     * A resource of monitored read streams.
     *
     * @var resource[]
     */
    private $readStreams = [];

    /**
     * A list of callbacks, indexed by resource ids.
     *
     * @var callable[]
     */
    private $readStreamsCallbacks = [];

    /**
     * A resource of monitored write streams.
     *
     * @var array
     */
    private $writeStreams = [];

    /**
     * A list of callbacks, indexed by resource ids.
     *
     * @var callable[]
     */
    private $writeStreamsCallbacks = [];

    /**
     * A list of timers.
     *
     * @var Timer[]
     */
    private $timers = [];

    /**
     * Adds a read stream to the loop, and await it to be readable and non-blocking.
     *
     * @param resource $readStream
     * @param callable $callback
     */
    public function awaitReadable($readStream, callable $callback)
    {
        $resourceId = intval($readStream);

        $this->readStreams[$resourceId] = $readStream;
        $this->readStreamsCallbacks[$resourceId][] = $callback;
    }

    /**
     * Adds a read stream to the loop, and await it to be writable and non-blocking.
     *
     * @param resource $writeStream
     * @param callable $callback
     */
    public function awaitWritable($writeStream, callable $callback)
    {
        $resourceId = intval($writeStream);

        $this->writeStreams[$resourceId] = $writeStream;
        $this->writeStreamsCallbacks[$resourceId][] = $callback;
    }

    /**
     * Completely removes a given a $stream from both read and write monitoring, as well as its registered callbacks.
     *
     * @param resource $stream
     */
    public function removeStream($stream)
    {
        $resourceId = intval($stream);

        if (isset($this->writeStreams[$resourceId])) {
            unset($this->writeStreams[$resourceId]);
            unset($this->writeStreamsCallbacks[$resourceId]);
        }

        if (isset($this->readStreams[$resourceId])) {
            unset($this->readStreams[$resourceId]);
            unset($this->readStreamsCallbacks[$resourceId]);
        }
    }

    /**
     * Adds a timer to the loop.
     *
     * @param Timer $timer
     */
    public function addTimer(Timer $timer)
    {
        $key = array_search($timer, $this->timers);

        if ($key === false) {
            $this->timers[] = $timer;
        }
    }

    /**
     * Removes a timer from the loop.
     *
     * @param Timer $timer
     */
    public function removeTimer(Timer $timer)
    {
        $key = array_search($timer, $this->timers);

        if ($key !== false) {
            unset($this->timers[$key]);
        }
    }

    /**
     * Returns an array containing loop statistics.
     *
     * The array contains the following values:
     *  -
     *
     * @return array
     */
    public function getStatistics()
    {
        return [
            'running' => $this->running,
            'timers' => count($this->timers),
            'readStreams' => count($this->readStreams),
            'writeStreams' => count($this->writeStreams)
        ];
    }

    /**
     * Cycles the event loop.
     */
    public function cycle()
    {
        foreach ($this->timers as $timer) {
            $timer->tick();
        }

        $this->pollStreams($this->readStreams, $this->writeStreams);
    }

    /**
     * Starts running the loop.
     *
     * @return bool False if the loop has not started, true if the loop has completed.
     */
    public function run()
    {
        if ($this->running) {
            return false;
        }

        $this->running = true;

        while ($this->running) {
            $this->cycle();

            // Prevent hogging the CPU to 100% if the loop isn't doing anything
            usleep(1);
        }

        return true;
    }

    /**
     * Polls a collection of $readStreams and $writeStreams for state changes, then returns the amount of changed
     * streams for the duration of a given $timeout.
     *
     * @param array $readStreams Read streams to monitor.
     * @param array $writeStreams Write streams to monitor.
     * @param int $uTimeout Timeout in microseconds. If set to 0, this function will block indefinitely.
     */
    private function pollStreams(array $readStreams, array $writeStreams, int $uTimeout = 200000)
    {
        if (empty($readStreams) && empty($writeStreams)) {
            return;
        }

        // Due to a limitation in the current Zend Engine it is not possible to pass a constant modifier
        // like NULL directly as a parameter to a function which expects this parameter to be passed by reference.
        $except = null;
        $selectValue = @stream_select($readStreams, $writeStreams, $except, 0, $uTimeout);

        if ($selectValue === false || $selectValue <= 0) {
            // Either no streams have changed (0), or the select call was interrupted (false)
            return;
        }

        // The stream_select function has modified the $readStreams and $writeStreams arrays to only contain the
        // streams that have actually changed at this point. Iterate those and invoke callbacks.
        foreach ($readStreams as $stream) {
            $streamId = intval($stream);
            $callbacks = isset($this->readStreamsCallbacks[$streamId]) ? $this->readStreamsCallbacks[$streamId] : [];

            foreach ($callbacks as $callback) {
                $callback($stream);
            }
        }

        foreach ($writeStreams as $stream) {
            $streamId = intval($stream);
            $callbacks = isset($this->writeStreamsCallbacks[$streamId]) ? $this->writeStreamsCallbacks[$streamId] : [];

            foreach ($callbacks as $callback) {
                $callback($stream);
            }
        }
    }

    /**
     * Prevents the loop from continuing.
     */
    public function stop()
    {
        $this->running = false;
    }
}