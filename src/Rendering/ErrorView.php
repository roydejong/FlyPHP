<?php

namespace FlyPHP\Rendering;

use FlyPHP\Fly;
use FlyPHP\Http\StatusCode;

/**
 * A view that can be used for generating dynamic error pages.
 *
 * @property int $errorCode
 * @property string $errorMessage
 */
class ErrorView extends View
{
    /**
     * Initializes a default error page for a given status code.
     *
     * @param int $errorCode
     */
    public function __construct(int $errorCode)
    {
        parent::__construct('error.twig');

        $this->errorCode = $errorCode;
        $this->errorMessage = StatusCode::getMessageForCode($errorCode);
    }
}