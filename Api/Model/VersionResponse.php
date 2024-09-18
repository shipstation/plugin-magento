<?php

namespace Auctane\Api\Model;

use Auctane\Api\Api\Data\VersionResponseInterface;

class VersionResponse implements VersionResponseInterface
{
    private $magentoVersion;
    private $pluginVersion;

    /**
     * {@inheritdoc}
     */
    public function getMagentoVersion()
    {
        return $this->magentoVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function setMagentoVersion($version)
    {
        $this->magentoVersion = $version;
    }

    /**
     * {@inheritdoc}
     */
    public function getPluginVersion()
    {
        return $this->pluginVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function setPluginVersion($version)
    {
        $this->pluginVersion = $version;
    }
}
