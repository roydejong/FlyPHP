<?php

namespace FlyPHP;

use FlyPHP\Commands\ConfigTestCommand;
use FlyPHP\Commands\StartCommand;
use FlyPHP\Config\Configuration;
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
        // Set the working directory to the fly install directory
        if (!defined('FLY_DIR'))
        {
            define('FLY_DIR', realpath(__DIR__ . '/../'));
        }

        // Try to configure a better process title
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
        $this->application->setDefaultCommand('start');

        // Register commands
        $this->application->addCommands([
            new StartCommand(),
            new ConfigTestCommand()
        ]);

        // Load configuration file
        $config = Configuration::instance();
        $config->loadFrom(FLY_DIR . '/fly.yaml');

        if (!$config->isValid())
        {
            echo "WARNING: There is a problem with your configuration file ({$config->getPath()}): {$config->getError()} - falling back to defaults." . PHP_EOL;
            echo "For more information, use the `fly config:test` command." . PHP_EOL;
            echo PHP_EOL;

        }

        // Start the console application
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