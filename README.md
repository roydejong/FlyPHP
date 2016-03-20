FlyPHP
======

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

Clone FlyPHP from GitHub to fetch the latest version while it's still in development:

    git clone git@github.com:roydejong/FlyPHP.git flyphp
    cd flyphp

Install the composer dependencies:

    composer install

This command assumes you have installed composer to a system bin folder. If you haven't used composer before, [download and install it](https://getcomposer.org/download/) with the command `php composer-setup.php --install-dir=/usr/bin --filename=composer`.

**Starting the server**

To start the FlyPHP server on the default port (`8080`):

    php bin/fly

Optionally, you can request a non-default port (e.g. `80` to have FlyPHP act as a drop-in replacement for your web server):

    php bin/fly start --port 80

It may be necessary if, for example, you want to use HTTPS, HTTP v2, to set up a reverse proxy such as nginx.

Performance
-----------
FlyPHP is faster than `php-fpm`. Benchmarks coming soon.

Troubleshooting
---------------

**Long running processes crashing**

Because long running PHP processes have a tendency to crash - PHP was never really designed for it - it is *highly recommended* to use a watchdog to ensure that the `fly` process is restarted should it crash.

**Could not bind to TCP socket - Permission denied / Running as superuser**

You may see this error when trying to start the server. This happens because on Unix systems,the process needs to be run with root access to bind to ports lower than 1024 for security reasons [(some additional details)](https://serverfault.com/questions/112795/how-can-i-run-a-server-on-linux-on-port-80-as-a-normal-user).

The easiest solution is to run the server as the superuser, either under the root account or by using `sudo` - but this may be a potential security risk should a vulnerability be discovered in FlyPHP. Possible workarounds may involve using `authbind` or a reverse proxy server such as nginx.
