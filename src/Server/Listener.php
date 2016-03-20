<?php

namespace FlyPHP\Server;

use FlyPHP\Promises\Promise;

/**
 * An asynchronous TCP socket listener that accepts incoming connections on an endpoint.
 */
class Listener
{
    /**
     * Listen TCP port (e.g. 80).
     *
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $address;

    /**
     * @var int
     */
    private $backlog;

    /**
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
     * Initializes a TCP socket listener.
     *
     * @param int $port The port to bind to, e.g. 80.
     * @param string $address The IP address to bind to.
     * @param int $backlog
     */
    public function __construct(int $port = 80, string $address = '0.0.0.0', int $backlog = 128)
    {
        $this->port = $port;
        $this->address = $address;
        $this->backlog = $backlog;
    }

    /**
     * Begins listening for incoming connections.
     *
     * @param Loop $loop The server process loop.
     * @throws ListenerException If an error occurs while initializing the listener or handling a connection, a ListenerException is thrown.
     * @return Promise
     */
    public function listen(Loop $loop)
    {
        // Initialize our non-blocking socket stream
        $this->socket = @stream_socket_server("tcp://{$this->address}:{$this->port}", $errno, $errstr);
        $this->loop = $loop;

        if ($this->socket === false) {
            throw new ListenerException("Could not bind to TCP socket {$this->address}:{$this->port} - {$errstr}", $errno);
        }

        stream_set_blocking($this->socket, false);

        // Add our stream to the loop
        $promise = new Promise();

        $loop->awaitReadable($this->socket, function ($readStream) use ($promise) {
            $connection = $this->handleConnection($readStream);
            $promise->resolve($connection);
        });

        return $promise;
    }

    /**
     * Handles an incoming connection on a given $readStream.
     * This function should be called after the stream is notified that it has become readable.
     *
     * @param resource $readStream
     * @throws ListenerException If an error occurs while handling the connection, a ListenerException is thrown.
     * @return Connection
     */
    private function handleConnection($readStream)
    {
        $clientSocket = @stream_socket_accept($readStream);

        if (!$clientSocket) {
            throw new ListenerException('Could not accept new connection');
        }

        return new Connection($clientSocket, $this->loop);
    }
}