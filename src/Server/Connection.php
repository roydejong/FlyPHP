<?php

namespace FlyPHP\Server;

use FlyPHP\IO\ReadBuffer;
use FlyPHP\IO\WriteBuffer;

/**
 * Represents an incoming client connection.
 */
class Connection
{
    /**
     * The size of the read buffer in bytes.
     * Represents how much is read when incoming data is received.
     */
    static $READ_BUFFER_SIZE = 1024;

    /**
     * The size of the write buffer in bytes.
     * Represents how often data is flushed to the connection.
     */
    static $WRITE_BUFFER_SIZE = 10;

    /**
     * The socket resource.
     *
     * @var resource
     */
    private $socket;

    /**
     * The server event loop.
     *
     * @var Loop
     */
    private $loop;

    /**
     * @var ReadBuffer
     */
    private $readBuffer;

    /**
     * @var WriteBuffer
     */
    private $writeBuffer;

    /**
     * @param resource $socket
     * @param Loop $loop
     */
    public function __construct($socket, Loop $loop)
    {
        if (!is_resource($socket)) {
            throw new \InvalidArgumentException('$socket must be a valid socket resource');
        }

        $this->socket = $socket;
        $this->loop = $loop;

        $this->readBuffer = new ReadBuffer();
        $this->writeBuffer = new WriteBuffer($this->socket, self::$WRITE_BUFFER_SIZE);

        stream_set_blocking($this->socket, false);

        // Await incoming data.
        $this->loop->awaitReadable($this->socket, function () {
            $this->readIntoBuffer();
        });
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return is_resource($this->socket);
    }

    /**
     * Reads data from the connection.
     *
     * @return string|null
     */
    private function readIntoBuffer()
    {
        $data = stream_socket_recvfrom($this->socket, self::$READ_BUFFER_SIZE);

        if ($data === '' || $data === false || !is_resource($this->socket) || feof($this->socket)) {
            $this->disconnect();
            return null;
        }

        $this->readBuffer->feed($data);
    }

    /**
     * @return ReadBuffer
     */
    public function getReadBuffer()
    {
        return $this->readBuffer;
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return $this->isReadable();
    }

    /**
     * Writes data to the connection.
     *
     * @param string $data
     * @param bool $flush If true, force a flush after appending data to the write buffer (in addition to intermediate threshold flushing).
     */
    public function write($data, bool $flush = true)
    {
        if (!$this->isWritable()) {
            return;
        }

        $this->writeBuffer->feed($data);

        if ($flush) {
            $this->writeBuffer->flush();
        }
    }

    /**
     * @return WriteBuffer
     */
    public function getWriteBuffer()
    {
        return $this->writeBuffer;
    }

    /**
     * Closes the connection.
     *
     * @return bool True on success, false on failure.
     */
    public function disconnect()
    {
        $this->loop->removeStream($this->socket);

        if (!is_resource($this->socket)) {
            // Looks like this socket was already closed.
            return false;
        }

        // Shut down the socket (disable send and receive)
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);

        // Prevent the stream from blocking on fclose (PHP bug workaround) and then actually close it
        stream_set_blocking($this->socket, false);
        return fclose($this->socket);
    }

    /**
     * Returns the remote address for this connection.
     *
     * @return string
     */
    public function getRemoteAddress()
    {
        return stream_socket_get_name($this->socket, null);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getRemoteAddress();
    }
}