<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class InventoryItemError
{
    /**
     * @var string
     */
    public string $integration_inventory_item_id;

    /**
     * @var string|null
     */
    public ?string $sku;

    /**
     * @var string|null
     */
    public ?string $message;

    /**
     * @var InventoryActionErrorCategoryType|null
     */
    public ?InventoryActionErrorCategoryType $category;

    /**
     * Constructor to initialize properties with JSON payload or as empty
     *
     * @param array|null $data JSON data to initialize properties
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->integration_inventory_item_id = $data['integration_inventory_item_id'] ?? '';
            $this->sku = $data['sku'] ?? null;
            $this->message = $data['message'] ?? '';
            $this->category = $data['category'] ?? null;
        }
    }
}
