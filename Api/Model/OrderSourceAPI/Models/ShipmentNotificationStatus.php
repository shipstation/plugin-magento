<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

enum ShipmentNotificationStatus: string
{
    case Success = 'success';
    case Failure = 'failure';
    case Pending = 'pending';
}
