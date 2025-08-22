<?php

namespace Magento\Framework\App;

/**
 * Stub interface for Magento's CsrfAwareActionInterface
 */
interface CsrfAwareActionInterface
{
    public function createCsrfValidationException(RequestInterface $request);
    public function validateForCsrf(RequestInterface $request);
}