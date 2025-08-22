<?php

namespace Magento\Sales\Api;

/**
 * Mock interface for Magento\Sales\Api\ProductRepositoryInterface
 * Note: This is different from Catalog\Api\ProductRepositoryInterface
 */
interface ProductRepositoryInterface
{
    /**
     * Get product by ID
     *
     * @param int $productId
     * @return \Magento\Sales\Api\Data\ProductInterface
     */
    public function getById($productId);

    /**
     * Get product by SKU
     *
     * @param string $sku
     * @return \Magento\Sales\Api\Data\ProductInterface
     */
    public function get($sku);

    /**
     * Get list of products
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Sales\Api\Data\ProductSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}