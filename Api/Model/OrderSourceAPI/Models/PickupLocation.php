<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

class PickupLocation
{
    /** @var string */
    public string $carrier_id;

    /** @var string */
    public string $relay_id;

    /**
     * PickupLocation constructor.
     *
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data !== null) {
            $this->carrier_id = $data['carrier_id'] ?? null;
            $this->relay_id = $data['relay_id'] ?? null;
        }
    }
}
