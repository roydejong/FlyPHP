<?php

/**
 * Tests related to keep-alive connections, timeouts and limitations.
 */
class PostRequestTest extends PHPUnit_Framework_TestCase
{
    public function testHttp100Continue()
    {
        $server = new \FlyPHP\Tests\Mock\MockServer();
        $connection = new \FlyPHP\Tests\Mock\MockConnection();

        $handler = new \FlyPHP\Http\TransactionHandler($server, $connection);
        $handler->handle();

        // Step one: send just the headers, with a content-length, post method and an Expect header
        $testPostContent = 'abcdefghijklmnopqrstuvwxyz';

        $testRequestHeaders = 'POST / HTTP/1.1' . "\r\n";
        $testRequestHeaders .= 'Content-Length: ' . strlen($testPostContent) . "\r\n";
        $testRequestHeaders .= 'Expect: 100-continue' . "\r\n";
        $testRequestHeaders .= "\r\n";

        $connection->mockReceiveData($testRequestHeaders);

        // Step two: the server should respond with a HTTP 100 continue message
        /**
         * @var $writeBuffer \FlyPHP\Tests\Mock\MockWriteBuffer
         */
        $writeBuffer = $handler->getConnection()->getWriteBuffer();
        $this->assertEquals('HTTP/1.1 100 Continue' . "\r\n", $writeBuffer->flushedOutput, 'Expect: 100-continue should be replied to with 100 Continue status [and nothing else]');
        $writeBuffer->clear();

        // Step three: we send our actual body
        $connection->mockReceiveData($testPostContent);

        // Step four: the server should respond again, but not with a 100 continue
        $this->assertNotEmpty($writeBuffer->flushedOutput, 'After sending a response body, post 100 continue, a response should be given by the server');
        $this->assertNotContains('100 Continue', $writeBuffer->flushedOutput, 'No further continue should be sent, post 100 continue body transmission');
    }

    

}