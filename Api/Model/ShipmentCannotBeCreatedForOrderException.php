<?php
/**
 * Copyright © Novatize. All rights reserved.
 */

namespace Auctane\Api\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;

class ShipmentCannotBeCreatedForOrderException extends LocalizedException
{
    public function __construct($orderId)
    {
        parent::__construct(__("Shipment can not be created for Order : %1", $orderId), null, 400);
    }
}
