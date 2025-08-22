<?php

/**
 * Mock global functions for Magento compatibility in tests
 */

if (!function_exists('__')) {


    /**
     * Mock implementation of Magento's translation function
     *
     * @param string $text
     * @param mixed ...$arguments
     * @return \Magento\Framework\Phrase
     */
    function __(string $text, ...$arguments): \Magento\Framework\Phrase
    {
        return new \Magento\Framework\Phrase($text, $arguments);
    }


}
