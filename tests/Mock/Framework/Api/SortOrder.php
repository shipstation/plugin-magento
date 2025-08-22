<?php

namespace Magento\Framework\Api;

/**
 * Mock class for Magento\Framework\Api\SortOrder
 */
class SortOrder
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getField()
    {
        return $this->data['field'] ?? '';
    }

    public function getDirection()
    {
        return $this->data['direction'] ?? 'ASC';
    }

    public function setField($field)
    {
        $this->data['field'] = $field;
        return $this;
    }

    public function setDirection($direction)
    {
        $this->data['direction'] = $direction;
        return $this;
    }
}