<?php

namespace FlyPHP\Http;

/**
 * Used for parsing or composing an HTTP request, sent by a client to a server.
 */
class Request extends HttpMessage
{
    /**
     * The request method (HTTP verb).
     *
     * @see RequestMethod
     * @var int
     */
    public $method = RequestMethod::GET;

    /**
     * The requested path, relative to the domain.
     *
     * @var string
     */
    public $path = '/';

    /**
     * The HTTP version used for this request.
     *
     * @var string
     */
    public $httpVersion = 'HTTP/1.0';
}