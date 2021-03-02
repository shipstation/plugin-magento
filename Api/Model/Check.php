<?php

namespace Auctane\Api\Model;

use Auctane\Api\Api\CheckInterface;
use Auctane\Api\Exception\AuthenticationFailedException;
use Auctane\Api\Request\Authenticator;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Exception;


/**
 * Class Check
 * @package Auctane\Api\Model
 */
class Check implements CheckInterface
{
    /** @var Authenticator */
    private $authenticator;
    /** @var Http */
    private $request;


    /**
     * Check constructor.
     * @param Authenticator $authenticator
     * @param Http $request
     */
    public function __construct(
        Authenticator $authenticator,
        Http $request
    )
    {
        $this->authenticator = $authenticator;
        $this->request = $request;
    }

    /**
     * @return bool
     * @throws AuthenticationFailedException
     * @throws LocalizedException
     */
    public function check(): bool
    {
        if (!$this->authenticator->authenticate($this->request)) {
            throw new LocalizedException(__('Authentication failed.'), null, Exception::HTTP_UNAUTHORIZED);
        }

        return true;
    }
}
