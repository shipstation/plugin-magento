<?php
/**
 * Copyright © Novatize. All rights reserved.
 */

namespace Auctane\Api\Model;

class ApiKeyGenerator
{
    const API_KEY_BYTES_AMOUNT = 16;

    public function generate()
    {
        return bin2hex(random_bytes(self::API_KEY_BYTES_AMOUNT));
    }
}
