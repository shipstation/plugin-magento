<?php

namespace Magento\Framework\Exception;

/**
 * Mock implementation of Magento's LocalizedException for testing
 * This allows tests to run without requiring the full Magento framework
 */
class LocalizedException extends \Exception
{
    /**
     * Constructor
     *
     * @param \Magento\Framework\Phrase|string $phrase
     * @param \Exception|null $cause
     * @param int $code
     */
    public function __construct($phrase = null, \Exception $cause = null, $code = 0)
    {
        if ($phrase instanceof \Magento\Framework\Phrase) {
            $message = $phrase->render();
        } else {
            $message = (string) $phrase;
        }
        
        parent::__construct($message, $code, $cause);
    }
}