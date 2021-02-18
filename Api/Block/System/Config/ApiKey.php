<?php
/**
 * Copyright © Novatize. All rights reserved.
 */

namespace Auctane\Api\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;


/**
 * Class ApiKey
 * @package Auctane\Api\Block\System\Config
 */
class ApiKey extends Field
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $element
            ->setReadonly(true)
            ->getElementHtml();
    }
}
