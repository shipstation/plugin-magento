<?php
namespace Auctane\Api\Controller\Diagnostics;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;

class Version implements HttpGetActionInterface
{
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    public function __construct(
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
    ) {
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
    }

    public function execute()
    {
        $moduleName = 'Auctane_Api';
        $module = $this->moduleList->getOne($moduleName);
        return [
            'magento' => [
                'version' => $this->productMetadata->getVersion(),
                'edition' => $this->productMetadata->getEdition(),
                'name' => $this->productMetadata->getName(),
            ],
            'module' => $module,
        ];
    }
}
