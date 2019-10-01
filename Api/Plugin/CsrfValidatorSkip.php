<?php

namespace Auctane\Api\Plugin;

use Closure;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;

/**
 * Class CsrfValidatorSkip
 * @package Auctane\Api\Plugin
 */
class CsrfValidatorSkip
{
    const CONTROLLER_MODULE = 'Auctane_Api';
    const CONTROLLER_NAME = 'auctane';

    /**
     * @param CsrfValidator $subject
     * @param Closure $proceed
     * @param RequestInterface $request
     * @param ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        Closure $proceed,
        $request,
        $action
    ) {
        // Skip CSRF check
        if ($request->getControllerModule() == self::CONTROLLER_MODULE
            && $request->getControllerName() == self::CONTROLLER_NAME) {
            return;
        }

        // Proceed Magento 2 core functionalities
        $proceed($request, $action);
    }
}
