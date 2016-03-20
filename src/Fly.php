<?php

namespace FlyPHP;

use FlyPHP\Commands\StartCommand;
use Symfony\Component\Console\Application;

/**
 * The FlyPHP console application wrapper.
 */
class Fly
{
    const FLY_VERSION = '0.1';
    const FLY_CODENAME = 'Frog';

    /**
     * @var Application
     */
    private $application;

    /**
     * Fly, to the sky!
     */
    public function fly()
    {
        if (function_exists('cli_set_process_title'))
        {
            // this doesn't really seem to work without running as superuser nowadays
            cli_set_process_title('fly');
        }

        if (function_exists('setproctitle'))
        {
            // setproctitle is considered dangerous, but only because it breaks out of its memory bounds
            // luckily "fly" is equally long as the default "php", so we should be OK
            setproctitle('fly');
        }

        // Configure the console application
        $this->application = new Application();
        $this->application->setName('FlyPHP');
        $this->application->setVersion(Fly::getVersionString());
        $this->application->setAutoExit(true);
        $this->application->setCatchExceptions(true);

        // Register commands
        $this->application->addCommands([
            new StartCommand()
        ]);

        // Start the console application
        $this->application->setDefaultCommand('start');
        $this->application->run();
    }

    /**
     * Returns the verbose version string for the FlyPHP application.
     *
     * @return string
     */
    public static function getVersionString()
    {
        return sprintf('%s ("Flying %s")', self::FLY_VERSION, self::FLY_CODENAME);
    }
}