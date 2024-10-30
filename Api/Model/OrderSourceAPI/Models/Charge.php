<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class Charge
{
    /**
     * The amount of the currency
     *
     * @var float
     */
    public float $amount;

    /**
     * A description for display purposes only
     *
     * @var string
     */
    public string $description;

    /**
     * Constructor for a Charge
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->amount = $data['amount'] ?? 0.0;
        $this->description = $data['description'] ?? '';
    }
}
