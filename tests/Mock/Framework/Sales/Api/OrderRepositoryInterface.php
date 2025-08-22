<?php

namespace Magento\Sales\Api;

/**
 * Mock interface for Magento\Sales\Api\OrderRepositoryInterface
 */
interface OrderRepositoryInterface
{
    /**
     * Get order by ID
     *
     * @param int $id
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function get($id);

    /**
     * Get list of orders
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Save order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $entity
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function save(\Magento\Sales\Api\Data\OrderInterface $entity);

    /**
     * Delete order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $entity
     * @return bool
     */
    public function delete(\Magento\Sales\Api\Data\OrderInterface $entity);

    /**
     * Delete order by ID
     *
     * @param int $id
     * @return bool
     */
    public function deleteById($id);
}