<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

/**
 * Contact information for the buyer of this sales order
 */
class Buyer
{
    /**
     * An ID for this buyer in the vendor API
     *
     * @var string|null
     */
    public ?string $buyer_id;

    /**
     * The full name of the buyer
     *
     * @var string|null
     */
    public ?string $name;

    /**
     * The primary email address of the buyer
     *
     * @var string|null
     */
    public ?string $email;

    /**
     * The primary phone number of the buyer
     *
     * @var string|null
     */
    public ?string $phone;

    /**
     * Buyer constructor.
     * @param array|null $data JSON data to populate the Buyer class properties
     */
    public function __construct(?array $data = null)
    {
        if ($data !== null) {
            $this->buyer_id = $data['buyer_id'] ?? null;
            $this->name = $data['name'] ?? null;
            $this->email = $data['email'] ?? null;
            $this->phone = $data['phone'] ?? null;
        }
    }
}
