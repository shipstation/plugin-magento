<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class DetailedError
{
    /** @var string|null */
    public $external_error_code;

    /** @var string|null */
    public $message;

    /** @var int|null */
    public $external_http_status_code;

    /** @var string|null */
    public $raw_external_context;

    /** @var string */
    public $standardized_error_code;

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

class ShipEngineErrorCode
{
    const GENERIC = 'generic';
    const SERIALIZATION = 'serialization';
    const VALIDATION = 'validation';
    const EXTERNAL_CLIENT_ERROR = 'external_client_error';
}
