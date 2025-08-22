<?php

namespace Magento\Sales\Model;

/**
 * Mock class for Magento\Sales\Model\Order
 */
class Order
{
    private array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function load($id)
    {
        return $this;
    }

    public function getId()
    {
        return $this->data['entity_id'] ?? null;
    }

    public function getIncrementId()
    {
        return $this->data['increment_id'] ?? null;
    }

    public function getState()
    {
        return $this->data['state'] ?? 'new';
    }

    public function getStatus()
    {
        return $this->data['status'] ?? 'pending';
    }

    public function canShip()
    {
        return $this->data['can_ship'] ?? true;
    }

    public function getIsVirtual()
    {
        return $this->data['is_virtual'] ?? false;
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
}