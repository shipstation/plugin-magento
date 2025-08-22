<?php

namespace Magento\Sales\Model\ResourceModel\Order;

/**
 * Mock class for Magento\Sales\Model\ResourceModel\Order\Collection
 */
class Collection implements \Iterator, \Countable
{
    private array $items = [];
    private int $position = 0;

    public function __construct(array $data = [])
    {
        $this->items = $data;
    }

    public function addFieldToFilter($field, $condition = null)
    {
        return $this;
    }

    public function addFieldToSelect($field)
    {
        return $this;
    }

    public function setOrder($field, $direction = 'ASC')
    {
        return $this;
    }

    public function setPageSize($size)
    {
        return $this;
    }

    public function setCurPage($page)
    {
        return $this;
    }

    public function getSize()
    {
        return count($this->items);
    }

    public function getItems()
    {
        return $this->items;
    }

    public function setItems(array $items)
    {
        $this->items = $items;
        return $this;
    }

    // Iterator interface
    public function current()
    {
        return $this->items[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    // Countable interface
    public function count(): int
    {
        return count($this->items);
    }
}