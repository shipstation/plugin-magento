<?php

namespace Auctane\Api\Model\OrderSourceAPI\Requests;

class VerifyDeliveryOptionsRequest extends RequestBase
{
    /**
     * Constructor that allows for a JSON payload.
     *
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        parent::__construct($data);
        if ($data) {
            // Assuming properties assignment from the JSON payload
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }
}
