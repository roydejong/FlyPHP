<?php

namespace FlyPHP\Http;

abstract class HttpMessage
{
    const HTTP_EOL = "\r\n";

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
     * Returns an associative array of all headers, indexed by header name.
     *
     * @return string[]
     */
    public function getHeaders()
    {
        return $this->headers;
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
}