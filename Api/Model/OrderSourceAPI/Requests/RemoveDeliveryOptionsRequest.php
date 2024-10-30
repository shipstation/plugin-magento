<?php

namespace Auctane\Api\Model\OrderSourceAPI\Requests;

class RemoveDeliveryOptionsRequest extends RequestBase
{
    /** @var string Identifier for the connection in the order source */
    public string $connection_id;

    /**
     * Constructor to initialize the object with JSON payload.
     * @param array|null $data
     */
    public function __construct($data = null)
    {
        parent::__construct($data);
        if ($data !== null) {
            $this->connection_id = $data['connection_id'] ?? null;
        }
    }
}
