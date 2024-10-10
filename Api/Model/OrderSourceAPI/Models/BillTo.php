<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

/**
 * @description This model represents information for who is being billed
 */
class BillTo extends Address
{
    /**
     * The email address of the person being billed
     *
     * @var string|null
     */
    protected ?string $email;

    /**
     * This creates a BillTo Address
     *
     * @param array|null $data
     */
    public function __construct($data = null)
    {
        parent::__construct($data);

        if ($data) {
            $this->email = $data['email'] ?? null;
        }
    }
}
