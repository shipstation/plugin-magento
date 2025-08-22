<?php

namespace Magento\Framework\Api;

/**
 * Mock class for Magento\Framework\Api\SearchCriteriaBuilder
 */
class SearchCriteriaBuilder
{
    private array $filters = [];
    private array $sortOrders = [];
    private int $pageSize = 0;
    private int $currentPage = 1;

    public function addFilter($field, $value, $conditionType = 'eq')
    {
        $this->filters[] = [
            'field' => $field,
            'value' => $value,
            'condition_type' => $conditionType
        ];
        return $this;
    }

    public function addSortOrder($field, $direction = 'ASC')
    {
        $this->sortOrders[] = [
            'field' => $field,
            'direction' => $direction
        ];
        return $this;
    }

    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
        return $this;
    }

    public function create()
    {
        return new SearchCriteria([
            'filters' => $this->filters,
            'sort_orders' => $this->sortOrders,
            'page_size' => $this->pageSize,
            'current_page' => $this->currentPage
        ]);
    }
}