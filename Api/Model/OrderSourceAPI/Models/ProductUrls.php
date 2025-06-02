<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

class ProductUrls
{
    /** @var string|mixed|null A link to the product page if available */
    public ?string $product_url = null;
    /** @var string|mixed|null A link to the image for a product if available */
    public ?string $image_url = null;
    /** @var string|mixed|null A link to the image for use in platform thumbnails */
    public ?string $thumbnail_url = null;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data !== null) {
            $this->product_url = $data['product_url'] ?? null;
            $this->image_url = $data['image_url'] ?? null;
            $this->thumbnail_url = $data['thumbnail_url'] ?? null;
        }
    }
}
