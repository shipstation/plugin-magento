<?php

namespace Auctane\Api\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Class AuthenticationFailedException
 *
 * Used to throw an exception when the authentication fails
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
