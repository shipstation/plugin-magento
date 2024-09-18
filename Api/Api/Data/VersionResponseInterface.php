<?php

namespace Auctane\Api\Api\Data;

interface VersionResponseInterface
{
    /**
     * Get Magento version
     *
     * @return string
     */
    public function getMagentoVersion();

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion();

    /**
     * Set Magento version
     *
     * @param string $version
     * @return void
     */
    public function setMagentoVersion($version);

    /**
     * Set plugin version
     *
     * @param string $version
     * @return void
     */
    public function setPluginVersion($version);
}
