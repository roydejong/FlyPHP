<?php

namespace FlyPHP\Tests\Mock;

use FlyPHP\IO\WriteBuffer;

class MockWriteBuffer extends WriteBuffer
{
    public $flushedOutput = '';

    public function __construct($stream = null, $size = 1024)
    {
        parent::__construct(null, $size);
    }

    public function clear($fo = true)
    {
        parent::clear();

        if ($fo) {
            $this->flushedOutput = '';
        }
    }

    public function flush()
    {
        $this->flushedOutput .= $this->contents();
        $this->clear(false);
    }
}