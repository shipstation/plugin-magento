<?php

namespace Auctane\Api\Model\OrderSourceAPI\Responses;

class RemoveDeliveryOptionsResponse
{
    /** @var bool|mixed  */
    public bool $succeeded;
    /** @var string|mixed|null  */
    public ?string $failure_reason;
}
