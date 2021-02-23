<?php
/**
 * Copyright © Novatize. All rights reserved.
 */

namespace Auctane\Api\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Config\Model\ResourceModel\Config\Data\Collection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ApiKey extends Field
{
    /**
     * @var CollectionFactory
     */
    private $configCollectionFactory;

    /**
     * ApiKey constructor.
     * @param Context $context
     * @param CollectionFactory $configCollectionFactory
     */
    public function __construct(
        Context $context,
        CollectionFactory $configCollectionFactory
    ) {
        $this->configCollectionFactory = $configCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setData('value', $this->getApiKeyValue());
        $element->setReadonly('readonly', 1);
        return $element->getElementHtml();
    }

    /**
     * @return string
     */
    protected function getApiKeyValue()
    {
        // we use the config collection to bypass the config cache
        /** @var Collection $collection */
        $collection = $this->configCollectionFactory->create();
        return $collection
            ->addFieldToFilter('path', 'shipstation_general/shipstation/ship_api_key')
            ->getFirstItem()
            ->getValue();
    }
}
