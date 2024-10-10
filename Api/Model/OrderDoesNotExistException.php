<?php
/**
 * Copyright © Novatize. All rights reserved.
 */

namespace Auctane\Api\Model;

use Magento\Framework\Exception\LocalizedException;

class OrderDoesNotExistException extends LocalizedException
{
    /**
     * OrderDoesNotExistException constructor.
     * @param $orderId
     */
    public function __construct($orderId)
    {
        parent::__construct(__("Order '%1' does not exist", $orderId), null, 400);
    }
}
