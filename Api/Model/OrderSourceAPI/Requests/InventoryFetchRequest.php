<?php

namespace Auctane\Api\Model\OrderSourceAPI\Requests;

use Auctane\Api\Model\OrderSourceAPI\Models\InventoryFetchCriteria;

class InventoryFetchRequest extends RequestBase
{
    /**
     * @var string|null The cursor is provided in the InventoryFetchResponse.
     * If provided, the cursor is used to fetch the next page of inventory.
     */
    public ?string $cursor;

    /**
     * @var InventoryFetchCriteria|null Criteria to use for fetching specific inventory.
     */
    public ?InventoryFetchCriteria $criteria;

    /**
     * @var string|null f provided, try only fetch inventory that has been updated since
     * the specified date and time.
     */
    public ?string $since_date;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        parent::__construct($data);
        if ($data) {
            $this->cursor = $data['cursor'] ?? null;
            $this->criteria = isset($data['criteria']) ? new InventoryFetchCriteria($data['criteria']) : null;
            $this->since_date = $data['since_date'] ?? null;
        }
    }
}
