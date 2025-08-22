<?php

namespace Magento\Sales\Model\Order;

/**
 * Mock class for Magento\Sales\Model\Order\Shipment
 */
class Shipment
{
    private array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getId()
    {
        return $this->data['entity_id'] ?? null;
    }

    public function getIncrementId()
    {
        return $this->data['increment_id'] ?? null;
    }

    public function getOrderId()
    {
        return $this->data['order_id'] ?? null;
    }

    public function setData($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    public function getData($key = null)
    {
        if ($key === null) {
            return $this->data;
        }
        return $this->data[$key] ?? null;
    }

    public function save()
    {
        return $this;
    }
}