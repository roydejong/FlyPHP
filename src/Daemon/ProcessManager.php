<?php

namespace FlyPHP\Daemon;

use FlyPHP\Config\DaemonConfigSection;
use FlyPHP\Runtime\Loop;
use FlyPHP\Runtime\Timer;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Fly process management provider.
 */
class ProcessManager
{
    /**
     * A list of managed Fly processes.
     *
     * @var ProcessReference[]
     */
    protected $processes;

    /**
     * The process manager event loop.
     *
     * @var Loop
     */
    protected $loop;

    /**
     * The process manager / deamon configuration data.
     *
     * @var DaemonConfigSection
     */
    protected $config;

    /**
     * Output interface for the daemon.
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * A list of port numbers on which child processes should be bound.
     *
     * @var int[]
     */
    protected $portNumbers;

    /**
     * Initializes a new process manager.
     *
     * @var DaemonConfigSection $config
     */
    public function __construct(DaemonConfigSection $config)
    {
        $this->processes = [];
        $this->config = $config;
        $this->output = new ConsoleOutput();
        $this->portNumbers = [];

        $portRangeEnd = $config->portRangeStart + $config->maxChildren;

        for ($portNumber = $config->portRangeStart; $portNumber <= $portRangeEnd; $portNumber++) {
            $this->portNumbers[] = $portNumber;
        }
    }

    /**
     * Starts the process manager loop.
     */
    public function start()
    {
        $this->output->writeln('<info>The FlyPHP process manager is starting...</info>');

        if (IS_WIN) {
            $this->output->writeln('<error>Warning: You are running Fly on Windows, which is not fully supported at this time.</error>');
        }

        $this->loop = new Loop();

        $timer = new Timer(1, true, [$this, 'loop']);
        $timer->start($this->loop);

        $this->loop->run();
    }

    /**
     * Stops the process manager by shutting down the event loop and stopping all child processes.
     */
    public function stop()
    {
        $this->loop->stop();

        foreach ($this->processes as $process) {
            $process->kill();
        }

        $this->processes = [];
    }

    /**
     * Loop function being triggered
     */
    public function loop()
    {
        $this->checkProcesses();

        $activeProcesses = count($this->processes);
        $this->output->writeln("Active child processes: {$activeProcesses}");

        for ($i = $activeProcesses; $i < $this->config->minChildren; $i++) {
            $this->spawnProcess();
        }
    }

    /**
     * Tries to locate an available port number to bind a new child process on.
     *
     * @return bool|int Returns an available port number, or FALSE if no ports are available.
     */
    private function getAvailablePortNumber()
    {
        foreach ($this->portNumbers as $portNumber) {
            foreach ($this->processes as $process) {
                if ($process->getPort() == $portNumber) {
                    // This port number is in use by a process
                    continue 2;
                }
            }

            // This port number looks to be free
            return $portNumber;
        }

        return false;
    }

    /**
     * Spawns a new child process.
     */
    public function spawnProcess()
    {
        $portNumber = $this->getAvailablePortNumber();

        if ($portNumber === false) {
            throw new \RuntimeException('No port number is available, cannot spawn new child process');
        }

        $options = [];
        $options[] = 'start';
        $options[] = '--daemon-child';
        $options[] = "--port {$portNumber}";

        $binPath = FLY_DIR . '/bin/fly';
        $optionsString = implode(' ', $options);
        $execString = "php {$binPath} {$optionsString}";

        $this->output->writeln($execString);

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $proc = proc_open($execString, $descriptorspec, $pipes);
        $proc_details = proc_get_status($proc);
        $pid = $proc_details['pid'];

        $this->output->writeln("Started process with PID {$pid} on port {$portNumber}");

        $process = new ProcessReference($pid, $portNumber);

        if (!$process->isAlive()) {
            $this->output->writeln('<error>Process exited immediately</error>');
            return;
        }

        $this->processes[] = $process;
    }

    /**
     * Checks all managed processes, removing any dead processes from the list.
     */
    public function checkProcesses()
    {
        $remainingProcesses = [];

        foreach ($this->processes as $process) {
            // Check whether the process is still alive
            if (!$process->isAlive()) {
                $this->output->writeln("Child process #{$process->getPid()} has exited unexpectedly");
                continue;
            }

            // Check whether the process has exceeded its max lifetime, if a limit configured
            if ($this->config->childLifetime > 0 && $process->getTimeSinceStart() >= $this->config->childLifetime) {
                $process->kill();
                $this->output->writeln("Child process #{$process->getPid()} killed (lifetime limit reached)");
                continue;
            }

            // Okay, looking good
            $remainingProcesses[] = $process;
        }

        $this->processes = $remainingProcesses;
    }
}