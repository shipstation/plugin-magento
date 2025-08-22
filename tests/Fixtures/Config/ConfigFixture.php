<?php

namespace Tests\Fixtures\Config;

/**
 * Fixture class for generating sample store configuration data for testing
 */
class ConfigFixture
{
    /**
     * Get default store configuration
     *
     * @return array
     */
    public static function getDefaultStoreConfig(): array
    {
        return [
            'auctane_api/general/enabled' => '1',
            'auctane_api/general/api_key' => 'test-api-key-12345',
            'auctane_api/general/debug_mode' => '0',
            'auctane_api/general/log_level' => 'info',
            'auctane_api/orders/export_status' => 'processing,complete',
            'auctane_api/orders/include_customer_notes' => '1',
            'auctane_api/orders/include_gift_messages' => '1',
            'auctane_api/inventory/sync_enabled' => '1',
            'auctane_api/inventory/sync_frequency' => '15',
            'auctane_api/shipping/create_shipments' => '1',
            'auctane_api/shipping/send_tracking_emails' => '1',
            'general/store_information/name' => 'Test Store',
            'general/store_information/phone' => '+1-555-123-4567',
            'general/store_information/merchant_country' => 'US',
            'shipping/origin/country_id' => 'US',
            'shipping/origin/region_id' => '12',
            'shipping/origin/postcode' => '90210',
            'shipping/origin/city' => 'Los Angeles',
            'shipping/origin/street_line1' => '456 Warehouse Ave',
            'currency/options/default' => 'USD',
            'currency/options/allow' => 'USD,EUR,GBP'
        ];
    }

    /**
     * Get multi-store configuration
     *
     * @return array
     */
    public static function getMultiStoreConfig(): array
    {
        return [
            'store1' => [
                'api_key' => 'us-store-api-key-12345',
                'enabled' => true,
                'order_statuses' => 'processing,complete',
                'store_name' => 'US Store',
                'country' => 'US',
                'currency' => 'USD'
            ],
            'store2' => [
                'api_key' => 'eu-store-api-key-12345',
                'enabled' => true,
                'order_statuses' => 'processing,complete,shipped',
                'store_name' => 'EU Store',
                'country' => 'DE',
                'currency' => 'EUR'
            ],
            'store3' => [
                'api_key' => 'disabled-store-api-key',
                'enabled' => false,
                'order_statuses' => 'processing',
                'store_name' => 'Disabled Store',
                'country' => 'US',
                'currency' => 'USD'
            ]
        ];
    }

    /**
     * Get API key configuration for specific store
     *
     * @param string $apiKey
     * @param int $storeId
     * @return array
     */
    public static function getApiKeyConfig(string $apiKey, int $storeId = 1): array
    {
        return [
            'auctane_api/general/enabled' => '1',
            'auctane_api/general/api_key' => $apiKey,
            'auctane_api/general/debug_mode' => '0',
            'general/store_information/name' => "Store {$storeId}",
            'store_id' => $storeId
        ];
    }

    /**
     * Get configuration with missing API key
     *
     * @return array
     */
    public static function getMissingApiKeyConfig(): array
    {
        $config = self::getDefaultStoreConfig();
        unset($config['auctane_api/general/api_key']);
        return $config;
    }

    /**
     * Get configuration with disabled module
     *
     * @return array
     */
    public static function getDisabledModuleConfig(): array
    {
        $config = self::getDefaultStoreConfig();
        $config['auctane_api/general/enabled'] = '0';
        return $config;
    }

    /**
     * Get debug mode configuration
     *
     * @return array
     */
    public static function getDebugModeConfig(): array
    {
        $config = self::getDefaultStoreConfig();
        $config['auctane_api/general/debug_mode'] = '1';
        $config['auctane_api/general/log_level'] = 'debug';
        return $config;
    }

    /**
     * Get configuration for testing different order statuses
     *
     * @param array $statuses
     * @return array
     */
    public static function getOrderStatusConfig(array $statuses = ['processing', 'complete']): array
    {
        $config = self::getDefaultStoreConfig();
        $config['auctane_api/orders/export_status'] = implode(',', $statuses);
        return $config;
    }

    /**
     * Get configuration for inventory sync testing
     *
     * @param bool $enabled
     * @param int $frequency
     * @return array
     */
    public static function getInventorySyncConfig(bool $enabled = true, int $frequency = 15): array
    {
        $config = self::getDefaultStoreConfig();
        $config['auctane_api/inventory/sync_enabled'] = $enabled ? '1' : '0';
        $config['auctane_api/inventory/sync_frequency'] = (string)$frequency;
        return $config;
    }

    /**
     * Get configuration for shipping settings
     *
     * @param bool $createShipments
     * @param bool $sendTrackingEmails
     * @return array
     */
    public static function getShippingConfig(bool $createShipments = true, bool $sendTrackingEmails = true): array
    {
        $config = self::getDefaultStoreConfig();
        $config['auctane_api/shipping/create_shipments'] = $createShipments ? '1' : '0';
        $config['auctane_api/shipping/send_tracking_emails'] = $sendTrackingEmails ? '1' : '0';
        return $config;
    }

    /**
     * Get store information configuration
     *
     * @param string $storeName
     * @param string $country
     * @param string $currency
     * @return array
     */
    public static function getStoreInfoConfig(
        string $storeName = 'Test Store',
        string $country = 'US',
        string $currency = 'USD'
    ): array {
        return [
            'general/store_information/name' => $storeName,
            'general/store_information/merchant_country' => $country,
            'currency/options/default' => $currency,
            'shipping/origin/country_id' => $country
        ];
    }

    /**
     * Get configuration with custom field mappings
     *
     * @return array
     */
    public static function getCustomFieldMappingsConfig(): array
    {
        $config = self::getDefaultStoreConfig();
        $config['auctane_api/field_mappings/order_number_field'] = 'increment_id';
        $config['auctane_api/field_mappings/customer_notes_field'] = 'customer_note';
        $config['auctane_api/field_mappings/gift_message_field'] = 'gift_message';
        $config['auctane_api/field_mappings/custom_attribute_1'] = 'custom_field_1';
        $config['auctane_api/field_mappings/custom_attribute_2'] = 'custom_field_2';
        return $config;
    }

    /**
     * Get configuration for different environments
     *
     * @param string $environment
     * @return array
     */
    public static function getEnvironmentConfig(string $environment = 'production'): array
    {
        $config = self::getDefaultStoreConfig();
        
        switch ($environment) {
            case 'development':
                $config['auctane_api/general/debug_mode'] = '1';
                $config['auctane_api/general/log_level'] = 'debug';
                $config['auctane_api/general/api_key'] = 'dev-api-key-12345';
                break;
            case 'staging':
                $config['auctane_api/general/debug_mode'] = '1';
                $config['auctane_api/general/log_level'] = 'info';
                $config['auctane_api/general/api_key'] = 'staging-api-key-12345';
                break;
            case 'production':
            default:
                $config['auctane_api/general/debug_mode'] = '0';
                $config['auctane_api/general/log_level'] = 'error';
                $config['auctane_api/general/api_key'] = 'prod-api-key-12345';
                break;
        }
        
        return $config;
    }

    /**
     * Get empty configuration for testing missing values
     *
     * @return array
     */
    public static function getEmptyConfig(): array
    {
        return [];
    }

    /**
     * Get configuration with invalid values for testing validation
     *
     * @return array
     */
    public static function getInvalidConfig(): array
    {
        return [
            'auctane_api/general/enabled' => 'invalid',
            'auctane_api/general/api_key' => '',
            'auctane_api/general/debug_mode' => 'not_boolean',
            'auctane_api/inventory/sync_frequency' => 'not_numeric',
            'currency/options/default' => 'INVALID_CURRENCY'
        ];
    }
}