<?php

namespace Auctane\Api\Model;

use Magento\Framework\DataObject;


/**
 * Class Weight
 *
 * @package Auctane\Api\Model
 */
class Weight extends DataObject
{
    const VALUE_KEY = 'value';
    const UNIT_KEY = 'unit';


    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->_getData(self::VALUE_KEY);
    }

    /**
     * @return string
     */
    public function getUnit(): string
    {
        return $this->_getData(self::UNIT_KEY);
    }
}
