<?php

namespace Auctane\Api\Test\Utilities;

/**
 * Data provider utility class for generating test data and scenarios
 * Used to create consistent test data across different test classes
 */
class DataProvider
{
    /**
     * Get test data for a specific scenario
     *
     * @param string $scenario Test scenario name
     * @param array $overrides Data overrides
     * @return array
     */
    public function getTestData(string $scenario, array $overrides = []): array
    {
        $data = $this->getScenarioData($scenario);
        return array_merge($data, $overrides);
    }

    /**
     * Get authentication test scenarios
     *
     * @return array
     */
    public function getAuthenticationScenarios(): array
    {
        return [
            'valid_api_key' => [
                'api_key' => 'valid-api-key-123',
                'expected_result' => true
            ],
            'invalid_api_key' => [
                'api_key' => 'invalid-key',
                'expected_result' => false
            ],
            'empty_api_key' => [
                'api_key' => '',
                'expected_result' => false
            ],
            'null_api_key' => [
                'api_key' => null,
                'expected_result' => false
            ]
        ];
    }

    /**
     * Get error scenario test data
     *
     * @return array
     */
    public function getErrorScenarios(): array
    {
        return [
            'authentication_failed' => [
                'exception_class' => 'Auctane\Api\Exception\AuthenticationFailedException',
                'message' => 'Authentication failed',
                'http_code' => 401
            ],
            'authorization_failed' => [
                'exception_class' => 'Auctane\Api\Exception\AuthorizationException',
                'message' => 'Authorization failed',
                'http_code' => 403
            ],
            'bad_request' => [
                'exception_class' => 'Auctane\Api\Exception\BadRequestException',
                'message' => 'Bad request',
                'http_code' => 400
            ],
            'not_found' => [
                'exception_class' => 'Auctane\Api\Exception\NotFoundException',
                'message' => 'Resource not found',
                'http_code' => 404
            ],
            'invalid_xml' => [
                'exception_class' => 'Auctane\Api\Exception\InvalidXmlException',
                'message' => 'Invalid XML format',
                'http_code' => 400
            ]
        ];
    }

    /**
     * Get PHP version test matrix
     *
     * @return array
     */
    public function getPhpVersionMatrix(): array
    {
        return [
            'php_8_0' => ['version' => '8.0'],
            'php_8_1' => ['version' => '8.1'],
            'php_8_2' => ['version' => '8.2'],
            'php_8_3' => ['version' => '8.3'],
            'php_8_4' => ['version' => '8.4']
        ];
    }

    /**
     * Get store configuration scenarios
     *
     * @return array
     */
    public function getStoreConfigurationScenarios(): array
    {
        return [
            'single_store' => [
                'stores' => [
                    'default' => [
                        'id' => 1,
                        'code' => 'default',
                        'name' => 'Default Store View',
                        'website_id' => 1
                    ]
                ]
            ],
            'multi_store' => [
                'stores' => [
                    'default' => [
                        'id' => 1,
                        'code' => 'default',
                        'name' => 'Default Store View',
                        'website_id' => 1
                    ],
                    'store_2' => [
                        'id' => 2,
                        'code' => 'store_2',
                        'name' => 'Second Store View',
                        'website_id' => 2
                    ]
                ]
            ]
        ];
    }

    /**
     * Get scenario-specific data
     *
     * @param string $scenario
     * @return array
     */
    private function getScenarioData(string $scenario): array
    {
        $scenarios = [
            'default_config' => [
                'auctane_api/general/enabled' => '1',
                'auctane_api/general/api_key' => 'test-api-key',
                'auctane_api/general/debug' => '0'
            ],
            'disabled_config' => [
                'auctane_api/general/enabled' => '0',
                'auctane_api/general/api_key' => '',
                'auctane_api/general/debug' => '0'
            ],
            'debug_config' => [
                'auctane_api/general/enabled' => '1',
                'auctane_api/general/api_key' => 'debug-api-key',
                'auctane_api/general/debug' => '1'
            ],
            'sample_order' => [
                'entity_id' => 1,
                'increment_id' => '000000001',
                'status' => 'processing',
                'state' => 'processing',
                'customer_email' => 'customer@example.com',
                'customer_firstname' => 'John',
                'customer_lastname' => 'Doe',
                'grand_total' => 100.00,
                'subtotal' => 85.00,
                'tax_amount' => 8.50,
                'shipping_amount' => 6.50
            ],
            'sample_request_params' => [
                'action' => 'export',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'store_id' => 1
            ],
            'sample_api_response' => [
                'success' => true,
                'message' => 'Operation completed successfully',
                'data' => []
            ]
        ];

        return $scenarios[$scenario] ?? [];
    }
}