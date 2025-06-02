<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

class ProductIdentifiers
{
    /** @var string|mixed|null A stock-keeping unit associated with a product by the order source */
    public ?string $sku = null;
    /** @var string|mixed|null A universal product code associated with a product */
    public ?string $upc = null;
    /** @var string|mixed|null An international standard book number associated with a product */
    public ?string $isbn = null;
    /** @var string|mixed|null An Amazon standard identification number associated with a product */
    public ?string $asin = null;
    /** @var string|mixed|null A stock-keeping unit associated with the fulfillment of an order */
    public ?string $fulfillment_sku = null;
    /** @var string|mixed|null The identifier needed to set and retrieve inventory levels  */
    public ?string $inventory_id = null;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data !== null) {
            $this->sku = $data['sku'] ?? null;
            $this->upc = $data['upc'] ?? null;
            $this->isbn = $data['isbn'] ?? null;
            $this->asin = $data['asin'] ?? null;
            $this->fulfillment_sku = $data['fulfillment_sku'] ?? null;
            $this->inventory_id = $data['inventory_id'] ?? null;
        }
    }
}
