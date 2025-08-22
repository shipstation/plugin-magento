<?php

namespace Magento\Sales\Model\Order;

/**
 * Mock class for Magento\Sales\Model\Order\ShipmentFactory
 */
class ShipmentFactory
{
    /**
     * Create shipment instance
     *
     * @param array $data
     * @return Shipment
     */
    public function create(array $data = [])
    {
        return new Shipment($data);
    }
}