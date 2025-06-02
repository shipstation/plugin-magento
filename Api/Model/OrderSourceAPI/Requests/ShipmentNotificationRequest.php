<?php

namespace Auctane\Api\Model\OrderSourceAPI\Requests;

use Auctane\Api\Model\OrderSourceAPI\Models\ShipmentNotification;

class ShipmentNotificationRequest extends RequestBase
{
    /** @var ShipmentNotification[]  */
    public array $notifications;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        parent::__construct($data);
        if ($data) {
            $this->notifications = array_map(
                function ($notification) {
                    return new ShipmentNotification($notification);
                },
                $data['notifications']
            );
        }
    }
}
