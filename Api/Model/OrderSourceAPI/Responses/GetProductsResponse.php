<?php

namespace Auctane\Api\Model\OrderSourceAPI\Responses;

use Auctane\Api\Model\OrderSourceAPI\Models\Product;

class GetProductsResponse
{
    /** @var Product[] The list of Products */
    public array $products;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->products = [];
    }
}
