<?php

namespace FlyPHP\Http;

use FlyPHP\Http\Encoding\ChunkedTransferEncoding;
use FlyPHP\IO\ReadBuffer;
use FlyPHP\Server\Connection;
use FlyPHP\Server\Server;
use FlyPHP\Http\Responses\ErrorResponse;

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
     * Option: Enable chunked transfer-encoding.
     *
     * @var bool
     */
    private $chunkedTransferEnabled = false;

    /**
     * Option: Chunked transfer encoding, chunk size.
     *
     * @var int
     */
    private $chunkedSize = 0;

    /**
     * The incoming request currently being parsed.
     *
     * @var Request
     */
    private $request;

    /**
     * The last request that was handled.
     *
     * @default null
     * @var Request
     */
    private $lastRequest;

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
     * Flag indicating whether we are handling a HTTP/Expect 100-continue scenario.
     * The flag is toggled ON when we have just sent a HTTP/1.1 100 Continue header and are awaiting a response body.
     * The flag is toggled OFF when we begin processing a new request.
     *
     * @var bool
     */
    private $handlingContinue;

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
     * Configures keep-alive configuration for this transaction.
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
     * Configures chunked transfer encoding for this transaction.
     *
     * @param bool $enable
     * @param int $chunkSize
     */
    public function setChunkedEncoding(bool $enable = false, int $chunkSize = 0)
    {
        $this->chunkedTransferEnabled = $enable;
        $this->chunkedSize = $chunkSize;
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
        // TODO (Wishful thinking) HTTPv2 / SPDY
        // TODO (Wishful thinking) HTTPS / TLS

        $this->connection->getReadBuffer()->subscribe(function (ReadBuffer $buffer) {
            try {
                $this->parseHttpRequest($buffer->contents());
            } catch (ParseException $ex) {
                // Send a 400 bad request response, and kill the connection to avoid getting stuck in a bad state
                $this->sendErrorResponse(StatusCode::HTTP_BAD_REQUEST, true);
                return;
            }
        });
    }

    /**
     * Completely resets the read buffer.
     */
    private function clearReadBuffer()
    {
        $this->connection->getReadBuffer()->clear();
        $this->parseOffset = 0;
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
     * Ends the current part of the transaction by marking the current request being parsed as "complete".
     * This puts the transaction handler in a state where it expects a new request will be received.
     * If the connection is not set to be kept alive, this will result in the transaction's end.
     */
    public function endParse()
    {
        $this->isParsing = false;

        if (!$this->keepAlive) {
            $this->end();
        }
    }

    /**
     * Sends an error response.
     *
     * @param int $statusCode
     * @param bool $killConnection
     */
    public function sendErrorResponse(int $statusCode = StatusCode::HTTP_BAD_REQUEST, bool $killConnection = false)
    {
        $response = new ErrorResponse($statusCode);

        if ($killConnection) {
            $this->keepAlive = false;
            $response->setHeader('Connection', 'close');
        } else if ($this->keepAlive) {
            $response->setHeader('Connection', 'keep-alive');
        }

        $response->send($this->connection);

        if ($killConnection) {
            $this->end();
        } else {
            $this->endParse();
        }
    }

    /**
     * Gets the the last handled request.
     *
     * @return Request
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Handles an incoming HTTP request.
     *
     * @param Request $request
     */
    public function handleRequest(Request $request)
    {
        $this->requestCounter++;
        $this->lastRequest = $request;

        // Check if keep-alive is enabled, and supported by the client
        if ($this->keepAliveEnabled && $request->hasHeader('connection') && strtolower($request->getHeader('connection')) == 'keep-alive') {
            $this->keepAlive = true;
        } else {
            $this->keepAlive = false;
        }

        if ($this->keepAlive && $this->keepAliveLimit > 0 && $this->requestCounter >= $this->keepAliveLimit) {
            // We have reached our limit for this keep-alive connection, disable keepalive
            $this->keepAlive = false;
        }

        // Handle "Expect" headers (e.g. for a 100 Continue transaction)
        // Note: We MUST either A) Send data and await further data, or B) end the connection when we are handling Expect
        $expectHeader = $request->getHeader('expect');

        if (!empty($expectHeader)) {
            if (!$this->handlingContinue && $expectHeader == '100-continue') {
                $this->handlingContinue = true;
                $this->connection->write('HTTP/1.1 100 Continue' . Response::HTTP_EOL);
                return;
            } else {
                // Either we have already sent a 100 Continue, or we got an invalid/unsupported expect header
                // Either way, we will blame the client with a 416 error and stop processing this request
                $this->sendErrorResponse(StatusCode::HTTP_EXPECTATION_FAILED);
                return;
            }
        }

        $response = new ErrorResponse(StatusCode::HTTP_NOT_FOUND);
        $response->setHeader('Connection', $this->keepAlive ? 'keep-alive' : 'close');

        $compressionMethod = $this->server->getCompressionNegotiator()->negotiate($request);

        if ($compressionMethod != null) {
            $compressionMethod->compress($response);
        }

        $sent = false;

        if ($this->chunkedTransferEnabled && $this->chunkedSize > 0) {
            $chunked = new ChunkedTransferEncoding($this->connection);

            if ($chunked->isSupported($request)) {
                $chunked->sendChunkedResponse($response, $this->chunkedSize);
                $sent = true;
            }
        }

        if (!$sent) {
            $response->send($this->connection);
        }

        if (!$this->keepAlive) {
            // We are not keeping this connection alive (anymore).
            $this->end();
            return;
        }
    }

    /**
     * Performs line-by-line parsing of an incoming request.
     *
     * This function can be called multiple times, once for each time any data is received, and will incrementally try
     * to read and parse the received data and construct a valid request.
     *
     * @param string $data
     * @throws ParseException
     */
    public function parseHttpRequest($data)
    {
        $originalData = $data;

        // Check if we are parsing a new message, or if we are continuing to parse a request we previously started parsing.
        if (!$this->isParsing) {
            $this->isParsing = true;

            $this->parseOffset = 0;
            $this->parsingFirstLine = true;
            $this->parsingHeaders = true;
            $this->parsingBody = false;

            $this->request = new Request();

            $this->handlingContinue = false;
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
            if ($this->request->getHeader('expect') != null) {
                // We have an expect header. Only parse the headers, and let the request handler determine what to do.
                // Request handler should either abort the connection, or cause further data to be sent.
                // Either way, end request processing at this stage (but not parsing) and reset the read buffer.
                $this->isParsing = true;
                $this->handleRequest($this->request);
                $this->clearReadBuffer();
                // Remove the "Expect" header as we have processed it and it is no longer indicative for the request.
                $this->request->removeHeader('expect');
                return;
            }

            // Determine how large the request body is
            $expectedContentLength = intval($this->request->getHeader('content-length'));
            $previouslyReceivedContentLength = strlen($this->request->getBody());
            $remainingContentLength = $expectedContentLength - $previouslyReceivedContentLength;

            if ($remainingContentLength > 0) {
                $dataReceived = substr($originalData, $this->parseOffset);
                $dataReceivedLength = strlen($dataReceived);

                if ($dataReceivedLength > $remainingContentLength) {
                    throw new ParseException('Content-Length mismatch');
                }

                $this->request->appendBody($dataReceived);
                $remainingContentLength -= $dataReceivedLength;

                // We eat the entire buffer, every time. Yummy. Reset pointer and buffer contents.
                $this->clearReadBuffer();
            }

            if ($remainingContentLength <= 0) {
                // We have received all the data we expected to receive in this request
                // Consider the request complete, end processing and parsing, and reset the buffer
                $this->isParsing = false;
                $this->handleRequest($this->request);
                $this->clearReadBuffer();
                return;
            }
        }
    }
}