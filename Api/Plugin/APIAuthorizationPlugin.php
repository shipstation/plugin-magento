<?php
namespace Auctane\Api\Plugin;

use Auctane\Api\Exception\AuthenticationFailedException;
use Auctane\Api\Exception\AuthorizationException;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Oauth\TokenProviderInterface;

class APIAuthorizationPlugin
{
    /** @var TokenProviderInterface */
    private TokenProviderInterface $tokenProvider;
    /** @var Http */
    private Http $request;


    /**
     * Authenticator constructor.
     *
     * @param TokenProviderInterface $tokenProvider
     * @param Http $request
     */
    public function __construct(
        TokenProviderInterface $tokenProvider,
        Http $request
    ) {
        $this->tokenProvider = $tokenProvider;
        $this->request = $request;
    }

    /**
     * Validates that the user can make api calls
     *
     * @throws AuthenticationFailedException
     */
    public function aroundExecute($subject, callable $proceed)
    {
        $accessToken = $this->request->getHeader('Authorization');
        $parts = explode(' ', $accessToken);

        try {
            $this->tokenProvider->validateAccessToken($parts[1]);
        } catch (\Exception $e) {
            throw new AuthorizationException('Failed to authorize token ' . $parts[1] . ' message ' . $e->getMessage());
        }
        $proceed();
    }
}
