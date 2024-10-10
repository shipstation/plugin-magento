<?php

namespace Auctane\Api\Model\OrderSourceAPI\Responses;

use Auctane\Api\Model\OrderSourceAPI\Models\InventoryFetchItem;
use Auctane\Api\Model\OrderSourceAPI\Models\InventoryItemError;

class InventoryFetchResponse
{
    /** @var string|null The next cursor to use for the next page of inventory */
    public ?string $cursor;
    /** @var string|null Any messages associated with the results inclosed */
    public ?string $message;
    /** @var InventoryItemError[]|null Any errros associated with the results included */
    public ?array $errors;
    /** @var InventoryFetchItem[]|null The inventory items being returned for the current page */
    public ?array $items;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->errors = [];
        $this->items = [];
    }
}
