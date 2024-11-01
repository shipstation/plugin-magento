# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [2.5.1-beta] - 2024-11-01
### Added
- Updated custom store export implementation to include the payment method.
### Update
- Release Job 

## [2.5.0-beta] - 2024-10-30
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
