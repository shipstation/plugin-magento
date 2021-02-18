<?php

namespace Auctane\Api\Controller\Auctane;

use Auctane\Api\Exception\InvalidXmlException;
use Auctane\Api\Helper\Data;
use Auctane\Api\Model\Action\Export;
use Auctane\Api\Model\Action\ShipNotify;
use Auctane\Api\Request\Authenticator;
use Exception;
use Magento\Backend\Model\Auth\Credential\StorageInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zend\Http\Response;


/**
 * Class Index
 * @package Auctane\Api\Controller\Auctane
 */
class Index extends Action implements CsrfAwareActionInterface
{
    /**
     * @var Authenticator
     */
    private $authenticator;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var StorageInterface
     */
    private $storage;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Data
     */
    private $dataHelper;
    /**
     * @var Export
     */
    private $export;
    /**
     * @var ShipNotify
     */
    private $shipNotify;
    /**
     * @var RedirectFactory
     */
    private $redirectFactory;
    /** @var LoggerInterface */
    private $logger;


    /**
     * Index constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param StorageInterface $storage
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $dataHelper
     * @param Export $export
     * @param ShipNotify $shipNotify
     * @param RedirectFactory $redirectFactory
     * @param Authenticator $authenticator
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        StorageInterface $storage,
        ScopeConfigInterface $scopeConfig,
        Data $dataHelper,
        Export $export,
        ShipNotify $shipNotify,
        RedirectFactory $redirectFactory,
        Authenticator $authenticator,
        LoggerInterface $logger
    )
    {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->storage = $storage;
        $this->scopeConfig = $scopeConfig;
        $this->dataHelper = $dataHelper;
        $this->export = $export;
        $this->shipNotify = $shipNotify;
        $this->redirectFactory = $redirectFactory;
        $this->authenticator = $authenticator;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Default function
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->authenticator->authenticate()) {
            $this->getResponse()->setHeader('WWW-Authenticate: ', 'Basic realm=ShipStation', true);
            $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=UTF-8', true);
            $result = $this->dataHelper->fault(401, 'Authentication failed');
            $this->getResponse()->setBody($result);
            $this->logger->error("Authentication failed.", ['authentication']);

            return false;
        }

        /** @var $request \Magento\Framework\App\Request\Http */
        $request = $this->getRequest();
        //Get the requested action
        $action = $request->getParam('action');

        try {
            switch ($action) {
                case 'export':
                    $this->getResponse()->setHeader('Content-Type', 'text/xml');
                    $result = $this->export->process($request);
                    break;

                case 'shipnotify':
                    $result = $this->shipNotify->process();
                    // if there hasn't been an error then "200 OK" is given
                    break;
            }
        } catch (LocalizedException $e) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            $result = $this->dataHelper->fault(Response::STATUS_CODE_400, $e->getMessage());
            $this->logger->error($e->getMessage());
        } catch (InvalidXmlException $e) {
            foreach ($e->getErrors() as $error) {
                $this->logger->error($error->message, ['ship_notify', $error->line]);
            }

            $result = $this->dataHelper->fault(Response::STATUS_CODE_400, $e->getMessage());
        } catch (Exception $fault) {
            $result = $this->dataHelper->fault($fault->getCode(), $fault->getMessage());
            $this->logger->error($fault->getMessage());
        }

        $this->getResponse()->setBody($result);
    }
}
