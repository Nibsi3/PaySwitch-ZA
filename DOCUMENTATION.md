# PaySwitch ZA - Complete Documentation

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Quick Start Guide](#quick-start-guide)
4. [Gateway Configuration](#gateway-configuration)
5. [Features](#features)
6. [Premium Features](#premium-features)
7. [Gateway-Specific Guides](#gateway-specific-guides)
8. [API Reference](#api-reference)
9. [Developer Guide](#developer-guide)
10. [Troubleshooting](#troubleshooting)
11. [FAQ](#faq)
12. [Changelog](#changelog)

---

## Overview

**PaySwitch ZA** is a comprehensive WordPress plugin designed for South African e-commerce businesses to manage, test, switch, compare, and optimize multiple payment gateways from a single unified dashboard.

### Key Benefits

- **Unified Management**: Control all payment gateways from one interface
- **Easy Switching**: Change default gateway with one click
- **Smart Optimization**: Automatic failover and load balancing (Premium)
- **Performance Monitoring**: Track success rates, response times, and uptime
- **Cost Optimization**: Compare fees and optimize payment costs
- **Comprehensive Testing**: Test connections before going live

### Business Impact: Payment Gateway Downtime in South Africa

Based on industry-wide e-commerce performance data in South Africa, payment-gateway downtime is estimated to account for a significant share of lost online sales. Typical checkout abandonment rates in the region average between 60–80%, and approximately 5–15% of these abandoned checkouts can be attributed directly to payment-gateway failures or downtime.

In practical terms, this means that for every 100 attempted checkouts, an estimated 5–15 transactions are lost specifically because the active paygate was unavailable or failed during payment processing.

At scale, a South African e-commerce platform processing around 1 million checkout attempts per month may lose approximately:

- **50,000–150,000 transactions per month**
- **600,000–1.8 million transactions per year**

These figures represent approximate national-market norms and provide a baseline for assessing the importance of:

- Maintaining high gateway uptime
- Offering multiple fallback payment providers
- Intelligently routing users to the most reliable available payment method

**PaySwitch ZA addresses these critical business needs** by providing automatic failover routing, real-time uptime monitoring, and intelligent gateway selection to minimize revenue loss from payment gateway failures.

### Supported Gateways

1. **Payfast** - Leading South African payment gateway
2. **Ozow** - Instant EFT and card processing
3. **Yoco** - Card payment solutions
4. **Peach Payments** - Multi-channel payment processing
5. **PayGate** - Secure online payment service provider
6. **Paystack ZA** - Modern payment infrastructure
7. **SnapScan** - QR code and mobile payments
8. **Zapper** - Mobile payment solutions
9. **Stitch** - Payment infrastructure platform

---

## Installation

### Requirements

- **WordPress**: 5.8 or higher
- **WooCommerce**: 5.0 or higher
- **PHP**: 8.0 or higher
- **MySQL**: 5.6 or higher
- **SSL Certificate**: Required for production use

### Installation Steps

1. **Download the Plugin**
   - Download the `sa-payment-gateway-switcher.zip` file

2. **Install via WordPress Admin**
   - Go to **Plugins > Add New**
   - Click **Upload Plugin**
   - Choose the zip file
   - Click **Install Now**
   - Click **Activate Plugin**

3. **Verify Installation**
   - Navigate to **PaySwitch ZA** in the WordPress admin menu
   - You should see the dashboard with all 9 gateways

### Database Setup

The plugin automatically creates the following database tables on activation:

- `wp_sapgs_logs` - Transaction logs
- `wp_sapgs_tests` - Test results
- `wp_sapgs_uptime` - Uptime monitoring data (hourly checks)
- `wp_sapgs_fees` - Fee history tracking (daily checks)

---

## Quick Start Guide

### Step 1: Configure Your First Gateway

1. Go to **PaySwitch ZA > Gateways**
2. Find the gateway you want to use (e.g., Payfast)
3. Click **Configure**
4. Enter your API credentials:
   - For test mode: Check "Test Mode" and enter test credentials
   - For live mode: Uncheck "Test Mode" and enter live credentials
5. Click **Save Configuration**
   - The system will validate your credentials
   - If valid, the gateway will be automatically enabled

### Step 2: Enable the Gateway

- The gateway is automatically enabled after successful configuration
- If needed, toggle the switch to enable/disable manually

### Step 3: Set as Default (Optional)

- Click **Set as Default** to make it your primary payment method
- Only one gateway can be default at a time

### Step 4: Test the Connection

1. Go to **Testing** tab
2. Find your gateway
3. Click **Run Test**
4. Review the results:
   - **Status**: Success/Failed
   - **Response Time**: API response time in milliseconds
   - **Health Score**: Overall gateway health (0-100)

---

## Gateway Configuration

### Configuration Modal

Each gateway has a configuration modal accessible via the **Configure** button. The modal includes:

- **Test Mode Toggle**: Switch between test and live credentials
- **Credential Fields**: Input fields for API keys, merchant IDs, etc.
- **Validation**: Automatic credential validation on save
- **Error Messages**: Detailed error messages if credentials are invalid

### Test Mode vs Live Mode

- **Test Mode**: Use sandbox/test credentials for development and testing
- **Live Mode**: Use production credentials for real transactions

When switching between modes, the form automatically shows the appropriate credential fields.

### Credential Links

Each credential field includes a clickable link that opens the gateway's credential page where you can:
- Copy your API keys
- Generate new keys
- View your credentials
- Access your dashboard

### Configuration Validation

When you save configuration:
1. The system validates credentials by testing the connection
2. If validation fails:
   - Error message is displayed with specific guidance
   - Original configuration is preserved
   - You can correct and try again
3. If validation succeeds:
   - Configuration is saved permanently
   - Gateway is automatically enabled
   - Gateway is placed in correct sort order

### Closing Without Saving

If you close the configuration modal without successfully validating:
- Incorrect credentials are **NOT saved**
- Original configuration is restored
- No changes are made to your gateway settings

---

## Features

### Free Features

#### Gateway Management
- Enable/disable gateways with toggle switches
- Set default gateway
- Configure gateway credentials
- View gateway status (connected/intermittent/offline)
- Gateway logos for easy identification

#### Testing
- Individual gateway connection testing
- Response time measurement
- Health score calculation
- Test results display

#### Logging
- Transaction logs (last 20 entries for free users)
- Filter by gateway
- View transaction details:
  - Date and time
  - Gateway used
  - Transaction ID
  - Order ID
  - Amount
  - Status (Success/Failed)
  - Response time

#### Sorting (Premium Only)
- Sort gateways by:
  - Smart Weighted Score (Recommended)
  - Highest Approval Rate
  - Highest Success Rate
  - Lowest Fees
  - Fastest Response Time
  - Highest Uptime

### Premium Features

#### Multiple Gateways
- Enable up to 9 gateways simultaneously
- Free plan limited to 2 gateways

#### Advanced Analytics
- Comprehensive analytics dashboard
- Comparison charts:
  - Fees comparison (percentage and fixed)
  - Success rates
  - Response times
  - Approval rates
  - Downtime patterns
  - 7-day performance trends
- Time period filtering (7, 30, 90 days)

#### Routing Modes
- **Default**: Primary gateway + failover
- **Highest Approval Rate**: Routes to gateway with best success rate
- **Load Balancing**: Distributes payments evenly across gateways

#### Automatic Failover
- Automatically routes failed payments to backup gateways
- Ensures payment processing continues even if one gateway is down

#### Unlimited Logging
- Unlimited transaction logs
- Full transaction history
- Advanced filtering options

#### Optimization Engine
- Intelligent optimization suggestions based on real transaction data
- Fee optimization recommendations
- Performance improvement tips
- Default gateway suggestions based on success rates
- One-click application of suggestions
- Analyzes patterns across all enabled gateways

#### Uptime Monitoring
- Hourly gateway availability checks (automatic)
- Downtime tracking and patterns
- Uptime percentage calculation
- Historical uptime data storage
- Real-time status indicators
- Automatic monitoring of all enabled gateways

#### Fee Monitoring
- Daily fee tracking for all gateways
- Fee history storage and comparison
- Cost optimization recommendations
- Automatic fee checks on activation
- Historical fee data analysis
- Helps identify best pricing options

#### Scheduled Testing
- Automatic daily gateway tests
- Performance trend tracking
- Health monitoring

---

## Premium Features

### License Activation

1. Go to **PaySwitch ZA > Premium** tab
2. Enter your license key
3. Click **Activate**
4. Premium features are immediately unlocked

### Test License

For testing purposes, use: `TEST-PREMIUM-LICENSE-2024`

This enables all premium features for evaluation.

### Premium Benefits

- **All 9 Gateways**: Enable all payment gateways simultaneously
- **Advanced Analytics**: Comprehensive performance analytics
- **Smart Routing**: Automatic failover and load balancing
- **Unlimited Logs**: Full transaction history
- **Optimization**: AI-powered optimization suggestions
- **Monitoring**: 24/7 uptime monitoring

---

## Gateway-Specific Guides

### Payfast

**Configuration Fields:**
- Merchant ID
- Merchant Key
- Passphrase (optional)

**Credential URLs:**
- Dashboard: https://www.payfast.co.za/user/login
- API Keys: https://www.payfast.co.za/user/api-keys

**Fees:**
- Percentage: 2.9%
- Fixed: R2.00

**Test Mode:** Supported

### Ozow

**Configuration Fields:**
- Site Code
- API Key
- Private Key

**Credential URLs:**
- Dashboard: https://dashboard.ozow.com
- API Documentation: https://docs.ozow.com

**Fees:**
- Percentage: 2.75%
- Fixed: R1.50

**Test Mode:** Supported

### Yoco

**Configuration Fields:**
- Secret Key (starts with `sk_`)
- Public Key (starts with `pk_`)

**Credential URLs:**
- Dashboard: https://www.yoco.com/za/developers/api-keys/
- API Keys: https://www.yoco.com/za/developers/api-keys/

**Fees:**
- Percentage: 2.95%
- Fixed: R0.00

**Test Mode:** Supported (uses test_secret_key and test_public_key)

### Peach Payments

**Configuration Fields:**
- Entity ID
- User ID
- Password

**Credential URLs:**
- Dashboard: https://www.peachpayments.com
- API Documentation: https://developer.peachpayments.com

**Fees:**
- Percentage: 2.75%
- Fixed: R0.00

**Test Mode:** Supported

### PayGate

**Configuration Fields:**
- PayGate ID
- Encryption Key

**Credential URLs:**
- Dashboard: https://secure.paygate.co.za
- Documentation: https://www.paygate.co.za/developers/

**Fees:**
- Percentage: 3.0%
- Fixed: R2.00

**Test Mode:** Supported

### Paystack ZA

**Configuration Fields:**
- Secret Key
- Public Key

**Credential URLs:**
- Dashboard: https://dashboard.paystack.com
- API Keys: https://dashboard.paystack.com/#/settings/developer

**Fees:**
- Percentage: 2.9%
- Fixed: R2.00

**Test Mode:** Supported

### SnapScan

**Configuration Fields:**
- Merchant ID
- API Key

**Credential URLs:**
- Dashboard: https://merchant.snapscan.co.za
- API Documentation: https://developer.snapscan.co.za

**Fees:**
- Percentage: 2.75%
- Fixed: R0.00

**Test Mode:** Supported

### Zapper

**Configuration Fields:**
- Site ID
- API Key

**Credential URLs:**
- Dashboard: https://www.zapper.co.za/merchant
- API Documentation: https://developer.zapper.co.za

**Fees:**
- Percentage: 2.5%
- Fixed: R0.00

**Test Mode:** Supported

### Stitch

**Configuration Fields:**
- Client ID
- Client Secret

**Credential URLs:**
- Dashboard: https://www.stitch.money
- API Documentation: https://docs.stitch.money

**Fees:**
- Percentage: 2.5%
- Fixed: R0.00

**Test Mode:** Supported

---

## API Reference

### AJAX Endpoints

All AJAX endpoints require:
- `nonce`: Security nonce (provided in `sapgsData.nonce`)
- `action`: Action name

#### Gateway Management

**`sapgs_toggle_gateway`**
- Enable or disable a gateway
- Parameters: `gateway_id`, `enabled`
- Returns: Success/error with gateway status

**`sapgs_set_default`**
- Set a gateway as default
- Parameters: `gateway_id`
- Returns: Success/error message

**`sapgs_get_gateway_config`**
- Get gateway configuration
- Parameters: `gateway_id`
- Returns: Configuration fields and current values

**`sapgs_save_gateway_config`**
- Save gateway configuration
- Parameters: `gateway_id`, `config` (JSON)
- Returns: Success/error with validation results

#### Testing

**`sapgs_test_gateway`**
- Test gateway connection
- Parameters: `gateway_id`
- Returns: Test results (success, response_time, health_score)

**`sapgs_bulk_test`**
- Test all enabled gateways (Premium)
- Parameters: None
- Returns: Test results for all gateways

#### Analytics (Premium)

**`sapgs_get_analytics`**
- Get analytics data
- Parameters: `days` (7, 30, or 90)
- Returns: Comprehensive analytics data

**`sapgs_get_sorting_data`**
- Get sorting data for gateways
- Parameters: None
- Returns: Sorting metrics for all gateways

**`sapgs_save_gateway_order`**
- Save gateway sort order
- Parameters: `order` (JSON array), `sort_type`
- Returns: Success/error

#### Logging

**`sapgs_get_logs`**
- Get transaction logs
- Parameters: `gateway_id` (optional), `limit`
- Returns: Array of log entries

#### License

**`sapgs_activate_license`**
- Activate premium license
- Parameters: `license_key`
- Returns: Success/error with license info

**`sapgs_deactivate_license`**
- Deactivate premium license
- Parameters: None
- Returns: Success/error

#### Optimization (Premium)

**`sapgs_get_optimization_suggestions`**
- Get optimization suggestions
- Parameters: None
- Returns: Array of suggestions

**`sapgs_apply_optimization`**
- Apply an optimization suggestion
- Parameters: `suggestion` (JSON)
- Returns: Success/error

#### Fee Monitoring (Premium)

**`sapgs_get_fee_history`**
- Get fee history for gateways
- Parameters: `gateway_id` (optional), `days` (optional)
- Returns: Array of fee history entries

**`sapgs_get_fee_comparison`**
- Compare fees across all gateways
- Parameters: None
- Returns: Fee comparison data

#### Uptime Monitoring (Premium)

**`sapgs_get_uptime_data`**
- Get uptime statistics for gateways
- Parameters: `gateway_id` (optional), `days` (optional)
- Returns: Uptime percentage and history

---

## Developer Guide

### Adding a New Gateway

1. **Create Gateway Class**
   ```php
   class SAPGS_NewGateway implements SAPGS_GatewayInterface {
       private $id = 'new_gateway';
       
       public function get_id() {
           return $this->id;
       }
       
       public function get_name() {
           return 'New Gateway';
       }
       
       // Implement all interface methods
   }
   ```

2. **Register in GatewayManager**
   ```php
   $this->gateways['new_gateway'] = new SAPGS_NewGateway();
   ```

3. **Add Configuration Fields**
   ```php
   public function get_config_fields() {
       return array(
           'api_key' => array(
               'label' => 'API Key',
               'type' => 'text',
               'required' => true
           )
       );
   }
   ```

4. **Implement Required Methods**
   - `connect()` - Test connection
   - `charge()` - Process payment
   - `refund()` - Process refund
   - `get_fees()` - Return fee structure
   - `get_credential_url()` - Return credential page URL

### Hooks and Filters

**Available Hooks:**
- `sapgs_before_gateway_charge` - Before processing payment
- `sapgs_after_gateway_charge` - After processing payment
- `sapgs_gateway_config_saved` - After saving configuration

**Example Usage:**
```php
add_action('sapgs_after_gateway_charge', function($gateway_id, $result) {
    // Custom logic after payment
}, 10, 2);
```

### Database Schema

**Transaction Logs Table: `wp_sapgs_logs`**
```sql
- id (bigint)
- gateway_id (varchar)
- transaction_id (varchar)
- order_id (bigint)
- amount (decimal)
- currency (varchar)
- status (varchar)
- response_time (int)
- error_message (text)
- request_data (text)
- response_data (text)
- created_at (datetime)
```

**Test Results Table: `wp_sapgs_tests`**
```sql
- id (bigint)
- gateway_id (varchar)
- test_type (varchar)
- success (tinyint)
- response_time (int)
- error_message (text)
- health_score (int)
- test_data (text)
- created_at (datetime)
```

**Uptime Table: `wp_sapgs_uptime`**
```sql
- id (bigint)
- gateway_id (varchar)
- is_up (tinyint)
- response_time (int)
- checked_at (datetime)
```

**Fee History Table: `wp_sapgs_fees`**
```sql
- id (bigint)
- gateway_id (varchar)
- percentage_fee (decimal)
- fixed_fee (decimal)
- checked_at (datetime)
```

### Customization

**Styling:**
- Override CSS variables in your theme
- Custom styles in `admin/assets/admin.css`

**JavaScript:**
- Extend functionality in `admin/assets/admin.js`
- Use `sapgsData` object for AJAX calls

---

## Troubleshooting

### Gateway Not Connecting

**Symptoms:**
- Red status indicator
- "Gateway is not configured" message
- Connection test fails

**Solutions:**
1. Verify API credentials are correct
2. Check if using test credentials in live mode (or vice versa)
3. Ensure server can make outbound HTTPS requests
4. Check firewall settings
5. Verify credentials haven't expired
6. Check for extra spaces in credential fields

### Configuration Validation Fails

**Common Errors:**
- **401 Unauthorized**: Invalid API keys
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Incorrect merchant ID or endpoint
- **Timeout**: Network connectivity issues

**Solutions:**
- Double-check all credential fields
- Verify you're using the correct environment (test/live)
- Contact gateway support if issue persists
- Check gateway documentation for any API changes

### Gateway Not Enabling

**Possible Causes:**
1. Gateway not configured (missing credentials)
2. Free plan limit reached (2 gateways max)
3. Validation failed

**Solutions:**
1. Configure gateway first
2. Upgrade to Premium for more gateways
3. Fix validation errors

### Premium Features Not Working

**Check:**
1. License is activated (Premium tab)
2. License is valid and not expired
3. Grace period hasn't expired (7 days)

**Solutions:**
1. Re-activate license
2. Contact support for license issues
3. Use test license: `TEST-PREMIUM-LICENSE-2024`

### Sorting Not Working

**For Premium Users:**
1. Ensure license is active
2. Select sort option from dropdown
3. Wait for sorting to complete

**Note:** Drag and drop has been removed. Use the sort dropdown instead.

### Error Messages Showing in Wrong Gateway

**Solution:**
- This has been fixed. Each gateway now has isolated error handling.
- If you see this, refresh the page and try again.

---

## FAQ

### General Questions

**Q: How many gateways can I enable?**
- Free: 2 gateways maximum
- Premium: All 9 gateways

**Q: Can I use test and live credentials at the same time?**
- No, you switch between test and live mode using the toggle in the configuration modal.

**Q: What happens if a gateway fails?**
- Premium users: Automatic failover to backup gateways
- Free users: Payment fails (no failover)

**Q: How do I get my API credentials?**
- Click the link next to each credential field to go directly to the gateway's credential page.

**Q: Are my credentials secure?**
- Yes, all credentials are stored securely in WordPress options
- Passwords are stored as password fields (masked)
- All data is encrypted in transit

### Premium Questions

**Q: What's included in Premium?**
- All 9 gateways enabled simultaneously
- Advanced analytics and charts
- Automatic failover routing
- Unlimited transaction logs
- Load balancing
- Optimization suggestions
- Uptime monitoring

**Q: How do I upgrade to Premium?**
- Go to Premium tab
- Enter your license key
- Click Activate

**Q: Can I test Premium features?**
- Yes, use license key: `TEST-PREMIUM-LICENSE-2024`

**Q: What happens when my license expires?**
- 7-day grace period
- After grace period, premium features are disabled
- Your configuration is preserved

### Technical Questions

**Q: Does this work with WooCommerce?**
- Yes, the plugin integrates with WooCommerce
- Gateways appear in WooCommerce checkout

**Q: Can I customize the gateway order?**
- Premium users can sort gateways using the sort dropdown
- Sorting options include approval rate, success rate, fees, etc.

**Q: How are transactions logged?**
- All payment attempts are logged automatically
- Logs include full request/response data
- Free users: Last 20 entries
- Premium users: Unlimited

**Q: Can I add custom gateways?**
- Yes, see Developer Guide for instructions
- Implement `SAPGS_GatewayInterface`
- Register in `GatewayManager`

---

## Changelog

### Version 1.0.0

**Initial Release**

**Features:**
- 9 payment gateway integrations
- Gateway management dashboard
- Configuration modals with test/live mode
- Automatic credential validation
- Transaction logging
- Sandbox testing
- Premium license system
- Advanced analytics (Premium)
- Automatic failover routing (Premium)
- Gateway sorting (Premium)
- Gateway logos
- Status indicators
- Error handling with detailed messages
- Configuration state isolation
- Optimization Engine (Premium) - Intelligent performance suggestions
- Uptime Monitor (Premium) - Hourly availability tracking
- Fee Monitor (Premium) - Daily fee tracking and cost optimization

**Improvements:**
- Enabled toggle shows green when active
- Credential validation before saving
- Original config restored if validation fails
- Isolated error messages per gateway
- Automatic gateway enabling on successful configuration
- Smart sorting with enabled gateways first

**Bug Fixes:**
- Fixed Yoco test mode key handling
- Fixed error messages showing in wrong gateways
- Fixed configuration state isolation
- Improved drag and drop (removed in favor of dropdown sorting)

---

## Support

For support, feature requests, or bug reports:
- Check this documentation first
- Review troubleshooting section
- Contact support: support@example.com

---

## License

This plugin is licensed under the GPL v2 or later.

---

**Last Updated:** 2024
**Version:** 1.0.0

