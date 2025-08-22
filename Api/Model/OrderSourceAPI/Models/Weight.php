<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class Weight
{
    /** @var WeightUnit The unit this weight was measured in */
    public WeightUnit $unit;

    /** @var float The value of the weight in weight units */
    public float $value;

    /**
     * Weight constructor.
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->unit = $data['unit'];
            $this->value = $data['value'];
        }
    }
}
