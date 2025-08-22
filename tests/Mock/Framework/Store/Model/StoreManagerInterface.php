<?php

namespace Magento\Store\Model;

/**
 * Mock interface for Magento\Store\Model\StoreManagerInterface
 */
interface StoreManagerInterface
{
    /**
     * Get stores
     *
     * @param bool $withDefault
     * @param bool $codeKey
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    public function getStores($withDefault = false, $codeKey = false);

    /**
     * Get store
     *
     * @param null|string|bool|int|\Magento\Store\Api\Data\StoreInterface $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore($storeId = null);

    /**
     * Get groups
     *
     * @return \Magento\Store\Api\Data\GroupInterface[]
     */
    public function getGroups();

    /**
     * Get group
     *
     * @param null|string|bool|int|\Magento\Store\Api\Data\GroupInterface $groupId
     * @return \Magento\Store\Api\Data\GroupInterface
     */
    public function getGroup($groupId = null);

    /**
     * Get websites
     *
     * @param bool $withDefault
     * @param bool $codeKey
     * @return \Magento\Store\Api\Data\WebsiteInterface[]
     */
    public function getWebsites($withDefault = false, $codeKey = false);

    /**
     * Get website
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return \Magento\Store\Api\Data\WebsiteInterface
     */
    public function getWebsite($websiteId = null);

    /**
     * Reinitialize store list
     *
     * @return void
     */
    public function reinitStores();

    /**
     * Get default store view
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getDefaultStoreView();

    /**
     * Check if system is run in the single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode();

    /**
     * Check if store has only one store view
     *
     * @return bool
     */
    public function hasSingleStore();

    /**
     * Set current store
     *
     * @param string|int|\Magento\Store\Model\Store $store
     * @return void
     */
    public function setCurrentStore($store);
}