<?php

namespace Auctane\Api\Model\Config\Source;

class Price implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Store Price')],
            ['value' => 1, 'label' => __('Base Price')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [0 => __('Store Price'), 1 => __('Base Price')];
    }
}
