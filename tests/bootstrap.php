<?php

define('SCRIPT_DIR', __DIR__);
define('FLY_DIR', realpath(__DIR__ . '/../'));

$autoloader = FLY_DIR . '/vendor/autoload.php';

if (!file_exists($autoloader))
{
    echo 'ERROR: Please initialize composer (`composer install`).' . PHP_EOL;
    exit;
}

require_once $autoloader;

if (!class_exists('\FlyPHP\Fly'))
{
    echo 'ERROR: The FlyPHP component could not be autoloaded. Please check your composer dependencies.' . PHP_EOL;
    exit;
}