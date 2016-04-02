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
    public $httpVersion = 'HTTP/1.1';

    /**
     * Serializes this request's status line in the <METHOD> <PATH> <VERSION> format.
     *
     * Example output:
     *  GET / HTTP/1.1
     *
     * @return string
     */
    public function serializeRequestLine()
    {
        return sprintf('%s %s %s', $this->method, $this->path, $this->httpVersion);
    }

    /**
     * Serializes this request's header block, including request line and headers.
     *
     * @return string
     */
    public function serializeHead()
    {
        // Status lines
        $output = $this->serializeRequestLine();
        $output .= self::HTTP_EOL;

        // General, response and entity headers
        foreach ($this->getHeaders() as $name => $value) {
            $output .= "{$name}: {$value}";
            $output .= self::HTTP_EOL;
        }

        return $output;
    }

    /**
     * Serializes this full response.
     */
    public function serialize()
    {
        $output = $this->serializeHead();
        $output .= self::HTTP_EOL;
        $output .= $this->getBody();
        return $output;
    }
}