<?php

namespace Auctane\Api\Model\OrderSourceAPI\Responses;

class RegisterDeliveryOptionsResponse
{
    /** @var string|mixed  */
    public string $connection_id;
    /** @var bool|mixed  */
    public bool $succeeded;
    /** @var string|mixed|null  */
    public ?string $failure_reason;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->connection_id = $data['connection_id'];
            $this->succeeded = $data['succeeded'];
            $this->failure_reason = $data['failure_reason'] ?? null;
        } else {
            $this->connection_id = '';
            $this->succeeded = false;
            $this->failure_reason = null;
        }
    }
}
