<?php

namespace Auctane\Api\Model\OrderSourceAPI\Requests;

class GetProductsRequest extends RequestBase
{
    /** @var string[]|null The product ids to get data for */
    public ?array $product_ids;

    /**
     * GetProductsRequest constructor.
     * @param array|null $data A JSON payload to initialize properties
     */
    public function __construct(?array $data = null)
    {
        parent::__construct($data);
        if ($data) {
            $this->product_ids = $data['product_ids'] ?? null;
        } else {
            $this->product_ids = null;
        }
    }
}
