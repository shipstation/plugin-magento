# Design Document

## Overview

This design implements a comprehensive standalone unit testing framework for the Auctane_Api Magento 2 module. The solution uses PHPUnit as the testing framework with custom mock implementations for Magento dependencies, allowing tests to run without a full Magento installation. The testing framework will be integrated with GitHub Actions for continuous integration and include code quality tools for maintaining high standards.

## Architecture

### Testing Framework Structure

```
tests/
├── Unit/                           # Unit tests mirror the Api/ structure
│   ├── Controller/                 # Controller tests with mocked dependencies
│   ├── Model/                      # Business logic and data model tests
│   ├── Exception/                  # Exception handling tests
│   └── Helper/                     # Utility class tests
├── Mock/                           # Mock implementations for Magento dependencies
│   ├── Framework/                  # Core Magento framework mocks
│   ├── Store/                      # Store management mocks
│   └── Config/                     # Configuration system mocks
├── Fixtures/                       # Test data and sample payloads
│   ├── Requests/                   # Sample API request data
│   ├── Responses/                  # Expected API response data
│   └── Orders/                     # Sample order data
├── Utilities/                      # Test helper classes and factories
│   ├── TestCase.php               # Base test case with common setup
│   ├── MockFactory.php            # Factory for creating mock objects
│   └── DataProvider.php           # Data providers for parameterized tests
└── bootstrap.php                   # Test environment initialization
```

### Mock Architecture

The mock system will provide lightweight implementations of Magento dependencies:

- **Framework Mocks**: HTTP request/response, JSON factory, object manager
- **Store Mocks**: Store manager, scope configuration, store entities
- **Database Mocks**: Repository interfaces, collection objects
- **Authentication Mocks**: Authorization services, API key validation

## Components and Interfaces

### Base Test Infrastructure

#### TestCase Base Class
```php
abstract class TestCase extends PHPUnit\Framework\TestCase
{
    protected MockFactory $mockFactory;
    protected DataProvider $dataProvider;
    
    protected function setUp(): void
    {
        $this->mockFactory = new MockFactory();
        $this->dataProvider = new DataProvider();
    }
    
    protected function createMockRequest(array $data = []): Http
    protected function createMockResponse(): Json
    protected function createMockScopeConfig(array $config = []): ScopeConfigInterface
}
```

#### MockFactory
Centralized factory for creating consistent mock objects across all tests:
```php
class MockFactory
{
    public function createScopeConfigMock(array $values = []): ScopeConfigInterface
    public function createStoreManagerMock(array $stores = []): StoreManagerInterface
    public function createJsonFactoryMock(): JsonFactory
    public function createHttpRequestMock(array $params = []): Http
}
```

### Controller Testing Strategy

#### Base Controller Tests
- Test the execute() method error handling and response formatting
- Verify CSRF validation bypass functionality
- Test authorization flow integration
- Mock all Magento dependencies through dependency injection

#### Specific Controller Tests
- **Authorization Tests**: Mock different API key scenarios
- **Request Handling**: Test parameter parsing and validation
- **Response Formatting**: Verify JSON response structure
- **Error Scenarios**: Test exception handling and HTTP status codes

### Model Testing Strategy

#### Business Logic Tests
- **Authorization Model**: Test API key validation against multiple stores
- **Action Models**: Test export and ship notification logic
- **Weight Adapter**: Test unit conversion and validation
- **API Models**: Test request/response serialization

#### Data Model Tests
- **OrderSourceAPI Models**: Test all data transfer objects
- **Validation Logic**: Test input validation and sanitization
- **Serialization**: Test JSON encoding/decoding

### Exception Testing Strategy

#### Exception Hierarchy Tests
- Verify all custom exceptions extend ApiException
- Test HTTP status code mapping
- Validate error message formatting
- Test exception inheritance chain

## Data Models

### Test Data Structure

#### Fixture Data Models
```php
class OrderFixture
{
    public static function createSampleOrder(): array
    public static function createOrderWithItems(int $itemCount): array
    public static function createOrderWithCustomFields(): array
}

class RequestFixture
{
    public static function createInventoryFetchRequest(): array
    public static function createShipmentNotificationRequest(): array
    public static function createSalesOrderExportRequest(): array
}
```

#### Mock Configuration Data
```php
class ConfigFixture
{
    public static function getDefaultStoreConfig(): array
    public static function getMultiStoreConfig(): array
    public static function getApiKeyConfig(string $apiKey): array
}
```

## Error Handling

### Test Error Scenarios

#### Exception Testing
- Test all custom exception types with appropriate HTTP status codes
- Verify exception message formatting and localization
- Test exception chaining and nested error handling

#### Mock Failure Scenarios
- Simulate network failures for external API calls
- Test database connection failures
- Simulate invalid configuration scenarios

#### Assertion Strategies
- Use PHPUnit's exception testing methods
- Implement custom assertions for API response validation
- Create helper methods for common error scenario testing

## Testing Strategy

### Unit Test Categories

#### 1. Controller Tests
- **Authorization Controllers**: Test API key validation and multi-store support
- **Diagnostic Controllers**: Test health check and version endpoints
- **API Controllers**: Test inventory, orders, and shipment endpoints
- **Error Handling**: Test exception propagation and response formatting

#### 2. Model Tests
- **Business Logic**: Test core functionality without external dependencies
- **Data Validation**: Test input sanitization and validation rules
- **API Integration**: Test request/response handling with mocked HTTP clients
- **Configuration**: Test settings retrieval and store-specific configurations

#### 3. Exception Tests
- **Custom Exceptions**: Test all ApiException subclasses
- **HTTP Status Mapping**: Verify correct status codes for different error types
- **Error Messages**: Test message formatting and internationalization

#### 4. Integration Tests
- **End-to-End Workflows**: Test complete API request/response cycles
- **Multi-Store Scenarios**: Test functionality across different store configurations
- **Authentication Flows**: Test various authentication scenarios

### Code Coverage Strategy

#### Coverage Targets
- **Models**: 90% line coverage minimum
- **Controllers**: 85% line coverage minimum
- **Exceptions**: 100% line coverage (simple classes)
- **Overall**: 80% project coverage minimum

#### Coverage Exclusions
- Registration files and autoloader setup
- Magento framework integration code that cannot be unit tested
- Simple getter/setter methods without business logic

### Test Execution Strategy

#### Local Development
- Fast execution (under 2 minutes for full suite)
- Parallel test execution where possible
- Clear output with progress indicators
- Integration with IDE test runners

#### CI/CD Integration
- Matrix testing across PHP versions (8.0, 8.1, 8.2, 8.3, 8.4)
- Parallel job execution for different test categories
- Artifact collection for coverage reports
- Integration with PR status checks

## Implementation Approach

### Phase 1: Foundation
1. Set up PHPUnit configuration and directory structure
2. Create base TestCase class and MockFactory
3. Implement core Magento framework mocks
4. Create sample test fixtures and data providers

### Phase 2: Core Testing
1. Implement controller tests with full mock coverage
2. Create comprehensive model tests
3. Add exception testing suite
4. Implement code coverage reporting

### Phase 3: Quality Integration
1. Add PHPStan static analysis configuration
2. Integrate PHP CodeSniffer for coding standards
3. Set up GitHub Actions workflow
4. Configure multi-PHP version testing matrix

### Phase 4: Advanced Features
1. Add performance benchmarking for critical paths
2. Implement mutation testing for test quality validation
3. Create custom assertions for API-specific testing
4. Add integration test scenarios for complex workflows