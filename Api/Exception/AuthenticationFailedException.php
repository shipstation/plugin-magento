<?php

namespace Auctane\Api\Exception;


/**
 * Class AuthenticationFailedException
 * @package Auctane\Api\Exception
 */
class AuthenticationFailedException extends \Exception
{
    /**
     * AuthenticationFailedException constructor.
     */
    public function __construct()
    {
        parent::__construct("Authentication failed");
    }
}
