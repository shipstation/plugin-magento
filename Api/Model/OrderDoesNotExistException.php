<?php
/**
 * Copyright © Novatize. All rights reserved.
 */

namespace Auctane\Api\Model;

use Exception;

class OrderDoesNotExistException extends Exception
{
    public function __construct($orderId)
    {
        parent::__construct("Order '{$orderId}' does not exist", 400);
    }
}
