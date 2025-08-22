# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [2.5.7] - 2025-08-22
### Update
- Replace signatures like Type $x = null with ?Type $x = null (or Type|null)
- The other way was deprecated in PHP 8.4
- Contribution made by [ckd](https://github.com/ckd)

## [2.5.6] - 2025-05-19
### Update
- Removed const string from ShipmentNotification [ORD-4355]() Caused issued in PHP 8.2 and below
- Bumped to resync with Adobes published version

## [2.5.3] - 2025-05-14
### Added
- Added UPC mapping functionality to order exports
- Added ability to select product attribute to use as UPC value in order exports

# [2.5.2] - 2025-04-17
### Update
- Fixed Issue with page sizing with empty results

## [2.5.1] - 2024-11-01
### Added
- Updated custom store export implementation to include the payment method.
### Update
- Release Job 

## [2.5.0] - 2024-10-30
### Added
- ShipEngine Connect Models
- Release Action
- New API Endpoints
    - [InventoryFetch (Beta)](#)
    - [InventoryPush (Beta)](#)
    - [SalesOrdersExport](https://connect.shipengine.com/orders/reference/operation/OrderSource_SalesOrdersExport/)
    - [ShipmentNotification](https://connect.shipengine.com/orders/reference/operation/OrderSource_ShipmentNotification/)
    - Diagnostics Routes
        - Liveness - Endpoint to determine if api is available
        - Version - Details about Magento installation & plugin version
