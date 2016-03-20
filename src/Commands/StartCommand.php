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
 * Main command for the FlyPHP server.
 * Starts listening and handling connections.
 */
class StartCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('start')
            ->setDescription('Start the FlyPHP server')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'TCP Port number to listen on (defaults to 8080)');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>Starting FlyPHP server</info> <comment>v%s</comment>', Fly::getVersionString()));

        $port = intval($input->getOption('port'));
        if ($port <= 0) $port = 8080;

        $server = new Server();
        $server->registerOutput($output);
        $server->start(Configuration::instance()->serverConfig);

        $output->writeln('<comment>Bye</comment>');
    }
}