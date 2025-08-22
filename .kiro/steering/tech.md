# Technology Stack

## Platform & Framework
- **Magento 2** - E-commerce platform
- **PHP** - Primary programming language
- **Composer** - Dependency management

## Module Information
- **Module Name**: Auctane_Api
- **Version**: 2.5.7
- **Type**: magento2-module
- **Namespace**: Auctane\Api

## Key Technologies
- **PSR-4 Autoloading** - Modern PHP class loading
- **Dependency Injection** - Magento 2's DI container
- **XML Configuration** - Module configuration files
- **JSON APIs** - RESTful web services
- **Monolog** - Logging framework

## Common Commands

### Installation & Setup
```bash
# From Magento root directory
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### Development & Debugging
```bash
# Run the debug management command
php bin/magento auctane:debug:management

# Check module status
php bin/magento module:status Auctane_Api

# Enable/disable module
php bin/magento module:enable Auctane_Api
php bin/magento module:disable Auctane_Api
```

### Logs
- Module logs are written to: `var/log/auctane_api.log`
- Use Magento's logging system for debugging

## Development Standards
- Follow Magento 2 coding standards
- Use dependency injection for class dependencies
- Implement proper exception handling with custom ApiException classes
- Use interfaces for service contracts
- Follow PSR-4 autoloading conventions