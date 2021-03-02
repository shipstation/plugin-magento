<?php

namespace Auctane\Api\Api;

use Auctane\Api\Exception\AuthenticationFailedException;


/**
 * Interface CheckInterface
 * @package Auctane\Api\Api
 */
interface CheckInterface
{
    /**
     * GET for GET api
     * @return bool
     * @throws AuthenticationFailedException
     */
    public function check(): bool;
}
