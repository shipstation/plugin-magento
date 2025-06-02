<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

class InventoryFetchCriteria
{
    /**
     * @var string[]|null
     */
    public ?array $skus;

    /**
     * @var string|null
     */
    public ?string $from_date_time;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->skus = $data['skus'] ?? null;
            $this->from_date_time = $data['from_date_time'] ?? null;
        }
    }
}
