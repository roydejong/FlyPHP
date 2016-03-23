<?php

namespace FlyPHP\Http\Compression;

use FlyPHP\Http\Response;

/**
 * Compression handler for a request.
 */
abstract class CompressionMethod
{
    /**
     * Given a response, compresses its body and modifies the headers as appropriate.
     *
     * @param Response $response
     */
    public abstract function compress(Response $response);
}