<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

class OriginalOrderSource
{
    /** @var string|mixed|null A unique identifier for the store inside of the original marketplace. */
    public ?string $source_id;
    /** @var string|mixed|null The ShipEngine API Code of the original marketplace. Check with the Engine team for allowed values. */
    public ?string $marketplace_code;
    /** @var string|mixed|null The unique identifier for the order at the original marketplace. */
    public ?string $order_id;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if (is_array($data)) {
            $this->source_id = $data['source_id'] ?? null;
            $this->marketplace_code = $data['marketplace_code'] ?? null;
            $this->order_id = $data['order_id'] ?? null;
        }
    }
}
