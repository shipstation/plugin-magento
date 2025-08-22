<?php

namespace Magento\Sales\Model\ResourceModel\Order;

/**
 * Mock class for Magento\Sales\Model\ResourceModel\Order\CollectionFactory
 */
class CollectionFactory
{
    /**
     * Create collection instance
     *
     * @param array $data
     * @return Collection
     */
    public function create(array $data = [])
    {
        return new Collection($data);
    }
}