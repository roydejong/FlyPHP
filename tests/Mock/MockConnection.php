<?php

namespace FlyPHP\Tests\Mock;

use FlyPHP\IO\ReadBuffer;
use FlyPHP\Runtime\Loop;

class MockConnection extends \FlyPHP\Server\Connection
{
    private $disconnected = false;

    public function __construct($socket = null, Loop $loop = null)
    {
        $this->readBuffer = new ReadBuffer();
        $this->writeBuffer = new MockWriteBuffer();
    }

    public function isReadable()
    {
        return !$this->disconnected;
    }

    public function isWritable()
    {
        return !$this->disconnected;
    }

    public function disconnect()
    {
        $this->disconnected = true;
    }

    public function mockReceiveData($data)
    {
        $this->getReadBuffer()->feed($data);
    }
}