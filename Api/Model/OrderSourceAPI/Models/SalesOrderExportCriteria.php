<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class SalesOrderExportCriteria
{
    /** @var string|mixed|null  */
    public ?string $from_date_time;
    /** @var string|mixed|null  */
    public ?string $to_date_time;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        $this->from_date_time = $data['from_date_time'] ?? null;
        $this->to_date_time = $data['to_date_time'] ?? null;
    }
}
