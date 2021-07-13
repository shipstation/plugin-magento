<?php

namespace Auctane\Api\Exception;

use Magento\Framework\Exception\LocalizedException;


/**
 * Class AuthenticationFailedException
 * @package Auctane\Api\Exception
 */
class AuthenticationFailedException extends LocalizedException
{
    /**
     * AuthenticationFailedException constructor.
     */
    public function __construct()
    {
        parent::__construct(__("Authentication failed"));
    }
}
