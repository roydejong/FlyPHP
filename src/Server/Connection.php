<?php

namespace FlyPHP\Server;

/**
 * Represents an incoming client connection.
 */
class Connection
{
    /**
     * The socket resource.
     *
     * @var resource
     */
    private $socket;

    /**
     * @param resource $socket
     */
    public function __construct($socket)
    {
        if (!is_resource($socket))
        {
            throw new \InvalidArgumentException('$socket must be a valid socket resource');
        }

        $this->socket = $socket;

        stream_set_blocking($this->socket, false);
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
     * @return bool
     */
    public function isReadable()
    {
        return is_resource($this->socket);
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
     * @param $data
     */
    public function write($data)
    {
        if (!$this->isWritable())
        {
            return;
        }

        echo $data;
        fwrite($this->socket, $data);
    }

    /**
     * Closes the connection.
     *
     * @return bool True on success, false on failure.
     */
    public function disconnect()
    {
        if (!is_resource($this->socket))
        {
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
     * @return string
     */
    public function __toString()
    {
        return $this->getRemoteAddress();
    }
}