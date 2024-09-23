<?php

namespace Auctane\Api\Exception;

class ApiException extends \Exception
{
    /**
     * @var int
     */
    protected $httpStatusCode;

    /**
     * Creates an error associated with API response types
     *
     * @param string $message
     * @param int $httpStatusCode
     */
    public function __construct(string $message, int $httpStatusCode = 500)
    {
        parent::__construct($message);
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * Returns the http status code associated with the error
     *
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}
