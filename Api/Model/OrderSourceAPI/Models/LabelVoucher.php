<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class LabelVoucher
{
    /** @var string|null Base URL for the Carrier API implementation */
    public ?string $url;

    /** @var string|null Token needed to authenticate with the Carrier API implementation */
    public ?string $token;

    /**
     * Constructor for LabelVoucher
     *
     * @param array|null $data
     */
    public function __construct(?array $data)
    {
        if ($data) {
            $this->url = $data['url'] ?? null;
            $this->token = $data['token'] ?? null;
        }
    }
}
