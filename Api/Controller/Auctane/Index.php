<?php

namespace Auctane\Api\Controller\Auctane;

use Auctane\Api\Exception\AuthenticationFailedException;
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
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Index extends Action implements CsrfAwareActionInterface
{
    /** @var Authenticator */
    private $authenticator;
    /** @var Data */
    private $dataHelper;
    /** @var Export */
    private $export;
    /** @var ShipNotify */
    private $shipNotify;
    /** @var LoggerInterface */
    private $logger;


    /**
     * Index constructor.
     * @param Context $context
     * @param Data $dataHelper
     * @param Export $export
     * @param ShipNotify $shipNotify
     * @param Authenticator $authenticator
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Data $dataHelper,
        Export $export,
        ShipNotify $shipNotify,
        Authenticator $authenticator,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->dataHelper = $dataHelper;
        $this->export = $export;
        $this->shipNotify = $shipNotify;
        $this->authenticator = $authenticator;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
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
     * Execute action based on request and return result.
     */
    public function execute()
    {
        /** @var HttpRequest $request */
        $request = $this->getRequest();
        /** @var HttpResponse $response */
        $response = $this->getResponse();

        try {
            $storeIds = $this->authenticator->authenticate();

            switch ($request->getParam('action')) {
                case 'export':
                    $response->setHeader('Content-Type', 'text/xml');
                    $result = $this->export->process($request, $storeIds);
                    break;

                case 'shipnotify':
                    $result = $this->shipNotify->process();
                    break;

                default:
                    throw new LocalizedException(__('Invalid action.'));
            }
        } catch (AuthenticationFailedException $e) {
            $result = $this->dataHelper->fault(WebapiException::HTTP_UNAUTHORIZED, $e->getMessage());
            $response
                ->setHeader('WWW-Authenticate: ', 'Basic realm=ShipStation', true)
                ->setHeader('Content-Type', 'text/xml; charset=UTF-8', true);

            $this->logger->error($e->getMessage(), ['authentication']);
        } catch (InvalidXmlException $e) {
            $result = $this->dataHelper->fault(WebapiException::HTTP_BAD_REQUEST, $e->getMessage());
            foreach ($e->getErrors() as $error) {
                $this->logger->error($error->message, ['ship_notify', $error->line]);
            }
        } catch (LocalizedException $e) {
            $result = $this->dataHelper->fault(WebapiException::HTTP_BAD_REQUEST, $e->getMessage());
            $response->setStatusCode(WebapiException::HTTP_BAD_REQUEST);
            $this->logger->error($e->getMessage());
        } catch (Exception $e) {
            $result = $this->dataHelper->fault($e->getCode(), $e->getMessage());
            $this->logger->error($e->getMessage());
        }

        $response->setBody($result);
    }
}
