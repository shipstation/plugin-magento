<?php

namespace Auctane\Api\Exception;

class AuthorizationException extends ApiException
{
    public function __construct($message = 'Unauthorized to take this action', $code = 401)
    {
        parent::__construct($message, $code);
    }
}
