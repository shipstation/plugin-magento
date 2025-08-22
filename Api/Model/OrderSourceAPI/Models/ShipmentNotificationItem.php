<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class ShipmentNotificationItem
{
    /** @var string|mixed|null The order source's unique identifier for the line item  */
    public ?string $line_item_id;
    /** @var string|mixed A description of the sales order item - which may differ from the product description */
    public string $description;
    /** @var string|mixed|null The unique identifier for the item that was shipped */
    public ?string $sku;
    /** @var string|mixed|null This ID of this product in the vendor API */
    public ?string $product_id;
    /** @var int|mixed The number of items of this SKU that were shipped */
    public int $quantity;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->line_item_id = $data['line_item_id'] ?? null;
            $this->description = $data['description'];
            $this->sku = $data['sku'] ?? null;
            $this->product_id = $data['product_id'] ?? null;
            $this->quantity = $data['quantity'];
        }
    }
}
