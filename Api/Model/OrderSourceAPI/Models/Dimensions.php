<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

/**
 * The definition of dimensions for an item or package
 */
class Dimensions
{
    /** @var DimensionsUnit */
    public DimensionsUnit $unit;
    /** @var float|null */
    public ?float $height;
    /** @var float|null */
    public ?float $width;
    /** @var float|null */
    public ?float $length;

    /**
     * Constructor for Dimensions
     *
     * @param array|null $data Optional JSON payload to initialize the object
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->unit = $data['unit'] ?? null;
            $this->height = $data['height'] ?? null;
            $this->width = $data['width'] ?? null;
            $this->length = $data['length'] ?? null;
        }
    }
}
