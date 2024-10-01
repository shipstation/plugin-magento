<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class SalesOrderCustomStatusMappings
{
    /** @var array  */
    public array $mappings;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        $this->mappings = $data ?? [];
    }
}
