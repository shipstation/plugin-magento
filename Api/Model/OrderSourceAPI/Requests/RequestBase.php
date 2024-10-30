<?php

namespace Auctane\Api\Model\OrderSourceAPI\Requests;

use Auctane\Api\Model\OrderSourceAPI\Models\Auth;

abstract class RequestBase
{
    /** @var string|null A randomly generated transaction ID, used to correlate the request and response */
    public ?string $transaction_id;
    /** @var Auth|null The authorization information necessary to fulfill this request. */
    public ?Auth $auth;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->auth = new Auth($data);
            $this->transaction_id = $data['transaction_id'] ?? null;
        }
    }
}
