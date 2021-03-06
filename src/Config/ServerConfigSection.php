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
     * @default 8080
     * @var int
     */
    public $port = 8080;

    /**
     * The bind address.
     *
     * @default 0.0.0.0
     * @var string
     */
    public $address = '0.0.0.0';

    /**
     * The backlog for the network listener.
     *
     * @default 128
     * @var int
     */
    public $backlog = 128;

    /**
     * The keep alive timeout for connections, in seconds.
     * After this time, the connection will be closed on the server side.
     * If set to zero, keep-alive will be disabled.
     *
     * @default 15
     * @var int
     */
    public $keepAliveTimeout = 15;

    /**
     * The keep alive request limit for connections.
     * After this limit is reached, the connection will be closed on the server side.
     *
     * @default 100
     * @var int
     */
    public $keepAliveLimit = 100;

    /**
     * If true, enable gzip compression for responses.
     *
     * @default true
     * @var bool
     */
    public $gzipEnabled = true;

    /**
     * The level of compression. Can be given as 0 for no compression up to 9 for maximum compression.
     *
     * @default 2
     * @var int
     */
    public $gzipLevel = 2;

    /**
     * If true, enable gzip compression for responses.
     *
     * @default true
     * @var bool
     */
    public $deflateEnabled = true;

    /**
     * The level of compression. Can be given as 0 for no compression up to 9 for maximum compression.
     *
     * @default 2
     * @var int
     */
    public $deflateLevel = 2;

    /**
     * If true, enable chunked transfer encoding.
     *
     * @var int
     */
    public $chunkedEnabled = true;

    /**
     * The maximum size per chunk, in bytes.
     *
     * @var int
     */
    public $chunkedMaxSize = 128;
}