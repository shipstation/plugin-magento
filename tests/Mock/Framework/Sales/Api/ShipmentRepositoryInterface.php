<?php

namespace Magento\Sales\Api;

/**
 * Mock interface for Magento\Sales\Api\ShipmentRepositoryInterface
 */
interface ShipmentRepositoryInterface
{
    /**
     * Get shipment by ID
     *
     * @param int $id
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     */
    public function get($id);

    /**
     * Get list of shipments
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Sales\Api\Data\ShipmentSearchResultInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Save shipment
     *
     * @param \Magento\Sales\Api\Data\ShipmentInterface $entity
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     */
    public function save(\Magento\Sales\Api\Data\ShipmentInterface $entity);

    /**
     * Delete shipment
     *
     * @param \Magento\Sales\Api\Data\ShipmentInterface $entity
     * @return bool
     */
    public function delete(\Magento\Sales\Api\Data\ShipmentInterface $entity);

    /**
     * Delete shipment by ID
     *
     * @param int $id
     * @return bool
     */
    public function deleteById($id);
}