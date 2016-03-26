<?php

namespace FlyPHP\Http\Responses;

use FlyPHP\Http\Response;
use FlyPHP\Http\StatusCode;

/**
 * Server error response.
 */
class ErrorResponse extends Response
{
    /**
     * Initializes an error response.
     *
     * @param int $errorCode HTTP Status Code
     * @param int|null $errorMessage
     */
    public function __construct(int $errorCode = StatusCode::HTTP_BAD_REQUEST, int $errorMessage = null)
    {
        parent::__construct();

        if (!StatusCode::isError($errorCode)) {
            throw new \InvalidArgumentException('ErrorResponse: Status code must be an error');
        }

        $this->setStatus($errorCode, $errorMessage == null ? StatusCode::getMessageForCode($errorCode) : $errorMessage);
        $this->setBody("error {$errorCode} {$errorMessage}");
    }
}