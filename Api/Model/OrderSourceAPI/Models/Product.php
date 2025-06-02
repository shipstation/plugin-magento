<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

class Product
{
    /** @var string|mixed This ID of this product in the vendor API */
    public string $product_id;
    /** @var string|mixed The product name */
    public string $name;
    /** @var string|mixed|null The product description */
    public ?string $description = null;
    /** @var ProductIdentifiers|null Additional identifiers associated with this product */
    public ?ProductIdentifiers $identifiers = null;
    /** @var ProductDetail[]|null A list of details associated with this product */
    public ?array $details = [];
    /** @var float|null The cost of a single product */
    public ?float $unit_cost = null;
    /** @var Weight|null The weight of the product */
    public ?Weight $weight = null;
    /** @var Dimensions|null The dimensions of the product */
    public ?Dimensions $dimensions = null;
    /** @var ProductUrls|null The urls associated with a product */
    public ?ProductUrls $urls = null;
    /** @var string|mixed|null The location the product can be found in a warehouse */
    public ?string $location = null;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data !== null) {
            $this->product_id = $data['product_id'];
            $this->name = $data['name'];
            $this->description = $data['description'] ?? null;
            $this->identifiers = isset($data['identifiers']) ? new ProductIdentifiers($data['identifiers']) : null;
            $this->details = [];

            if (isset($data['details'])) {
                foreach ($data['details'] as $detail) {
                    $this->details[] = new ProductDetail($detail);
                }
            }

            $this->unit_cost = $data['unit_cost'] ?? null;
            $this->weight = isset($data['weight']) ? new Weight($data['weight']) : null;
            $this->dimensions = isset($data['dimensions']) ? new Dimensions($data['dimensions']) : null;
            $this->urls = isset($data['urls']) ? new ProductUrls($data['urls']) : null;
            $this->location = $data['location'] ?? null;
        }
    }
}
