<?php

namespace Magento\InventoryApi\Api;

/**
 * Mock interface for Magento\InventoryApi\Api\GetSourceItemsBySkuInterface
 */
interface GetSourceItemsBySkuInterface
{
    /**
     * Get source items by SKU
     *
     * @param string $sku
     * @return \Magento\InventoryApi\Api\Data\SourceItemInterface[]
     */
    public function execute(string $sku): array;
}