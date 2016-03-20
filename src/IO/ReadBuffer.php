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
     * Feeds data to the buffer, appending it to the current contents.
     *
     * @param string $data
     */
    public function feed(string $data)
    {
        echo $data;
        $this->contents .= $data;
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