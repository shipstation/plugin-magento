<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

enum InventoryActionErrorCategoryType: string
{
    case NotFound = 'not_found';
    case ResourceLocked = 'resource_locked';
}
