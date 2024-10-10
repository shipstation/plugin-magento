<?php

namespace Auctane\Api\Model\OrderSourceAPI\Responses;

use Auctane\Api\Model\OrderSourceAPI\Models\InventoryItemError;

class InventoryPushResponse
{
    /** @var string|null Any messages associated with the results inclosed  */
    public ?string $message;
    /** @var InventoryItemError[]|null Any errors associated with the results included  */
    public ?array $errors;
    /** @var string|mixed|null The next cursor to use for the next page of inventory  */
    public ?string $cursor;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->cursor = $data['cursor'] ?? '';
            $this->message = $data['message'] ?? null;
            $this->errors = $data['errors'] ?? null;
        } else {
            $this->cursor = null;
            $this->message = null;
            $this->errors = [];
        }
    }
}
