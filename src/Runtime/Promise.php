<?php

namespace FlyPHP\Runtime;

/**
 * Represents a promise for a return value for asynchronous operations.
 */
class Promise
{
    /**
     * A list of registered "then" event callbacks, in the order they were registered.
     *
     * @var callable[]
     */
    private $thenCallbacks = false;

    /**
     * Flag that indicates whether this promise has been resolved or not.
     *
     * @var bool
     */
    private $resolved = [];

    /**
     * Contains the resolution for the promise, if there is one.
     *
     * @var mixed
     */
    private $resolution = null;

    /**
     * Registers a "then" callback. The callable will be invoked when the promise completes.
     *
     * @param callable $then
     */
    public function then(callable $then)
    {
        $this->thenCallbacks[] = $then;

        // If this promise is already resolved, immediately fire this callback
        if ($this->resolved) {
            call_user_func($then, $this->resolution);
        }
    }

    /**
     * Resolves the promise, optionally with a resolution value.
     *
     * @param mixed $resolutionValue The resolution data, which will be passed to all event callbacks.
     */
    public function resolve($resolutionValue = null)
    {
        $this->resolved = true;
        $this->resolution = $resolutionValue;

        foreach ($this->thenCallbacks as $callback) {
            $callback($resolutionValue);
        }
    }
}