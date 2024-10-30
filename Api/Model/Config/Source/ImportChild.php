<?php

namespace Auctane\Api\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ImportChild implements OptionSourceInterface
{
    const CHILD_ONLY_VALUE = 2;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 1, 'label' => __('Yes')],
            ['value' => 0, 'label' => __('No')],
            ['value' => self::CHILD_ONLY_VALUE, 'label' => __('Child Only')]
        ];
    }
}
