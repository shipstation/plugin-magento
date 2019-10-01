<?php
/**
 * Copyright © Novatize. All rights reserved.
 */

namespace Auctane\Api\Model;

use Exception;

class ShipmentCannotBeCreatedForOrderException extends Exception
{
    public function __construct($orderId)
    {
        parent::__construct("Shipment can not be created for Order : {$orderId}", 400);
    }
}
