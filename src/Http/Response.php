<?php

namespace FlyPHP\Http;
use FlyPHP\Fly;
use FlyPHP\Server\Connection;

/**
 * Used for composing an HTTP response, sent from a server to a client.
 */
class Response
{
    const HTTP_VERSION = 'HTTP/1.1';
    const HTTP_EOL = "\r\n";

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
     * An associative array containing HTTP response headers indexed by their name.
     *
     * @var string[]
     */
    private $headers;

    /**
     * A case sensitivity mapping for header keys. Maps all lowercase headers to the actual casing.
     *
     * For example:
     *  x-requested-with -> X-Requested-With
     *
     * This array is used for preventing duplicates.
     *
     * @var string[]
     */
    private $headerKeys;

    /**
     * The response body.
     *
     * @var string
     */
    private $body;

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
     * Sets a header value, updating or adding it.
     *
     * @param string $key
     * @param string $value
     */
    public function setHeader(string $key, string $value)
    {
        $lowerKey = strtolower($key);

        if (isset($this->headerKeys[$lowerKey])) {
            // Remove old header if a key mapping for this header exists.
            // The result is that the casing of the latest setHeader call is always retained, and duplicates with
            //  different casing are prevented.
            $this->removeHeader($lowerKey);
        }

        $this->headers[$key] = $value;
        $this->headerKeys[$lowerKey] = $key;
    }

    /**
     * Case-insensitive check to see if a header with a given $key has been set up.
     *
     * @param string $key
     * @return bool
     */
    public function hasHeader(string $key)
    {
        return isset($this->headerKeys[strtolower($key)]);
    }

    /**
     * Case-insensitive function to fetch a header by its key.
     *
     * @param string $key
     * @return bool
     */
    public function getHeader(string $key)
    {
        if (!$this->hasHeader($key)) {
            return null;
        }

        $lowerKey = strtolower($key);
        $actualKey = $this->headerKeys[$lowerKey];
        return $this->headers[$actualKey];
    }

    /**
     * Removes a header by its key.
     *
     * @param string $key Case-insenstive key.
     */
    public function removeHeader(string $key)
    {
        $lowerKey = strtolower($key);

        if (isset($this->headerKeys[$lowerKey])) {
            // Update actual key name we need to remove from the mapping
            $key = $this->headerKeys[$lowerKey];

            // Remove key mapping
            unset($this->headerKeys[$lowerKey]);
        }

        if (isset($this->headers[$key])) {
            // Remove actual header
            unset($this->headers[$key]);
        }
    }

    /**
     * Clears the response body, and sets it to a given string.
     *
     * @param string $body
     */
    public function setBody(string $body)
    {
        $this->clearBody();
        $this->appendBody($body);
    }

    /**
     * Appends a string to the response body.
     *
     * @param string $append
     */
    public function appendBody(string $append)
    {
        $this->body .= $append;
    }

    /**
     * Resets the response body.
     */
    public function clearBody()
    {
        $this->body = '';
    }

    /**
     * Gets the response body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
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
        foreach ($this->headers as $name => $value) {
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
            $output .= $this->body;
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

        $this->setHeader('Content-Length', strlen($this->body));
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