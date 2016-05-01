<?php

namespace FlyPHP\Daemon;

/**
 * A reference to a process.
 */
class ProcessReference
{
    /**
     * The process ID.
     *
     * @var int
     */
    private $pid;

    /**
     * The port number this process is associated with.
     *
     * @var int
     */
    private $port;

    /**
     * The start time of the process (the point from which the process is being tracked).
     *
     * @var float
     */
    private $startTime;

    /**
     * ProcessReference constructor.
     *
     * @param int $pid Process ID
     * @param int $port The TCP port this process is bound to
     */
    public function __construct(int $pid, int $port)
    {
        $this->pid = $pid;
        $this->startTime = microtime(true);
        $this->port = $port;
    }

    /**
     * Gets the process ID.
     *
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Gets the port number this process is bound to.
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Returns the time since the process was started (tracked).
     *
     * @return float Time since start, as a float precise to the microsecond.
     */
    public function getTimeSinceStart()
    {
        return microtime(true) - $this->startTime;
    }

    /**
     * Checks and returns whether this process is still alive.
     *
     * @return bool
     */
    public function isAlive()
    {
        if (IS_WIN) {
            $processes = explode("\n", shell_exec('tasklist.exe'));

            foreach ($processes as $processLine) {
                $processInfo = preg_split('/\s+/', $processLine);

                if (count($processInfo) < 2) {
                    continue;
                }

                $pid = intval($processInfo[1]);

                if ($pid == $this->pid) {
                    // Found our PID in the active list
                    return true;
                }
            }

            return false;
        } else {
            return file_exists("/proc/{$this->pid}");
        }
    }

    /**
     * Kills the process immediately.
     */
    public function kill()
    {
        if (IS_WIN) {
            shell_exec("taskkill /PID {$this->pid}");
        } else {
            posix_kill($this->pid, SIGKILL);
        }
    }
}