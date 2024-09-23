<?php

namespace Auctane\Api\Exception;

class BadRequestException extends ApiException
{
    /**
     * Constructor method for a 400 bad request
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message, int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
