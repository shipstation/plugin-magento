<?php

namespace Auctane\Api\Model;

use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;

class WeightAdapter
{
    const LOCAL_UNIT_KGS = 'kgs';
    const LOCAL_UNIT_LBS = 'lbs';

    const FOREIGN_UNIT_POUNDS = 'Pounds';
    const FOREIGN_UNIT_OUNCES = 'Ounces';
    const FOREIGN_UNIT_GRAMS = 'Grams';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var WeightFactory */
    private $weightFactory;


    /**
     * WeightAdapter constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param WeightFactory $weightFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WeightFactory $weightFactory
    ) {
        $this->scopeConfig = $scopeConfig;

        $this->weightFactory = $weightFactory;
    }

    /**
     * @param float|null $localWeight
     * @return Weight
     */
    public function toForeignWeight(?float $localWeight): Weight
    {
        $localWeightUnit = $this->scopeConfig->getValue(Data::XML_PATH_WEIGHT_UNIT);

        $foreignWeightValue = $localWeight ?: 0;

        if ($localWeightUnit == self::LOCAL_UNIT_KGS) {
            $foreignWeightValue = $localWeight * 1000;
        }

        return $this->weightFactory->create(['data' => [
            Weight::UNIT_KEY => $this->getForeignWeightUnit($localWeightUnit),
            Weight::VALUE_KEY => $foreignWeightValue
        ]]);
    }

    /**
     * @param string $localWeightUnit
     * @return string
     */
    private function getForeignWeightUnit(string $localWeightUnit): string
    {
        /**
         * @see \Magento\Directory\Model\Config\Source\WeightUnit
         */
        switch ($localWeightUnit) {
            case self::LOCAL_UNIT_KGS:
                $foreignWeightUnit = self::FOREIGN_UNIT_GRAMS;
                break;

            case self::LOCAL_UNIT_LBS:
            default:
                $foreignWeightUnit = self::FOREIGN_UNIT_POUNDS;
        }

        return $foreignWeightUnit;
    }
}
