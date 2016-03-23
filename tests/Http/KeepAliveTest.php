<?php

class KeepAliveTest extends PHPUnit_Framework_TestCase
{
    public function testNoKeepAlive()
    {
        $server = new \FlyPHP\Tests\Mock\MockServer();
        $connection = new \FlyPHP\Tests\Mock\MockConnection();

        $handler = new \FlyPHP\Http\TransactionHandler($server, $connection);
        $handler->handle();

        $connection->mockReceiveData("GET / HTTP/1.1\r\nConnection: keep-alive\r\n\r\n");

        /**
         * @var $writeBuffer \FlyPHP\Tests\Mock\MockWriteBuffer
         */
        $writeBuffer = $handler->getConnection()->getWriteBuffer();

        $this->assertContains('Connection: close', $writeBuffer->flushedOutput, 'Connection should be closed');
        $this->assertFalse($connection->isReadable(), 'Connection should be closed');
    }

    public function testKeepAliveWithLimit()
    {
        $server = new \FlyPHP\Tests\Mock\MockServer();
        $connection = new \FlyPHP\Tests\Mock\MockConnection();

        $handler = new \FlyPHP\Http\TransactionHandler($server, $connection);
        $handler->setKeepAlive(true, 0, 6);
        $handler->handle();

        /**
         * @var $writeBuffer \FlyPHP\Tests\Mock\MockWriteBuffer
         */
        $writeBuffer = $handler->getConnection()->getWriteBuffer();

        // Send five requests, which should all be answered with a "keep-alive"
        for ($i = 0; $i < 5; $i++) {
            $connection->mockReceiveData("GET / HTTP/1.1\r\nConnection: keep-alive\r\n\r\n");

            $this->assertContains('Connection: keep-alive', $writeBuffer->flushedOutput, 'Connection should be open');
            $this->assertTrue($connection->isReadable(), 'Connection should be open');
        }

        // On the 6th request, we expect a closure
        $connection->mockReceiveData("GET / HTTP/1.1\r\nConnection: keep-alive\r\n\r\n");

        $this->assertContains('Connection: close', $writeBuffer->flushedOutput, 'Connection should be closed');
        $this->assertFalse($connection->isReadable(), 'Connection should be closed');
    }
}