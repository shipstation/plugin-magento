<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class Document
{
    /**
     * @var array|null
     */
    public ?array $type;

    /**
     * @var string|null
     */
    public ?string $data;

    /**
     * @var string
     */
    public string $format;

    /**
     * Document constructor.
     *
     * @param array|null $data
     */
    public function __construct(?array $data)
    {
        if ($data) {
            $this->type = $data['type'] ?? null;
            $this->data = $data['data'] ?? null;
            $this->format = $data['format'] ?? null;
        }
    }
}
