<?php

namespace Magento\Framework\Api;

/**
 * Mock class for Magento\Framework\Api\SearchCriteria
 */
class SearchCriteria implements SearchCriteriaInterface
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getFilterGroups()
    {
        return $this->data['filter_groups'] ?? [];
    }

    public function getSortOrders()
    {
        return $this->data['sort_orders'] ?? [];
    }

    public function getPageSize()
    {
        return $this->data['page_size'] ?? 0;
    }

    public function getCurrentPage()
    {
        return $this->data['current_page'] ?? 1;
    }

    public function setFilterGroups(array $filterGroups)
    {
        $this->data['filter_groups'] = $filterGroups;
        return $this;
    }

    public function setSortOrders(array $sortOrders)
    {
        $this->data['sort_orders'] = $sortOrders;
        return $this;
    }

    public function setPageSize($pageSize)
    {
        $this->data['page_size'] = $pageSize;
        return $this;
    }

    public function setCurrentPage($currentPage)
    {
        $this->data['current_page'] = $currentPage;
        return $this;
    }
}