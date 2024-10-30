<?php

namespace Auctane\Api\Controller\Adminhtml\ApiKey;

use Auctane\Api\Model\ApiKeyGenerator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
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
     * @var ApiKeyGenerator
     */
    private $apiKeyGenerator;


    /**
     * Index constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ApiKeyGenerator $apiKeyGenerator
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ApiKeyGenerator $apiKeyGenerator
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiKeyGenerator = $apiKeyGenerator;
    }

    /**
     * Generates an api key and saves it.
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        return $this->resultJsonFactory->create()
            ->setData(['key' => $this->apiKeyGenerator->generate()]);
    }

    /**
     * Check current user permission on resource and privilege
     *
     * @param string $resource
     * @param string|null $privilege
     * @return  boolean
     * @noinspection PhpMissingParamTypeInspection
     */
    public function isAllowed($resource, $privilege = null): bool
    {
        return $this->_authorization->isAllowed('Auctane_Api::admin_config');
    }
}
