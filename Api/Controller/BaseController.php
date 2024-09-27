<?php
namespace Auctane\Api\Controller;

use Auctane\Api\Api\HttpActionInterface;
use Auctane\Api\Exception\ApiException;
use Auctane\Api\Exception\AuthorizationException;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

abstract class BaseController implements HttpActionInterface, CsrfAwareActionInterface
{

    /** @var JsonFactory */
    protected JsonFactory $jsonFactory;
    /** @var Http  */
    protected Http $request;

    /**
     * The base controller.
     *
     * @param JsonFactory $jsonFactory
     * @param Http $request
     */
    public function __construct(
        JsonFactory $jsonFactory,
        Http $request
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
    }

    /**
     * This method wraps the implementation with error handling and authorization.
     *
     * @return Json
     */
    public function execute(): Json
    {
        try {
            if (!$this->callIsAuthorized()) {
                throw new AuthorizationException();
            }
            $response = $this->executeAction();
            return $this->jsonFactory->create()->setData($response);
        } catch (ApiException $apiException) {
            return $this->jsonFactory->create()->setHttpResponseCode($apiException->getHttpStatusCode())->setData([
                'status' => 'failure',
                'message' => $apiException->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonFactory->create()->setHttpResponseCode(500)->setData([
                'status' => 'failure',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * This method will be implemented by the controller and return the array payload.
     *
     * @return array
     * @throws ApiException
     */
    abstract protected function executeAction(): array;

    /**
     * This method determines whether a caller is authorized to make this call.
     *
     * @returns bool
     */
    protected function callIsAuthorized():bool
    {
        return true;
    }

    /**
     * This method is returning null because we will not be throwing a cross site request forgery error.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * This method disables cross site request forgery validation so external servers can call these api endpoints.
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
