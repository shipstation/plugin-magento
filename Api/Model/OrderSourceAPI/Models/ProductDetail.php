<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

class ProductDetail
{
    /** @var string|mixed  The type of the product detail. Example (non-exhaustive) values: 'Color', 'CountryOfManufacture', 'Shape', 'Size', 'Style' */
    public string $name;
    /** @var string|mixed  The value of the product detail */
    public string $value;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->name = $data['name'];
            $this->value = $data['value'];
        }
    }
}
