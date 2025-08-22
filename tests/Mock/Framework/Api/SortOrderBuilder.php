<?php

namespace Magento\Framework\Api;

/**
 * Mock class for Magento\Framework\Api\SortOrderBuilder
 */
class SortOrderBuilder
{
    private string $field = '';
    private string $direction = 'ASC';

    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    public function setDirection($direction)
    {
        $this->direction = $direction;
        return $this;
    }

    public function setAscendingDirection()
    {
        $this->direction = 'ASC';
        return $this;
    }

    public function setDescendingDirection()
    {
        $this->direction = 'DESC';
        return $this;
    }

    public function create()
    {
        return new SortOrder([
            'field' => $this->field,
            'direction' => $this->direction
        ]);
    }
}