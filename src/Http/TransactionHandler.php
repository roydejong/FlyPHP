<?php

namespace FlyPHP\Http;

use FlyPHP\IO\ReadBuffer;
use FlyPHP\Server\Connection;
use FlyPHP\Server\Server;

/**
 * This utility acts as a manager for a connection object.
 * It reads and parses incoming requests, and manages the connection throughout its lifetime.
 */
class TransactionHandler
{
    /**
     * The server instance hosting this transaction.
     *
     * @var Server
     */
    private $server;

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
     * @var bool
     */
    private $keepAlive = false;

    /**
     * Option: Enable keep-alive connections.
     *
     * @var bool
     */
    private $keepAliveEnabled = false;

    /**
     * Option: Timeout for keep-alive connections.
     *
     * @var int
     */
    private $keepAliveTimeout = 0;

    /**
     * Option: Limit of requests per single connection.
     *
     * @var int
     */
    private $keepAliveLimit = 0;

    /**
     * The incoming request currently being parsed.
     *
     * @var Request
     */
    private $request;

    /**
     * The amount of processed requests during this transaction.
     *
     * @var int
     */
    private $requestCounter;

    /**
     * Transaction start time, unix timestamp as a float.
     *
     * @var float
     */
    private $transactionStarted;

    /**
     * Initializes a new transaction handler for a given connection.
     *
     * @param Server $server
     * @param Connection $connection
     */
    public function __construct(Server $server, Connection $connection)
    {
        $this->server = $server;
        $this->connection = $connection;
    }

    /**
     * Configures keep-alive configuration for this connection.
     *
     * @param bool $enable
     * @param float $timeout
     * @param int $limit
     */
    public function setKeepAlive(bool $enable = false, float $timeout = 0, int $limit = 0)
    {
        $this->keepAliveEnabled = $enable;
        $this->keepAliveTimeout = $timeout;
        $this->keepAliveLimit = $limit;
    }

    /**
     * Gets the connection managed by this transaction.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Ticks the transaction, checking for timeouts.
     */
    public function tick()
    {
        if (!$this->connection->isReadable()) {
            // The connection has died, clean up the transaction.
            $this->end();
            return;
        }

        if ($this->keepAlive && $this->keepAliveTimeout > 0) {
            $transactionTime = microtime(true) - $this->transactionStarted;

            if ($transactionTime > $this->keepAliveTimeout) {
                // This is a keep-alive connection that has timed out.
                echo '< keep alive timeout reached >';
                $this->end();
                return;
            }
        }
    }

    /**
     * Initiates management of the current connection.
     */
    public function handle()
    {
        $this->transactionStarted = microtime(true);

        // TODO Handle multiparts
        // TODO Clean up and prevent leaky memory
        // TODO Handle excessive data spam
        // TODO HTTPv2 / SPDY
        // TODO (Wishful thinking) HTTPS / TLS

        $this->connection->getReadBuffer()->subscribe(function (ReadBuffer $buffer) {
            try {
                $this->parseHttpRequest($buffer->contents());
            } catch (ParseException $ex) {
                // TODO Send HTTP 400 error
                echo '< parse error >';
                $this->end();
                return;
            }
        });
    }

    /**
     * Ends the transaction, closing the connection and cleaning it up from the server.
     */
    public function end()
    {
        $this->connection->disconnect();
        $this->server->endTransaction($this);
    }

    /**
     * Handles an incoming HTTP request.
     *
     * @param Request $request
     */
    public function handleRequest(Request $request)
    {
        // Check if keep-alive is enabled, and supported by the client
        if ($this->keepAliveEnabled && $request->hasHeader('connection') && strtolower($request->getHeader('connection')) == 'keep-alive') {
            $this->keepAlive = true;
        } else {
            $this->keepAlive = false;
        }

        $this->requestCounter++;

        if ($this->keepAlive && $this->keepAliveLimit > 0 && $this->requestCounter >= $this->keepAliveLimit) {
            // We have reached our limit for this keep-alive connection, disable keepalive
            echo '< keep alive limit reached >';
            $this->keepAlive = false;
        }

        $response = new Response();
        $response->setHeader('Connection', $this->keepAlive ? 'keep-alive' : 'close');
        $response->setBody("Hello world!<br />Your user agent is <b>{$request->getHeader('user-agent')}</b>");

        $compressionMethod = $this->server->getCompressionNegotiator()->negotiate($request);

        if ($compressionMethod != null) {
            $compressionMethod->compress($response);
        }

        $response->send($this->connection);

        if (!$this->keepAlive) {
            // We are not keeping this connection alive (anymore).
            $this->end();
            return;
        }
    }

    /**
     * Performs line-by-line parsing of an incoming request.
     *
     * @param string $data
     * @throws ParseException
     */
    public function parseHttpRequest($data)
    {
        $originalData = $data;

        // Check if we are parsing a new message, or if we are continuing to parse a request we previously started
        // parsing.
        if (!$this->isParsing) {
            $this->isParsing = true;
            $this->parseOffset = 0;
            $this->request = new Request();
            $this->parsingFirstLine = true;
            $this->parsingHeaders = true;
        }

        // Parse headers line-by-line
        while ($this->parsingHeaders) {
            // Extract the relevant part of the buffer that we have not yet processed.
            $data = substr($originalData, $this->parseOffset);

            // Read until the next HTTP_EOL (\r\n)
            $nextEolIdx = strpos($data, HttpMessage::HTTP_EOL);

            if ($nextEolIdx === false) {
                break;
            }

            $line = substr($data, 0, $nextEolIdx);

            if ($this->parsingFirstLine) {
                // Parsing the request line: GET / HTTP/1.1
                $parts = explode(' ', $line);

                if (count($parts) != 3) {
                    throw new ParseException("Malformatted request line: {$line}");
                }

                $this->request->method = $parts[0];
                $this->request->path = $parts[1];
                $this->request->httpVersion = $parts[2];

                $this->parsingFirstLine = false;
            } else if (empty($line)) {
                // This is a blank line, which we take to mean the end of the header block
                $this->parsingHeaders = false;
                $this->parsingBody = true;
            } else {
                // Parsing a general key/value header
                $parts = explode(': ', $line, 2);

                if (count($parts) != 2) {
                    throw new ParseException('Encountered a malformatted request header');
                }

                $key = $parts[0];
                $value = $parts[1];

                $this->request->setHeader($key, $value);
            }

            $this->parseOffset += strlen($line) + strlen(HttpMessage::HTTP_EOL);
        }

        // Parse body
        if ($this->parsingBody) {
            // TODO Parse request bodies
            // For now, we'll just pretend we're done
            $this->isParsing = false;
            $this->handleRequest($this->request);
        }
    }
}