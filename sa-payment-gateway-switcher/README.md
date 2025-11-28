# SA Payment Gateway Switcher

A comprehensive WordPress plugin for managing South African payment gateways in WooCommerce.

## Overview

SA Payment Gateway Switcher allows South African e-commerce store owners to manage, test, switch, compare, and optimize multiple payment gateways from a single unified dashboard.

## Features

### Core Features
- ✅ **9 Supported Gateways**: Payfast, Ozow, Yoco, Peach Payments, PayGate, Paystack ZA, SnapScan, Zapper, Stitch
- ✅ **Unified Management**: Enable/disable and configure all gateways from one interface
- ✅ **Easy Switching**: Change default gateway with one click
- ✅ **Sandbox Testing**: Test connections, response times, and health scores
- ✅ **Transaction Logging**: Track all payment attempts with detailed logs
- ✅ **Gateway Comparison**: Compare fees, success rates, and performance
- ✅ **Premium Licensing**: Monthly and lifetime license options

### Premium Features
- 🔒 Multiple gateways enabled simultaneously
- 🔒 Advanced analytics and comparison charts
- 🔒 Automatic failover routing
- 🔒 Unlimited transaction logs
- 🔒 Load balancing
- 🔒 Scheduled daily tests
- 🔒 Performance optimization suggestions

## Requirements

- WordPress 5.8+
- WooCommerce 5.0+
- PHP 8.0+
- MySQL 5.6+

## Installation

1. Upload the plugin to `/wp-content/plugins/`
2. Activate through the WordPress 'Plugins' menu
3. Navigate to **SA Gateways** in the admin menu
4. Configure your payment gateways

See `INSTALLATION.md` for detailed setup instructions.

## Quick Start

1. **Configure a Gateway**:
   - Go to SA Gateways > Gateways
   - Click "Configure" on your desired gateway
   - Enter API credentials
   - Save configuration

2. **Enable Gateway**:
   - Toggle the switch to enable
   - Click "Set as Default" to make it primary

3. **Test Connection**:
   - Go to Testing tab
   - Click "Run Test"
   - Review health score and response time

## Plugin Structure

```
sa-payment-gateway-switcher/
├── admin/
│   ├── assets/
│   │   ├── admin.css
│   │   └── admin.js
│   ├── dashboard.php
│   └── settings-page.php
├── core/
│   ├── GatewayInterface.php
│   ├── GatewayManager.php
│   ├── LicenseManager.php
│   ├── Logger.php
│   ├── Metrics.php
│   └── SandboxTester.php
├── gateways/
│   ├── PayfastGateway.php
│   ├── OzowGateway.php
│   ├── YocoGateway.php
│   ├── PeachPaymentsGateway.php
│   ├── PayGateGateway.php
│   ├── PaystackZAGateway.php
│   ├── SnapScanGateway.php
│   ├── ZapperGateway.php
│   └── StitchGateway.php
├── sa-payment-gateway-switcher.php
├── readme.txt
├── README.md
└── INSTALLATION.md
```

## Architecture

### Gateway Interface
All gateways implement `SAPGS_GatewayInterface` with standard methods:
- `connect()` - Test API connection
- `charge()` - Process payment
- `refund()` - Process refund
- `test_connection()` - Full connection test
- `get_logs()` - Retrieve transaction logs
- `get_fees()` - Get fee structure

### Core Components

**GatewayManager**: Manages all gateways, handles failover routing, and gateway switching.

**LicenseManager**: Handles premium license activation, validation, and feature unlocking.

**Logger**: Stores transaction logs in custom database table with filtering and statistics.

**Metrics**: Generates analytics data for comparison charts and performance insights.

**SandboxTester**: Performs comprehensive gateway testing including DNS, TLS, and webhook checks.

## Database Tables

The plugin creates two custom tables on activation:

1. **wp_sapgs_logs**: Transaction logs
   - gateway_id, transaction_id, order_id, amount, status, response_time, etc.

2. **wp_sapgs_tests**: Test results
   - gateway_id, test_type, success, response_time, health_score, etc.

## API Integration

Each gateway connector implements real API endpoints:
- Payfast: `https://www.payfast.co.za` / `https://sandbox.payfast.co.za`
- Ozow: `https://api.ozow.com`
- Yoco: `https://api.yoco.com/v1`
- Peach Payments: `https://oppwa.com` / `https://test.peachpayments.com`
- PayGate: `https://secure.paygate.co.za/payweb3`
- Paystack ZA: `https://api.paystack.co`
- SnapScan: `https://pos.snapscan.io/merchant/api/v1`
- Zapper: `https://api.zapper.co.za`
- Stitch: `https://api.stitch.money`

## License System

The plugin includes a built-in licensing system:
- License server URL: Configured in `SAPGS_LICENSE_SERVER` constant
- Activation: Validates license key with remote server
- Validation: Daily automatic license check
- Grace Period: 7 days after expiration

## Development

### Adding a New Gateway

1. Create a new class in `gateways/` implementing `SAPGS_GatewayInterface`
2. Register the gateway in `GatewayManager::register_gateways()`
3. Add configuration fields in `get_config_fields()`
4. Implement all required interface methods

### Hooks and Filters

The plugin uses standard WordPress hooks:
- `sapgs_daily_license_check` - Daily license validation
- `sapgs_webhook_{gateway_id}` - Gateway webhook handlers

## Security

- All API keys stored encrypted in WordPress options
- Nonce verification on all AJAX requests
- Capability checks for admin functions
- Sanitization on all user inputs
- Prepared statements for database queries

## Support

- Documentation: See `INSTALLATION.md`
- Support Email: support@example.com
- License Issues: Check Premium tab in admin

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Built for South African e-commerce businesses to simplify payment gateway management and optimization.

