<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class SalesOrderCustomFieldMappings
{
    /** @var string|mixed|null  */
    public ?string $custom_field_1;
    /** @var string|mixed|null  */
    public ?string $custom_field_2;
    /** @var string|mixed|null  */
    public ?string $custom_field_3;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->custom_field_1 = $data['custom_field_1'] ?? null;
            $this->custom_field_2 = $data['custom_field_2'] ?? null;
            $this->custom_field_3 = $data['custom_field_3'] ?? null;
        }
    }
}
