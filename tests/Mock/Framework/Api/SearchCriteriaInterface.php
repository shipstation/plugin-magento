<?php

namespace Magento\Framework\Api;

/**
 * Mock interface for Magento\Framework\Api\SearchCriteriaInterface
 */
interface SearchCriteriaInterface
{
    /**
     * Get filter groups
     *
     * @return \Magento\Framework\Api\Search\FilterGroup[]
     */
    public function getFilterGroups();

    /**
     * Get sort orders
     *
     * @return \Magento\Framework\Api\SortOrder[]|null
     */
    public function getSortOrders();

    /**
     * Get page size
     *
     * @return int|null
     */
    public function getPageSize();

    /**
     * Get current page
     *
     * @return int|null
     */
    public function getCurrentPage();

    /**
     * Set filter groups
     *
     * @param \Magento\Framework\Api\Search\FilterGroup[] $filterGroups
     * @return $this
     */
    public function setFilterGroups(array $filterGroups);

    /**
     * Set sort orders
     *
     * @param \Magento\Framework\Api\SortOrder[] $sortOrders
     * @return $this
     */
    public function setSortOrders(array $sortOrders);

    /**
     * Set page size
     *
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize($pageSize);

    /**
     * Set current page
     *
     * @param int $currentPage
     * @return $this
     */
    public function setCurrentPage($currentPage);
}