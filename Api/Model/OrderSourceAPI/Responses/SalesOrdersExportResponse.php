<?php

namespace Auctane\Api\Model\OrderSourceAPI\Responses;

use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrder;

class SalesOrdersExportResponse
{
    /** @var SalesOrder[] */
    public array $sales_orders;

    /** @var string|null */
    public ?string $cursor;
}
