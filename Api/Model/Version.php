<?php

namespace Auctane\Api\Model;

use Auctane\Api\Api\VersionInterface;
use Auctane\Api\Api\Data\VersionResponseInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;

class Version implements VersionInterface
{
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var VersionResponseInterface
     */
    private $versionResponse;

    public function __construct(
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
        VersionResponseInterface $versionResponse
    ) {
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->versionResponse = $versionResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        // Get Magento version
        $magentoVersion = $this->productMetadata->getVersion();

        // Get plugin version
        $moduleName = 'Auctane_Api';
        $pluginVersion = $this->moduleList->getOne($moduleName)['setup_version'];

        // Set response data
        $this->versionResponse->setMagentoVersion($magentoVersion);
        $this->versionResponse->setPluginVersion($pluginVersion);

        return $this->versionResponse;
    }
}
