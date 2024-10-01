<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

/**
 * @description A Custom Field Option describes a possible value that a store could
 * send as a `sales_order_field_mapping` during `sales_orders_export` requests
 */
class CustomFieldOption
{
    /**
     * @var string
     * The name of the field being requested, which will be sent as`custom_field_*` when chosen by the seller.
     * This value is expected to be unique per integration.
     */
    public string $Field;

    /**
     * @var string
     * @description The name of this option to use in the UI
     */
    public string $DisplayName;

    /**
     * @var bool|null
     * @description Set to true if this option must be enabled by support
     */
    public ?bool $IsAdmin = false;

    /**
     * @var bool|null
     * @description Set to true if this option shouldn't be shown in the UI.
     * This does not prevent the option from being sent to the API for stores which have already chosen it
     */
    public ?bool $IsDeprecated = false;

    /**
     * CustomFieldOption constructor.
     * @param array $data JSON payload with the structure to initialize the object properties
     */
    public function __construct(array $data = [])
    {
        $this->Field = $data['Field'] ?? '';
        $this->DisplayName = $data['DisplayName'] ?? '';
        $this->IsAdmin = $data['IsAdmin'] ?? false;
        $this->IsDeprecated = $data['IsDeprecated'] ?? false;
    }
}
