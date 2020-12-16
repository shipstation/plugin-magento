<?php

namespace Auctane\Api\Controller\Auctane;

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
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Zend\Http\Response;
use Zend\Json\Server\Response\Http;
use Magento\Store\Model\Store;


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

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        StorageInterface $storage,
        ScopeConfigInterface $scopeConfig,
        Data $dataHelper,
        Export $export,
        ShipNotify $shipNotify,
        RedirectFactory $redirectFactory,
        Authenticator $authenticator
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
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
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
            return false;
        }

        /** @var $request \Magento\Framework\App\Request\Http */
        $request = $this->getRequest();
        //Get the requested action
        $action = $request->getParam('action');

        try {
            switch ($action) {
                case 'export':
                    $result = $this->export->process($request, $this->getResponse(), Store::DEFAULT_STORE_ID);
                    break;

                case 'shipnotify':
                    $result = $this->shipNotify->process();
                    // if there hasn't been an error then "200 OK" is given
                    break;
            }
        } catch (LocalizedException $e) {
            $this->_response->setStatusCode(Response::STATUS_CODE_400);
            $result = $this->dataHelper->fault(Response::STATUS_CODE_400, $e->getMessage());
        } catch (Exception $fault) {
            $result = $this->dataHelper->fault($fault->getCode(), $fault->getMessage());
        }

        $this->getResponse()->setBody($result);
    }
}
