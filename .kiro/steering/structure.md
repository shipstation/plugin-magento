# Project Structure

## Module Organization
The project follows Magento 2's standard module structure under the `Api/` directory:

### Core Directories

#### `/Api` - Service Contracts & Interfaces
- Contains interface definitions for the module's public API
- Follow naming convention: `*Interface.php`

#### `/Block` - View Layer Components
- `/Block/System/Config/` - Admin configuration blocks
- Used for custom form elements and admin UI components

#### `/Console` - CLI Commands
- `/Console/Command/` - Custom Magento CLI commands
- Example: `DebugManagementCommand.php`

#### `/Controller` - HTTP Request Handlers
- `/Controller/Adminhtml/` - Admin panel controllers
- `/Controller/Auctane/` - Main API endpoint
- `/Controller/Diagnostics/` - Health check endpoints
- `/Controller/InventoryFetch/` - Inventory retrieval
- `/Controller/InventoryPush/` - Inventory updates
- `/Controller/SalesOrdersExport/` - Order export
- `/Controller/ShipmentNotification/` - Shipping updates

#### `/Exception` - Custom Exception Classes
- All API-specific exceptions inherit from `ApiException`
- HTTP status code mapping for proper API responses

#### `/Helper` - Utility Classes
- `/Helper/Data.php` - Common helper functions

#### `/Model` - Business Logic & Data Models
- `/Model/Action/` - Business action classes
- `/Model/Carrier/` - Custom shipping carrier implementation
- `/Model/Config/Source/` - Configuration option sources
- `/Model/OrderSourceAPI/` - API request/response models
  - `/Models/` - Data transfer objects
  - `/Requests/` - API request classes
  - `/Responses/` - API response classes

#### `/Plugin` - Magento Interceptors
- Used to modify existing Magento functionality
- Example: CSRF validation bypass

#### `/Request` - Request Processing
- Authentication and request validation logic

### Configuration Files (`/etc`)

#### Core Configuration
- `module.xml` - Module registration and version
- `config.xml` - Default configuration values
- `di.xml` - Dependency injection configuration
- `acl.xml` - Access control list for admin permissions
- `webapi.xml` - Web API route definitions

#### Area-Specific Configuration
- `/etc/adminhtml/` - Admin panel specific configs
  - `routes.xml` - Admin routing
  - `system.xml` - System configuration structure
- `/etc/frontend/` - Frontend specific configs

### Other Important Directories

#### `/i18n` - Internationalization
- Translation files for multiple locales (en_US, de_DE, es_MX, fr_CA, fr_FR)

#### `/view` - Templates & UI Components
- `/view/adminhtml/templates/` - Admin panel templates

## Naming Conventions

### Classes
- Controllers: `*Controller.php` or `Index.php` in controller directories
- Models: Descriptive names in `/Model/` directory
- Exceptions: `*Exception.php` suffix
- Interfaces: `*Interface.php` suffix

### Files
- PHP files use PascalCase
- Configuration files use lowercase with underscores
- Template files use `.phtml` extension

## Architecture Patterns

### Controller Pattern
- Base controllers (`BaseController.php`, `BaseAuthorizedController.php`)
- Trait-based shared functionality (`BaseControllerTrait.php`)
- Consistent error handling and JSON response format

### Service Layer
- Interface-based service contracts in `/Api`
- Implementation in `/Model`
- Dependency injection for loose coupling

### Exception Handling
- Custom exception hierarchy with HTTP status codes
- Centralized error response formatting
- Proper logging integration