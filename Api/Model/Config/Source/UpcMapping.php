<?php

namespace Auctane\Api\Model\Config\Source;

class UpcMapping implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Attribute collection factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $_factory;

    /**
     * Collection factory
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $factory collection
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $factory
    ) {
        $this->_factory = $factory;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributesCollection = $this->_factory->create();
        $attributes = [['value' => '', 'label' => __('-- Please Select --')]];
        
        foreach ($attributesCollection as $attribute) {
            $code = $attribute->getAttributeCode();
            $label = $attribute->getFrontendLabel();
            if ($label) {
                $attributes[] = ['value' => $code, 'label' => $label];
            }
        }
        
        return $attributes;
    }
}