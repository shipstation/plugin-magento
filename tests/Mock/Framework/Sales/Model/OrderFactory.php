<?php

namespace Magento\Sales\Model;

/**
 * Mock class for Magento\Sales\Model\OrderFactory
 */
class OrderFactory
{
    /**
     * Create order instance
     *
     * @param array $data
     * @return \Magento\Sales\Model\Order
     */
    public function create(array $data = [])
    {
        return new Order($data);
    }
}