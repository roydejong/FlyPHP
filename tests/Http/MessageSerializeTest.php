<?php

class MessageSerializeTest extends PHPUnit_Framework_TestCase
{
    public function testRequestSerialize()
    {
        $server = new \FlyPHP\Tests\Mock\MockServer();
        $connection = new \FlyPHP\Tests\Mock\MockConnection();

        $handler = new \FlyPHP\Http\TransactionHandler($server, $connection);
        $handler->handle();

        $testPostContent = 'abcdefghijklmnopqrstuvwxyz';

        $testRequestHeaders = 'POST / HTTP/1.1' . "\r\n";
        $testRequestHeaders .= 'Content-Length: ' . strlen($testPostContent) . "\r\n";
        $testRequestHeaders .= 'X-Sample-Header: some-value; charset=utf-8; etcetera' . "\r\n";
        $testRequestHeaders .= "\r\n";
        $testRequestHeaders .= $testPostContent;

        $connection->mockReceiveData($testRequestHeaders);

        $this->assertNotEmpty($handler->getLastRequest());
        $this->assertEquals($testRequestHeaders, $handler->getLastRequest()->serialize(), 'Re-serialization of a request should exactly match the received request');
    }
}