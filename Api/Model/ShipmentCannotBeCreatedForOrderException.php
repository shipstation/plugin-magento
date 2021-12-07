<?php
/**
 * Copyright © Novatize. All rights reserved.
 */

namespace Auctane\Api\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;

class ShipmentCannotBeCreatedForOrderException extends LocalizedException
{
    public function __construct($orderId, array $errors)
    {
        parent::__construct(__("Shipment can not be created for Order #%1. Here the reasons: %2", $orderId, implode("; ", $errors)), null, 400);
    }
}
