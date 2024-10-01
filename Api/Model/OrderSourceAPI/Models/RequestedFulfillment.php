<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

/**
 * The fulfillment requested by the marketplace or the buyer
 */
class RequestedFulfillment
{
    /** @var string|mixed|null Identifier for the requested fulfillment from the order source */
    public ?string $requested_fulfillment_id;
    /** @var Address|mixed|null Who the order should be shipped to */
    public ?Address $ship_to;
    /** @var SalesOrderItem[] The items that should be shipped */
    public array $items; // Array of SalesOrderItem instances
    /** @var RequestedFulfillmentExtensions|null Additional information about this fulfillment */
    public ?RequestedFulfillmentExtensions $extensions;
    /** @var mixed|null Preferences about how the order is shipped */
    public ShippingPreferences $shipping_preferences;

    /**
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if (is_array($data)) {
            $this->requested_fulfillment_id = $data['requested_fulfillment_id'] ?? null;
            $this->ship_to = $data['ship_to'] ?? null; // Pass Address instance
            $this->items = $data['items'] ?? []; // Populate with SalesOrderItem instances
            $this->extensions = !empty($data['extensions'])
                ? new RequestedFulfillmentExtensions($data['extensions']) : null;
            $this->shipping_preferences = $data['shipping_preferences'] ?? null;
        }
    }
}
