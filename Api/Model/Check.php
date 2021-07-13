<?php

namespace Auctane\Api\Model;

use Auctane\Api\Api\CheckInterface;
use Auctane\Api\Exception\AuthenticationFailedException;
use Auctane\Api\Request\Authenticator;


/**
 * Class Check
 * @package Auctane\Api\Model
 */
class Check implements CheckInterface
{
    /**
     * @var Authenticator
     */
    private $authenticator;


    /**
     * Check constructor.
     * @param Authenticator $authenticator
     */
    public function __construct(
        Authenticator $authenticator
    )
    {
        $this->authenticator = $authenticator;
    }

    /**
     * @return bool
     * @throws AuthenticationFailedException
     */
    public function check(): bool
    {
        $this->authenticator->authenticate();
        return true;
    }
}
