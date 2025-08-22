<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

/**
 * Forms the base of an inventory item with their shared properties
 */
abstract class InventoryItemBase
{
    /** @var string|mixed  Merchant-supplied identifier for the product or item  */
    public string $sku;
    /**
     * Unique identifier for the inventory record in the 3rd party integration system (i.e. marketplace, 3PL, WMS, etc).
     * This may be the same as `sku`, or be a JSON encoded string containing all the identifiers required to uniquely
     * specify and update the item.
     *
     * @var string|mixed
     */
    public string $integration_inventory_item_id;
    /** @var int|mixed Stock available to sell – generally this is `onhand` minus `committed` */
    public int $available_quantity;
    /** @var int|mixed|null Stock allocated to specific orders for fulfillment – this will be less than `onhand` */
    public ?int $allocated_quantity;
    /** @var int|mixed|null Stock that is physically present in a location, regardless of commitment / allocation */
    public ?int $onhand_quantity;
    /** @var string|mixed|null Time stamp (ISO 8601) indicating when the current inventory values changed in the source system */
    public ?string $updated_at;
    /** @var string|mixed|null A unique identifier in the source system for the physical building of a quantity of stock */
    public ?string $warehouse_id;

    /**
     * Constructs the Base Inventory Item
     *
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->sku = $data['sku'] ?? null;
            $this->integration_inventory_item_id = $data['integration_inventory_item_id'] ?? null;
            $this->available_quantity = $data['available_quantity'] ?? null;
            $this->allocated_quantity = $data['allocated_quantity'] ?? null;
            $this->onhand_quantity = $data['onhand_quantity'] ?? null;
            $this->updated_at = $data['updated_at'] ?? null;
            $this->warehouse_id = $data['warehouse_id'] ?? null;
        }
    }
}
