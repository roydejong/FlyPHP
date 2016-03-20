<?php

namespace FlyPHP\IO;

/**
 * A data buffer for reading.
 */
class ReadBuffer
{
    /**
     * The raw contents of the buffer.
     *
     * @var string
     */
    private $contents = '';

    /**
     * A collection of callbacks, to be invoked when data is fed in to the buffer.
     *
     * @var callable[]
     */
    private $callbacks = [];

    /**
     * Feeds data to the buffer, appending it to the current contents.
     *
     * @param string $data
     */
    public function feed(string $data)
    {
        echo $data;
        $this->contents .= $data;

        // Notify all subscribers that this buffer has changed
        foreach ($this->callbacks as $callback) {
            $callback($this);
        }
    }

    /**
     * Subscribes to events for this buffer.
     *
     * @param callable $callback
     */
    public function subscribe(callable $callback)
    {
        $this->callbacks[] = $callback;
    }

    /**
     * Clears the buffer to zero-length.
     */
    public function clear()
    {
        $this->contents = '';
    }

    /**
     * @return string
     */
    public function length()
    {
        return strlen($this->contents);
    }

    /**
     * @return string
     */
    public function contents()
    {
        return $this->contents;
    }
}