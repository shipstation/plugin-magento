<?php

namespace Auctane\Api\Api;

interface VersionInterface
{
    /**
     * Get Magento and plugin versions
     *
     * @return \Auctane\Api\Api\Data\VersionResponseInterface
     */
    public function getVersion();
}
