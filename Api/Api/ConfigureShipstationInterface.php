<?php
namespace Auctane\Api\Api;
interface ConfigureShipstationInterface {

    /**
     * GET for Post api
     * @param string $option_key
     * @param string $marketplace_key
     * @param string $rates_url
     * @param string $verify_url
     * @return string
     */
    public function configureShipstation($option_key, $marketplace_key, $rates_url);
}