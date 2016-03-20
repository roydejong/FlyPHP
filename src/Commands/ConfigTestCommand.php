<?php

namespace FlyPHP\Commands;

use FlyPHP\Config\Configuration;
use FlyPHP\Fly;
use FlyPHP\Server\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Configuration test command.
 */
class ConfigTestCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('config:test')
            ->setDescription('Tests the validity of the configuration file, and shows basic information.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $flyDir = FLY_DIR;

        $configName = 'fly.yaml';
        $configPath = FLY_DIR . '/' . $configName;

        $output->writeln('<info>Testing configuration file: fly.yaml.</info>');

        $config = Configuration::instance();
        $config->loadFrom($configPath);

        // Environment info
        $output->writeln("- Fly installation directory: <comment>{$flyDir}</comment>");
        $output->writeln("- Configuration file path: <comment>{$config->getPath()}</comment>");

        // Config basics
        if ($config->isValid()) {
            $output->writeln('- <info>Configuration file syntax looks valid (parsed successfully)</info>');
        } else {
            $output->writeln("- Configuration file is invalid: <error>{$config->getError()}</error>");
        }

        if ($config->serverConfig->port < 1024) {
            $output->writeln("- <comment>Warning: The selected port number ({$config->serverConfig->port}) may require superuser rights.</comment>");
        }

        if (@inet_pton($config->serverConfig->address) === false) {
            $output->writeln("- <comment>Warning: The selected bind address ({$config->serverConfig->address}) does not look like a valid IPv4 or IPv6 address.</comment>");
        }

        if ($config->serverConfig->backlog < 50 || $config->serverConfig->backlog > 250) {
            $output->writeln("- <comment>Warning: A very low or very high backlog value has been set ({$config->serverConfig->backlog}) for the server. This is not recommended as this makes it more vulnerable to SYN attacks.</comment>");
        }

        $output->writeln('Completed configuration file checks.');
    }
}