<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class SalesOrderItem
{
    /** @var string|null ID for the line item for the vendor API */
    public ?string $line_item_id;

    /** @var string Description of the sales order item - may differ from the product description */
    public string $description;

    /** @var Product|null Product associated with this order item */
    public ?Product $product;

    /** @var int Item quantity for this sales order item */
    public int $quantity;

    /** @var float|null Amount of the currency per unit */
    public ?float $unit_price;

    /** @var Charge[]|null List of tax charges. The description can convey the jurisdiction */
    public ?array $taxes;

    /** @var Charge[]|null List of shipping charges */
    public ?array $shipping_charges;

    /** @var Charge[]|null List of adjustments applied that influence the order total */
    public ?array $adjustments;

    /** @var string|null URL for the item being purchased */
    public ?string $item_url;

    /** @var string|null (ISO 8601) datetime (UTC) when this item was last modified */
    public ?string $modified_date_time;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->line_item_id = $data['line_item_id'] ?? null;
            $this->description = $data['description'] ?? '';
            $this->product = isset($data['product']) ? new Product($data['product']) : null;
            $this->quantity = $data['quantity'] ?? 0;
            $this->unit_price = $data['unit_price'] ?? null;

            $this->taxes = isset($data['taxes']) ? array_map(fn($tax) => new Charge($tax), $data['taxes']) : [];
            $this->shipping_charges = isset($data['shipping_charges'])
                ? array_map(fn($charge) => new Charge($charge), $data['shipping_charges']) : [];
            $this->adjustments = isset($data['adjustments'])
                ? array_map(fn($adjustment) => new Charge($adjustment), $data['adjustments']) : [];

            $this->item_url = $data['item_url'] ?? null;
            $this->modified_date_time = $data['modified_date_time'] ?? null;
        }
    }
}
