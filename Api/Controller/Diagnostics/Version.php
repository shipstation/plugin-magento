<?php
namespace Auctane\Api\Controller\Diagnostics;

use Auctane\Api\Controller\BaseController;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Module\ModuleListInterface;

class Version extends BaseController implements HttpGetActionInterface
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
        JsonFactory $jsonFactory,
        Http $request,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
    ) {
        parent::__construct($jsonFactory, $request);
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
    }

    public function executeAction(): array
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
