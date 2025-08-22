<?php

namespace Magento\Catalog\Api;

/**
 * Mock interface for Magento\Catalog\Api\ProductRepositoryInterface
 */
interface ProductRepositoryInterface
{
    /**
     * Get product by ID
     *
     * @param int $productId
     * @param bool $editMode
     * @param int|null $storeId
     * @param bool $forceReload
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function getById($productId, $editMode = false, $storeId = null, $forceReload = false);

    /**
     * Get product by SKU
     *
     * @param string $sku
     * @param bool $editMode
     * @param int|null $storeId
     * @param bool $forceReload
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function get($sku, $editMode = false, $storeId = null, $forceReload = false);

    /**
     * Get list of products
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Catalog\Api\Data\ProductSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Save product
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param bool $saveOptions
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function save(\Magento\Catalog\Api\Data\ProductInterface $product, $saveOptions = false);

    /**
     * Delete product
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function delete(\Magento\Catalog\Api\Data\ProductInterface $product);

    /**
     * Delete product by ID
     *
     * @param int $productId
     * @return bool
     */
    public function deleteById($productId);
}