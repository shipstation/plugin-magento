<?php

namespace Auctane\Api\Api;

/**
 * Interface ConfigureShipstationInterface
 */
interface ConfigureShipstationInterface
{
    /**
     * GET for Post api.
     * @param string $option_key
     * @param string $marketplace_key
     * @param string $rates_url
     * @return string
     */
    public function configureShipstation(string $option_key, string $marketplace_key, string $rates_url): string;
}
