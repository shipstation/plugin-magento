<?php

namespace Auctane\Api\Model\OrderSourceAPI\Requests;

use Auctane\Api\Model\OrderSourceAPI\Models\InventoryPushItem;

/**
 * A request to update inventory in an order source.
 */
class InventoryPushRequest extends RequestBase
{
    /**
     * A list of inventory items that need to be updated.
     *
     * @var InventoryPushItem[]
     */
    public array $items;

    /**
     * An optional cursor if one was provided by the InventoryPushResponse.
     *
     * @var string|null
     */
    public ?string $cursor;

    /**
     * Constructor from JSON payload.
     *
     * @param array|null $data JSON payload as an associative array.
     */
    public function __construct(array $data = null)
    {
        if ($data) {
            $this->items = isset($data['items']) ? $data['items'] : [];
            $this->cursor = isset($data['cursor']) ? $data['cursor'] : null;
        } else {
            $this->items = [];
            $this->cursor = null;
        }
    }
}
