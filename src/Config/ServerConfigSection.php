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
}