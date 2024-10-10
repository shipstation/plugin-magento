<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

/**
 * Represents the potential statuses a ShipStation order can have.
 */
enum SalesOrderStatus: string
{
    case AwaitingPayment = 'AwaitingPayment';
    case AwaitingShipment = 'AwaitingShipment';
    case Cancelled = 'Cancelled';
    case Completed = 'Completed';
    case OnHold = 'OnHold';
    case PendingFulfillment = 'PendingFulfillment';
}
