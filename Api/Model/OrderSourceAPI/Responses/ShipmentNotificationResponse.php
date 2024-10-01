<?php

namespace Auctane\Api\Model\OrderSourceAPI\Responses;

use Auctane\Api\Model\OrderSourceAPI\Models\ShipmentNotificationResult;

class ShipmentNotificationResponse
{
    /** @var ShipmentNotificationResult[] */
    public array $notification_results;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->notification_results = [];
    }
}
