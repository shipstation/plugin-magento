<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class InventoryFetchItem extends InventoryItemBase
{
    /** @var string|null Time stamp (ISO 8601) indicating when the current values were fetched from the source system. */
    public ?string $fetched_at;

    /** @var int|null The total stock quantity needed to fulfill all pending orders */
    public ?int $committed_quantity;

    /**
     * InventoryFetchItem constructor.
     * Allows for a JSON payload that assigns to each property or an empty constructor.
     *
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        parent::__construct($data);
        if ($data) {
            $this->fetched_at = $data['fetched_at'] ?? null;
            $this->committed_quantity = $data['committed_quantity']  ?? null;
        }
    }
}
