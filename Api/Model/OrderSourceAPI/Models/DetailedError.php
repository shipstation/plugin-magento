<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class DetailedError
{
    /** @var string|null */
    public ?string $external_error_code;

    /** @var string|null */
    public ?string $message;

    /** @var int|null */
    public ?int $external_http_status_code;

    /** @var string|null */
    public ?string $raw_external_context;

    /** @var string|null */
    public ?string $standardized_error_code;

    public function __construct($jsonPayload = null)
    {
        if ($jsonPayload) {
            $data = json_decode($jsonPayload, true);
            $this->external_error_code = $data['external_error_code'] ?? null;
            $this->message = $data['message'] ?? null;
            $this->external_http_status_code = $data['external_http_status_code'] ?? null;
            $this->raw_external_context = $data['raw_external_context'] ?? null;
            $this->standardized_error_code = $data['standardized_error_code'] ?? null;
        } else {
            $this->external_error_code = null;
            $this->message = null;
            $this->external_http_status_code = null;
            $this->raw_external_context = null;
            $this->standardized_error_code = null;
        }
    }
}
