<?php
/**
 * Copyright © Novatize. All rights reserved.
 */

namespace Auctane\Api\Model;

use Magento\Framework\Exception\LocalizedException;


/**
 * Class OrderDoesNotExistException
 * @package Auctane\Api\Model
 */
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
