:warning: **Important:** This project is still under development and is not ready for any kind of usage. It's a playground, nothing more. Move along, nothing to see here yet.

FlyPHP
======

[![Build Status](https://travis-ci.org/roydejong/FlyPHP.svg?branch=master)](https://travis-ci.org/roydejong/FlyPHP)

**[flyphp.org](http://www.flyphp.org)**

**An asychronous, non-blocking HTTP server, bootstrapper and process manager written in pure PHP that lets you supercharge your web applications.**

FlyPHP is a drop-in replacement for `php-fpm` and, optionally, your web server. By managing long-running PHP processes it can bootstrap your application or framework to memory for a significant performance boost.

Features
--------

- PHP-based HTTP server (asynchronous, non-blocking IO)
- Process manager and bootstrapper for your application

Requirements
------------

- PHP 7 with CLI module
- Composer

Usage
-----

**Install FlyPHP**

Clone FlyPHP from GitHub to fetch the latest version (while it's still in development):

    git clone https://github.com/roydejong/FlyPHP.git flyphp
    cd flyphp

Install the composer dependencies:

    composer install

This command assumes you have installed composer to a system bin folder. If you haven't used composer before, [download and install it](https://getcomposer.org/download/) with the command `php composer-setup.php --install-dir=/usr/bin --filename=composer`.

Note: If you do not wish to install the development components (such as PHPUnit), use `composer install --no-dev`.

**Starting the server**

To start the FlyPHP server on the default port (`8080`):

    php bin/fly

Optionally, you can request a non-default port (e.g. `80` to have FlyPHP act as a drop-in replacement for your web server):

    php bin/fly start --port 80

It may be necessary if, for example, you want to use HTTPS, HTTP v2, to set up a reverse proxy such as nginx.

**Stopping the server**

To stop the server, simply terminate the main process. An interrupt will also (`CTRL+C`) do the trick.

Performance
-----------
FlyPHP is faster than `php-fpm`. That's the idea, anyway. Benchmarks coming soon.

Troubleshooting
---------------

**Long running processes crashing**

Because long running PHP processes have a tendency to crash - PHP was never really designed for it - it is *highly recommended* to use a watchdog to ensure that the `fly` process is restarted should it crash.

**Could not bind to TCP socket - Permission denied / Running as superuser**

You may see this error when trying to start the server. This happens because on Unix systems,the process needs to be run with root access to bind to ports lower than 1024 for security reasons [(some additional details)](https://serverfault.com/questions/112795/how-can-i-run-a-server-on-linux-on-port-80-as-a-normal-user).

The easiest solution is to run the server as the superuser, either under the root account or by using `sudo` - but this may be a potential security risk should a vulnerability be discovered in FlyPHP. Possible workarounds may involve using `authbind` or a reverse proxy server such as nginx.

**Configuration file location / troubleshooting**

The configuration file is called `fly.yaml`. It is located in the root of the FlyPHP installation directory. The installation directory is wherever you cloned the repository to.

You can verify your configuration file and view some basic information about it by using the `bin/fly config:test` command from the installation directory. This command will also alert you to any potential problems found in your settings.