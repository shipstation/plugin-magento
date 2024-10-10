<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

class SalesOrderStatusMapping
{
    /** @var string|mixed The raw status string used by the order source (csv list) */
    public string $source_status;
    /** @var SalesoRderStatus The sales order status this should map to */
    public SalesOrderStatus $maps_to;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->source_status = $data['source_status'] ?? '';
            $this->maps_to = $data['maps_to'] ?? null;
        }
    }
}
