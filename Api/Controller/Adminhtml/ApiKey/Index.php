<?php
/**
 * Copyright © Novatize. All rights reserved.
 */

namespace Auctane\Api\Controller\Adminhtml\ApiKey;

use Auctane\Api\Model\ApiKeyGenerator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class Index extends Action implements AuthorizationInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var ApiKeyGenerator
     */
    private $apiKeyGenerator;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        WriterInterface $configWriter,
        ApiKeyGenerator $apiKeyGenerator
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configWriter = $configWriter;
        $this->apiKeyGenerator = $apiKeyGenerator;
    }

    /**
     * Generates an api key and saves it.
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $key = $this->apiKeyGenerator->generate();
        $this->configWriter->save('shipstation_general/shipstation/ship_api_key', $key);

        $result = $this->resultJsonFactory->create();
        $result->setData([
            'key' => $key
        ]);

        return $result;
    }

    /**
     * Check current user permission on resource and privilege
     *
     * @param string $resource
     * @param string $privilege
     * @return  boolean
     */
    public function isAllowed($resource, $privilege = null)
    {
        return $this->_authorization->isAllowed('Auctane_Api::admin_config');
    }
}
