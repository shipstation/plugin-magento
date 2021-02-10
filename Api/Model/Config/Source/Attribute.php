<?php

namespace Auctane\Api\Model\Config\Source;

class Attribute implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Attribute collection factory
     *
     * @var \ResourceModel\Product\Attribute\CollectionFactory
     */
    private $_factory;

    /**
     * Collection factory
     *
     * @param \Attribute\CollectionFactory $factory collection
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
        $attributes = [];
        foreach ($attributesCollection as $attribute) {
            if ($attribute->getIsUserDefined()) {
                $code = $attribute->getAttributeCode();
                $label = $attribute->getFrontendLabel();
                $attributes[] = ['value' => $code, 'label' => $label];
            }
        }
        return $attributes;
    }
}
