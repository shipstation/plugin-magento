<?php
namespace Auctane\Api\Controller;

abstract class BaseAuthorizedController extends BaseController
{
    use BaseAuthorizedControllerTrait;

    /**
     * Initializes BaseAuthenticatedController.
     */
    public function __construct()
    {
        parent::__construct();
        $this->initAuthorization();
    }

    /**
     * Pulls auth information from the auth header
     *
     * @return bool
     */
    public function getIsAuthorized(): bool
    {
        $authorizationHeader = $this->request->getHeader('Authorization');
        // Token will be "Bearer token"
        $accessToken = explode(" ", $authorizationHeader)[1];
        return $this->authHandler->isAuthorized($accessToken);
    }
}
