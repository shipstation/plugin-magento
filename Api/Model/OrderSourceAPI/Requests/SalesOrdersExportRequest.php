<?php

namespace Auctane\Api\Model\OrderSourceAPI\Requests;

use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrderCustomFieldMappings;
use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrderCustomStatusMappings;
use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrderExportCriteria;

class SalesOrdersExportRequest extends RequestBase
{
    /** @var SalesOrderExportCriteria|null  */
    public ?SalesOrderExportCriteria $criteria;
    /** @var string|mixed|null  */
    public ?string $cursor;
    /** @var SalesOrderCustomFieldMappings|null  */
    public ?SalesOrderCustomFieldMappings $sales_order_field_mappings;
    /** @var SalesOrderCustomStatusMappings|null  */
    public ?SalesOrderCustomStatusMappings $sales_order_status_mappings;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        parent::__construct($data);
        if ($data != null) {
            $this->criteria = isset($data['criteria']) ? new SalesOrderExportCriteria($data['criteria']) : null;
            $this->cursor = $data['cursor'] ?? null;
            $this->sales_order_field_mappings = isset($data['sales_order_field_mappings'])
                ? new SalesOrderCustomFieldMappings($data['sales_order_field_mappings']) : null;
            $this->sales_order_status_mappings = isset($data['sales_order_status_mappings'])
                ? new SalesOrderCustomStatusMappings($data['sales_order_status_mappings']) : null;
        }
    }
}
