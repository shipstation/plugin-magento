<?php
/**
 * Copyright © Novatize. All rights reserved.
 */

namespace Auctane\Api\Model;

class ApiKeyGenerator
{
    const API_KEY_BYTES_AMOUNT = 16;

    /**
     * @return string
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function generate(): string
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return bin2hex(random_bytes(self::API_KEY_BYTES_AMOUNT));
    }
}
