<?php

namespace FlyPHP\Http\Encoding;

use FlyPHP\Http\HttpMessage;
use FlyPHP\Http\Request;
use FlyPHP\Http\Response;
use FlyPHP\Server\Connection;

/**
 * Provides chunked transfer encoding for HTTP/1.1 responses.
 */
class ChunkedTransferEncoding
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * ChunkedTransferEncoding constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Analyzes a request to determine if it supports chunked encoding.
     *
     * @param Request $request
     * @return bool
     */
    public function isSupported(Request $request)
    {
        if ($request->httpVersion == 'HTTP/1.1') {
            return true;
        }

        return false;
    }

    /**
     * @param Response $response
     * @param int $chunkSize
     */
    public function sendChunkedResponse(Response $response, int $chunkSize = 100)
    {
        // Modify the headers so we do not send a Content-Length header, but only
        $response->removeHeader('Content-Length');
        $response->setHeader('Transfer-Encoding', 'chunked');

        // Write the headers plus EOL
        $responseHeader = $response->serializeHead();
        $this->connection->write($responseHeader . HttpMessage::HTTP_EOL);

        // Begin transmitting the body in chucks
        $data = str_split($response->getBody(), $chunkSize);

        foreach ($data as $chunk) {
            $this->sendChunk($chunk);
        }

        // Terminator
        $this->sendChunk('');
    }

    /**
     * Encodes and transmits a chunk of data to the current connection.
     * Assumes headers are already sent.
     *
     * @param string $data
     */
    public function sendChunk($data)
    {
        $this->connection->write(dechex(strlen($data)) . HttpMessage::HTTP_EOL . $data . HttpMessage::HTTP_EOL);
    }
}