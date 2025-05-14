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
        $pleaseSelectOption = [['value' => '', 'label' => __('-- Please Select --')]];
        $attributeOptions = [];
        
        foreach ($attributesCollection as $attribute) {
            $code = $attribute->getAttributeCode();
            $label = $attribute->getFrontendLabel();
            if ($label) {
                $attributeOptions[] = ['value' => $code, 'label' => $label];
            }
        }
        
        // Sort attributes alphabetically by label
        usort($attributeOptions, function ($a, $b) {
            return strcmp($a['label'], $b['label']);
        });
        
        // Merge the "Please Select" option with the sorted attributes
        return array_merge($pleaseSelectOption, $attributeOptions);
    }
}