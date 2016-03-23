<?php

namespace FlyPHP\Http\Compression;

use FlyPHP\Http\Response;

/**
 * Gzip deflate compression method.
 */
class Deflate extends CompressionMethod
{
    /**
     * The level of compression. Can be given as 0 for no compression up to 9 for maximum compression.
     *
     * @default 2
     * @var int
     */
    public $compressionLevel = 2;

    /**
     * Gzip compression constructor.
     *
     * @param int $compressionLevel
     */
    public function __construct(int $compressionLevel = 2)
    {
        $this->compressionLevel = $compressionLevel;
    }

    /**
     * @inheritdoc
     */
    public function compress(Response $response)
    {
        $response->setHeader('Content-Encoding', 'deflate');
        $response->setBody(gzdeflate($response->getBody(), $this->compressionLevel, ZLIB_ENCODING_DEFLATE));
    }
}