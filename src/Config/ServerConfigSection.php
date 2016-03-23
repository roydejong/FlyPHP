<?php

namespace FlyPHP\Config;

/**
 * A root-level configuration block representing the server configuration.
 */
class ServerConfigSection extends ConfigSection
{
    /**
     * The bind port (TCP).
     *
     * @var int
     */
    public $port = 8080;

    /**
     * The bind address.
     *
     * @var string
     */
    public $address = '0.0.0.0';

    /**
     * The backlog for the network listener.
     *
     * @var int
     */
    public $backlog = 128;

    /**
     * The keep alive timeout for connections, in seconds.
     * After this time, the connection will be closed on the server side.
     * If set to zero, keep-alive will be disabled.
     *
     * @var int
     */
    public $keepAliveTimeout = 30;

    /**
     * The keep alive request limit for connections.
     * After this limit is reached, the connection will be closed on the server side.
     *
     * @var int
     */
    public $keepAliveLimit = 0;
}