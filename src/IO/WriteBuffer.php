<?php

namespace FlyPHP\IO;

/**
 * A data buffer for writing.
 */
class WriteBuffer extends ReadBuffer
{
    /**
     * The stream to write to.
     *
     * @var resource
     */
    private $stream;

    /**
     * The maximum size of the buffer.
     * This is the threshold before data is sent.
     *
     * @var int
     */
    private $size;

    /**
     * Constructs a new write buffer for a given stream.
     *
     * @param $stream
     * @param int $size
     */
    public function __construct($stream, int $size = 1024)
    {
        $this->stream = $stream;
        $this->size = $size;
    }

    /**
     * @inheritdoc
     */
    public function feed(string $data)
    {
        while (strlen($data) > 0) {
            // Do not exceed our buffer size, feed it in segments instead
            $bytesToRead = $this->size - $this->length();
            $dataSegement = substr($data, 0, $bytesToRead);
            $data = substr($data, strlen($dataSegement));

            parent::feed($dataSegement);

            if ($this->length() >= $this->size) {
                $this->flush();
            }
        }
    }

    /**
     * Flushes the write buffer to the current stream.
     */
    public function flush()
    {
        $dataToSend = $this->contents();

        if (strlen($dataToSend) > 0) {
            @fwrite($this->stream, $dataToSend);
            $this->clear();
        }
    }
}