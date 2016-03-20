<?php

namespace FlyPHP\Http;

use FlyPHP\Fly;
use FlyPHP\Server\Connection;

/**
 * Used for composing an HTTP response, sent from a server to a client.
 */
class Response extends HttpMessage
{
    const HTTP_VERSION = 'HTTP/1.1';

    /**
     * The HTTP status code.
     *
     * @var int
     */
    private $statusCode;

    /**
     * The status message tied to the status code.
     * Usually this is set automatically based on the status code, but it can be overriden by the user.
     *
     * @var string
     */
    private $statusMessage;

    /**
     * Initializes a new, blank HTTP response.
     */
    public function __construct()
    {
        $this->setStatus(StatusCode::HTTP_OK);
    }

    /**
     * Sets the HTTP status code, and optionally the status message.
     *
     * @param int $statusCode The HTTP status code.
     * @param string|null $statusMessage A custom HTTP status message. If null or empty, it will be set automatically.
     */
    public function setStatus(int $statusCode, string $statusMessage = null)
    {
        if (!StatusCode::isValid($statusCode)) {
            throw new \InvalidArgumentException("Code {$statusCode} is not a valid HTTP status code");
        }

        $this->statusCode = $statusCode;

        if (empty($statusMessage)) {
            $this->statusMessage = StatusCode::getMessageForCode($statusCode);
        } else {
            $this->statusMessage = $statusMessage;
        }
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }

    /**
     * Serializes this response's status line in the <VERSION> <CODE> <TEXT> format.
     *
     * Example output:
     *  HTTP/1.1 200 OK
     *
     * @return string
     */
    public function serializeStatusLine()
    {
        return sprintf('%s %s %s', self::HTTP_VERSION, $this->statusCode, $this->statusMessage);
    }

    /**
     * Serializes this response's header block, including status line and general, response and entity headers.
     *
     * @return string
     */
    public function serializeHead()
    {
        // Status lines
        $output = $this->serializeStatusLine();
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

        if (StatusCode::canHaveBody($this->statusCode)) {
            $output .= $this->getBody();
        }

        return $output;
    }

    /**
     * Prepares a request before sending it.
     * Used to set appropriate headers and configure certain parts of the response before each transmission.
     */
    private function prepare()
    {
        $this->setHeader('Date', (new \DateTime('UTC'))->format('D, d M Y H:i:s \G\M\T'));
        $this->setHeader('Connection', 'close');
        $this->setHeader('Server', sprintf('fly/%s', Fly::FLY_VERSION));

        if (!$this->hasHeader('Content-Type')) {
            $this->setHeader('Content-Type', 'text/html');
        }

        $this->setHeader('Content-Length', strlen($this->getBody()));
    }

    /**
     * Given a $connection, serializes and sends this response.
     *
     * @param Connection $connection
     */
    public function send(Connection $connection)
    {
        $this->prepare();
        $connection->write($this->serialize());
    }
}