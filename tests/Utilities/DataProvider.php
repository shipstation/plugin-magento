<?php

namespace Auctane\Api\Tests\Utilities;

use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrderStatus;
use Auctane\Api\Tests\Fixtures\Orders\OrderFixture;
use Auctane\Api\Tests\Fixtures\Requests\RequestFixture;
use Auctane\Api\Tests\Fixtures\Config\ConfigFixture;

/**
 * Data provider utility class for generating parameterized test data
 */
class DataProvider
{
    /**
     * Provide authentication test scenarios
     *
     * @return array
     */
    public static function authenticationScenarios(): array
    {
        return [
            'valid_api_key' => [
                'api_key' => 'valid-api-key-12345',
                'expected_result' => true,
                'description' => 'Valid API key should authenticate successfully'
            ],
            'invalid_api_key' => [
                'api_key' => 'invalid-api-key',
                'expected_result' => false,
                'description' => 'Invalid API key should fail authentication'
            ],
            'empty_api_key' => [
                'api_key' => '',
                'expected_result' => false,
                'description' => 'Empty API key should fail authentication'
            ],
            'null_api_key' => [
                'api_key' => null,
                'expected_result' => false,
                'description' => 'Null API key should fail authentication'
            ],
            'whitespace_api_key' => [
                'api_key' => '   ',
                'expected_result' => false,
                'description' => 'Whitespace-only API key should fail authentication'
            ],
            'special_chars_api_key' => [
                'api_key' => 'api-key-with-special-chars-!@#$%',
                'expected_result' => true,
                'description' => 'API key with special characters should work'
            ]
        ];
    }

    /**
     * Provide error scenarios for testing exception handling
     *
     * @return array
     */
    public static function errorScenarios(): array
    {
        return [
            'authentication_failed' => [
                'exception_class' => 'Auctane\Api\Exception\AuthenticationFailedException',
                'http_status' => 401,
                'message' => 'Authentication failed',
                'scenario' => 'Invalid API credentials provided'
            ],
            'authorization_failed' => [
                'exception_class' => 'Auctane\Api\Exception\AuthorizationException',
                'http_status' => 403,
                'message' => 'Access denied',
                'scenario' => 'Valid credentials but insufficient permissions'
            ],
            'bad_request' => [
                'exception_class' => 'Auctane\Api\Exception\BadRequestException',
                'http_status' => 400,
                'message' => 'Invalid request data',
                'scenario' => 'Malformed request parameters'
            ],
            'not_found' => [
                'exception_class' => 'Auctane\Api\Exception\NotFoundException',
                'http_status' => 404,
                'message' => 'Resource not found',
                'scenario' => 'Requested resource does not exist'
            ],
            'invalid_xml' => [
                'exception_class' => 'Auctane\Api\Exception\InvalidXmlException',
                'http_status' => 400,
                'message' => 'Invalid XML format',
                'scenario' => 'Malformed XML in request body'
            ]
        ];
    }

    /**
     * Provide different PHP version configurations for testing
     *
     * @return array
     */
    public static function phpVersionConfigurations(): array
    {
        return [
            'php_8_0' => [
                'version' => '8.0',
                'features' => ['union_types', 'named_arguments', 'attributes'],
                'extensions' => ['json', 'xml', 'curl', 'mbstring']
            ],
            'php_8_1' => [
                'version' => '8.1',
                'features' => ['enums', 'readonly_properties', 'fibers'],
                'extensions' => ['json', 'xml', 'curl', 'mbstring', 'sodium']
            ],
            'php_8_2' => [
                'version' => '8.2',
                'features' => ['readonly_classes', 'dnf_types'],
                'extensions' => ['json', 'xml', 'curl', 'mbstring', 'sodium', 'random']
            ],
            'php_8_3' => [
                'version' => '8.3',
                'features' => ['typed_class_constants', 'dynamic_class_constant_fetch'],
                'extensions' => ['json', 'xml', 'curl', 'mbstring', 'sodium', 'random']
            ],
            'php_8_4' => [
                'version' => '8.4',
                'features' => ['property_hooks', 'asymmetric_visibility'],
                'extensions' => ['json', 'xml', 'curl', 'mbstring', 'sodium', 'random']
            ]
        ];
    }

    /**
     * Provide different store configurations for multi-store testing
     *
     * @return array
     */
    public static function storeConfigurations(): array
    {
        return [
            'single_store_us' => [
                'store_id' => 1,
                'config' => ConfigFixture::getStoreInfoConfig('US Store', 'US', 'USD'),
                'expected_currency' => 'USD',
                'expected_country' => 'US'
            ],
            'single_store_eu' => [
                'store_id' => 2,
                'config' => ConfigFixture::getStoreInfoConfig('EU Store', 'DE', 'EUR'),
                'expected_currency' => 'EUR',
                'expected_country' => 'DE'
            ],
            'single_store_uk' => [
                'store_id' => 3,
                'config' => ConfigFixture::getStoreInfoConfig('UK Store', 'GB', 'GBP'),
                'expected_currency' => 'GBP',
                'expected_country' => 'GB'
            ],
            'multi_store' => [
                'store_id' => [1, 2, 3],
                'config' => ConfigFixture::getMultiStoreConfig(),
                'expected_stores' => 3,
                'expected_currencies' => ['USD', 'EUR']
            ]
        ];
    }

    /**
     * Provide different order status scenarios
     *
     * @return array
     */
    public static function orderStatusScenarios(): array
    {
        return [
            'awaiting_payment' => [
                'status' => SalesOrderStatus::AWAITING_PAYMENT,
                'should_export' => false,
                'description' => 'Orders awaiting payment should not be exported'
            ],
            'awaiting_shipment' => [
                'status' => SalesOrderStatus::AWAITING_SHIPMENT,
                'should_export' => true,
                'description' => 'Orders awaiting shipment should be exported'
            ],
            'shipped' => [
                'status' => SalesOrderStatus::SHIPPED,
                'should_export' => true,
                'description' => 'Shipped orders should be exported'
            ],
            'cancelled' => [
                'status' => SalesOrderStatus::CANCELLED,
                'should_export' => false,
                'description' => 'Cancelled orders should not be exported'
            ],
            'on_hold' => [
                'status' => SalesOrderStatus::ON_HOLD,
                'should_export' => false,
                'description' => 'Orders on hold should not be exported'
            ]
        ];
    }

    /**
     * Provide different request validation scenarios
     *
     * @return array
     */
    public static function requestValidationScenarios(): array
    {
        return [
            'valid_export_request' => [
                'request_data' => RequestFixture::createSalesOrdersExportRequest(),
                'is_valid' => true,
                'expected_errors' => []
            ],
            'missing_criteria' => [
                'request_data' => ['cursor' => null],
                'is_valid' => false,
                'expected_errors' => ['criteria is required']
            ],
            'invalid_date_format' => [
                'request_data' => [
                    'criteria' => [
                        'start_date' => 'invalid-date',
                        'end_date' => '2024-01-31T23:59:59.999Z'
                    ]
                ],
                'is_valid' => false,
                'expected_errors' => ['start_date must be valid ISO 8601 format']
            ],
            'end_date_before_start_date' => [
                'request_data' => [
                    'criteria' => [
                        'start_date' => '2024-01-31T00:00:00.000Z',
                        'end_date' => '2024-01-01T23:59:59.999Z'
                    ]
                ],
                'is_valid' => false,
                'expected_errors' => ['end_date must be after start_date']
            ]
        ];
    }

    /**
     * Provide different inventory scenarios
     *
     * @return array
     */
    public static function inventoryScenarios(): array
    {
        return [
            'in_stock_item' => [
                'sku' => 'TEST-SKU-001',
                'quantity' => 100,
                'is_in_stock' => true,
                'manage_stock' => true
            ],
            'out_of_stock_item' => [
                'sku' => 'TEST-SKU-002',
                'quantity' => 0,
                'is_in_stock' => false,
                'manage_stock' => true
            ],
            'backorder_item' => [
                'sku' => 'TEST-SKU-003',
                'quantity' => -5,
                'is_in_stock' => true,
                'manage_stock' => true,
                'backorders' => true
            ],
            'unmanaged_stock_item' => [
                'sku' => 'TEST-SKU-004',
                'quantity' => null,
                'is_in_stock' => true,
                'manage_stock' => false
            ]
        ];
    }

    /**
     * Provide different shipping scenarios
     *
     * @return array
     */
    public static function shippingScenarios(): array
    {
        return [
            'domestic_standard' => [
                'carrier' => 'ups',
                'service' => 'ups_ground',
                'origin_country' => 'US',
                'destination_country' => 'US',
                'is_international' => false
            ],
            'domestic_express' => [
                'carrier' => 'fedex',
                'service' => 'fedex_overnight',
                'origin_country' => 'US',
                'destination_country' => 'US',
                'is_international' => false
            ],
            'international_standard' => [
                'carrier' => 'usps',
                'service' => 'usps_international',
                'origin_country' => 'US',
                'destination_country' => 'CA',
                'is_international' => true
            ],
            'international_express' => [
                'carrier' => 'dhl',
                'service' => 'dhl_express',
                'origin_country' => 'US',
                'destination_country' => 'DE',
                'is_international' => true
            ]
        ];
    }

    /**
     * Provide different order complexity scenarios
     *
     * @return array
     */
    public static function orderComplexityScenarios(): array
    {
        return [
            'simple_order' => [
                'order_data' => OrderFixture::createSampleOrder(),
                'item_count' => 1,
                'has_custom_fields' => false,
                'is_international' => false
            ],
            'multi_item_order' => [
                'order_data' => OrderFixture::createOrderWithItems(5),
                'item_count' => 5,
                'has_custom_fields' => false,
                'is_international' => false
            ],
            'complex_order' => [
                'order_data' => OrderFixture::createOrderWithCustomFields(),
                'item_count' => 1,
                'has_custom_fields' => true,
                'is_international' => false
            ],
            'international_order' => [
                'order_data' => OrderFixture::createInternationalOrder(),
                'item_count' => 1,
                'has_custom_fields' => false,
                'is_international' => true
            ],
            'minimal_order' => [
                'order_data' => OrderFixture::createMinimalOrder(),
                'item_count' => 1,
                'has_custom_fields' => false,
                'is_international' => false
            ]
        ];
    }

    /**
     * Provide HTTP method and endpoint combinations
     *
     * @return array
     */
    public static function httpEndpointScenarios(): array
    {
        return [
            'get_diagnostics_live' => [
                'method' => 'GET',
                'endpoint' => '/auctane_api/diagnostics/live',
                'requires_auth' => false,
                'expected_status' => 200
            ],
            'get_diagnostics_version' => [
                'method' => 'GET',
                'endpoint' => '/auctane_api/diagnostics/version',
                'requires_auth' => false,
                'expected_status' => 200
            ],
            'post_sales_orders_export' => [
                'method' => 'POST',
                'endpoint' => '/auctane_api/sales_orders/export',
                'requires_auth' => true,
                'expected_status' => 200
            ],
            'post_inventory_fetch' => [
                'method' => 'POST',
                'endpoint' => '/auctane_api/inventory/fetch',
                'requires_auth' => true,
                'expected_status' => 200
            ],
            'post_inventory_push' => [
                'method' => 'POST',
                'endpoint' => '/auctane_api/inventory/push',
                'requires_auth' => true,
                'expected_status' => 200
            ],
            'post_shipment_notification' => [
                'method' => 'POST',
                'endpoint' => '/auctane_api/shipment/notification',
                'requires_auth' => true,
                'expected_status' => 200
            ]
        ];
    }

    /**
     * Provide performance test scenarios
     *
     * @return array
     */
    public static function performanceScenarios(): array
    {
        return [
            'small_dataset' => [
                'order_count' => 10,
                'items_per_order' => 1,
                'expected_max_time' => 1.0, // seconds
                'expected_max_memory' => 10 * 1024 * 1024 // 10MB
            ],
            'medium_dataset' => [
                'order_count' => 100,
                'items_per_order' => 3,
                'expected_max_time' => 5.0,
                'expected_max_memory' => 50 * 1024 * 1024 // 50MB
            ],
            'large_dataset' => [
                'order_count' => 1000,
                'items_per_order' => 5,
                'expected_max_time' => 30.0,
                'expected_max_memory' => 100 * 1024 * 1024 // 100MB
            ]
        ];
    }

    /**
     * Generate test data for specific test method
     *
     * @param string $testType
     * @param array $parameters
     * @return array
     */
    public static function generateTestData(string $testType, array $parameters = []): array
    {
        switch ($testType) {
            case 'authentication':
                return self::authenticationScenarios();
            case 'errors':
                return self::errorScenarios();
            case 'php_versions':
                return self::phpVersionConfigurations();
            case 'stores':
                return self::storeConfigurations();
            case 'order_status':
                return self::orderStatusScenarios();
            case 'request_validation':
                return self::requestValidationScenarios();
            case 'inventory':
                return self::inventoryScenarios();
            case 'shipping':
                return self::shippingScenarios();
            case 'order_complexity':
                return self::orderComplexityScenarios();
            case 'http_endpoints':
                return self::httpEndpointScenarios();
            case 'performance':
                return self::performanceScenarios();
            default:
                return [];
        }
    }

    /**
     * Create parameterized test data with custom parameters
     *
     * @param array $baseData
     * @param array $variations
     * @return array
     */
    public static function createParameterizedData(array $baseData, array $variations): array
    {
        $testData = [];
        
        foreach ($variations as $variationName => $variationData) {
            $testData[$variationName] = array_merge($baseData, $variationData);
        }
        
        return $testData;
    }

    /**
     * Generate random test data for stress testing
     *
     * @param int $count
     * @param string $type
     * @return array
     */
    public static function generateRandomTestData(int $count, string $type = 'orders'): array
    {
        $data = [];
        
        for ($i = 0; $i < $count; $i++) {
            switch ($type) {
                case 'orders':
                    $data[] = OrderFixture::createOrderWithItems(rand(1, 10));
                    break;
                case 'api_keys':
                    $data[] = 'test-api-key-' . uniqid();
                    break;
                case 'skus':
                    $data[] = 'TEST-SKU-' . str_pad($i, 6, '0', STR_PAD_LEFT);
                    break;
                default:
                    $data[] = ['id' => $i, 'data' => 'test-data-' . $i];
                    break;
            }
        }
        
        return $data;
    }
}