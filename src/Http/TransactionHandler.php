<?php

namespace FlyPHP\Http;

use FlyPHP\IO\ReadBuffer;
use FlyPHP\Server\Connection;

/**
 * This utility acts as a manager for a connection object.
 * It reads and parses incoming requests, and manages the connection throughout its lifetime.
 */
class TransactionHandler
{
    /**
     * @var Connection $connection
     */
    private $connection;

    /**
     * Indicates whether we are in the process of parsing a request.
     *
     * @var bool
     */
    private $isParsing = false;

    /**
     * @var int
     */
    private $parseOffset = 0;

    /**
     * @var bool
     */
    private $parsingHeaders = false;

    /**
     * @var bool
     */
    private $parsingFirstLine = false;

    /**
     * @var bool
     */
    private $parsingBody = false;

    /**
     * The incoming request currently being parsed.
     *
     * @var Request
     */
    private $request;

    /**
     * Initializes a new transaction handler for a given connection.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Initiates management of the current connection.
     */
    public function handle()
    {
        // TODO Handle timeouts
        // TODO Support keep alive connections
        // TODO Various encodings
        // TODO Handle multiparts
        // TODO Clean up and prevent leaky memory
        // TODO Handle excessive data spam
        // TODO HTTPv2 / SPDY
        // TODO (Wishful thinking) HTTPS / TLS

        $this->connection->getReadBuffer()->subscribe(function (ReadBuffer $buffer) {
            $this->parseHttpRequest($buffer->contents());
        });
    }

    /**
     * Handles an incoming HTTP request.
     *
     * @param Request $request
     */
    public function handleRequest(Request $request)
    {
        $response = new Response();
        $response->setBody("Hello world!<br />Your user agent is <b>{$request->getHeader('user-agent')}</b>");
        $response->send($this->connection);

        $this->connection->disconnect();
    }

    /**
     * Performs line-by-line parsing of an incoming request.
     *
     * @param string $data
     */
    public function parseHttpRequest($data)
    {
        $originalData = $data;

        // Check if we are parsing a new message, or if we are continuing to parse a request we previously started
        // parsing.
        if (!$this->isParsing)
        {
            $this->isParsing = true;
            $this->parseOffset = 0;
            $this->request = new Request();
            $this->parsingFirstLine = true;
            $this->parsingHeaders = true;
        }

        // Parse headers line-by-line
        while ($this->parsingHeaders)
        {
            // Extract the relevant part of the buffer that we have not yet processed.
            $data = substr($originalData, $this->parseOffset);

            // Read until the next HTTP_EOL (\r\n)
            $nextEolIdx = strpos($data, HttpMessage::HTTP_EOL);

            if ($nextEolIdx === false)
            {
                break;
            }

            $line = substr($data, 0, $nextEolIdx);

            if ($this->parsingFirstLine)
            {
                // Parsing the request line: GET / HTTP/1.1
                $parts = explode(' ', $line);
                
                if (count($parts) != 3)
                {
                    throw new ParseException("Malformatted request line: {$line}");
                }

                $this->request->method = $parts[0];
                $this->request->path = $parts[1];
                $this->request->httpVersion = $parts[2];

                $this->parsingFirstLine = false;
            }
            else if (empty($line))
            {
                // This is a blank line, which we take to mean the end of the header block
                $this->parsingHeaders = false;
                $this->parsingBody = true;
            }
            else
            {
                // Parsing a general key/value header
                $parts = explode(': ', $line, 2);

                if (count($parts) != 2)
                {
                    throw new ParseException('Encountered a malformatted request header');
                }

                $key = $parts[0];
                $value = $parts[1];

                $this->request->setHeader($key, $value);
            }

            $this->parseOffset += strlen($line) + strlen(HttpMessage::HTTP_EOL);
        }

        // Parse body
        if ($this->parsingBody)
        {
            // TODO Parse request bodies
            // For now, we'll just pretend we're done
            $this->isParsing = false;
            $this->handleRequest($this->request);
        }
    }
}