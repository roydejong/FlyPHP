<?php

namespace FlyPHP\Config;

/**
 * Configuration section for the fly daemon / process manager.
 */
class DaemonConfigSection extends ConfigSection
{
    /**
     * The minimum and default amount of children to spawn.
     *
     * @var int
     */
    public $minChildren = 4;

    /**
     * The maximum amount of children to spawn.
     *
     * @var int
     */
    public $maxChildren = 8;

    /**
     * The maximum amount of time, in seconds, a single process is allowed to be kept alive for
     * If set to zero, this limit is not enforced.
     *
     * @var int
     */
    public $childLifetime = 60;

    /**
     * The start of the TCP port range (inclusive) on which the child processes are spawned and bound.
     *
     * Example: If 10 children are spawned, and the port range starts at 8080, ports will be bound in the
     * inclusive range of 8080 - 8090.
     *
     * @var int
     */
    public $portRangeStart = 8081;
}