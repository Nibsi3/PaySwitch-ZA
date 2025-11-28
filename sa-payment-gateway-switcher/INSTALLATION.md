# SA Payment Gateway Switcher - Installation Guide

## Prerequisites

1. **WordPress** 5.8 or higher
2. **WooCommerce** 5.0 or higher
3. **PHP** 8.0 or higher
4. **MySQL** 5.6 or higher

## Installation Steps

### Method 1: Manual Installation

1. Download or clone this plugin to your computer
2. Upload the entire `sa-payment-gateway-switcher` folder to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **SA Gateways** in the WordPress admin menu

### Method 2: WordPress Admin Installation

1. Go to **Plugins > Add New** in WordPress admin
2. Click **Upload Plugin**
3. Choose the plugin zip file
4. Click **Install Now**
5. Activate the plugin

## Initial Setup

### 1. Verify WooCommerce is Active

The plugin requires WooCommerce. If WooCommerce is not installed, you'll see a notice in the admin area.

### 2. Configure Your First Gateway

1. Go to **SA Gateways > Gateways** tab
2. Find the payment gateway you want to use (e.g., Payfast)
3. Click **Configure**
4. Enter your API credentials:
   - **Merchant ID** / **Site Code** / **Client ID** (varies by gateway)
   - **API Key** / **Secret Key** / **Password** (varies by gateway)
   - Enable **Sandbox Mode** for testing
5. Click **Save Configuration**

### 3. Enable and Set Default Gateway

1. Toggle the switch to **Enable** your configured gateway
2. Click **Set as Default** to make it your primary payment method
3. The gateway is now active!

## Gateway Configuration Details

### Payfast
- **Merchant ID**: Found in your Payfast merchant dashboard
- **Merchant Key**: Found in your Payfast merchant dashboard
- **Passphrase**: Optional, for additional security
- **API URL**: Automatically set based on sandbox mode

### Ozow
- **Site Code**: Your Ozow site identifier
- **API Key**: Your Ozow API key
- **Private Key**: Your Ozow private key for signature generation

### Yoco
- **Secret Key**: Your Yoco secret key (starts with `sk_`)
- **Public Key**: Your Yoco public key (starts with `pk_`)

### Peach Payments
- **Entity ID**: Your Peach Payments entity identifier
- **User ID**: Your Peach Payments user ID
- **Password**: Your Peach Payments password

### PayGate
- **PayGate ID**: Your PayGate merchant ID
- **Encryption Key**: Your PayGate encryption key

### Paystack ZA
- **Secret Key**: Your Paystack secret key (starts with `sk_`)
- **Public Key**: Your Paystack public key (starts with `pk_`)

### SnapScan
- **Merchant ID**: Your SnapScan merchant ID
- **API Key**: Your SnapScan API key

### Zapper
- **Site ID**: Your Zapper site identifier
- **API Key**: Your Zapper API key

### Stitch
- **Client ID**: Your Stitch client ID
- **Client Secret**: Your Stitch client secret

## Testing Your Gateway

1. Go to **SA Gateways > Testing** tab
2. Find your configured gateway
3. Click **Run Test**
4. Review the results:
   - **Status**: Success/Failed
   - **Response Time**: API response time in milliseconds
   - **Health Score**: Overall gateway health (0-100)

## Premium License Activation

### Free vs Premium

**Free Features:**
- Enable 1 gateway at a time
- Basic transaction logs (20 entries)
- Sandbox testing
- Gateway configuration

**Premium Features:**
- Multiple gateways enabled simultaneously
- Advanced analytics and comparison charts
- Automatic failover routing
- Unlimited transaction logs
- Load balancing
- Scheduled daily tests

### Activating Premium

1. Go to **SA Gateways > Premium** tab
2. Enter your license key
3. Click **Activate**
4. Premium features will unlock immediately

### License Types

- **Monthly**: Recurring monthly subscription
- **Lifetime**: One-time payment, lifetime access

## Using Failover Routing (Premium)

1. Enable multiple gateways in the **Gateways** tab
2. Set your primary gateway as default
3. Go to **Settings** tab
4. Enable **Failover Routing**
5. If the primary gateway fails, the plugin will automatically try backup gateways

## Viewing Transaction Logs

1. Go to **SA Gateways > Logs** tab
2. Filter by gateway (optional)
3. View all transaction attempts with:
   - Date and time
   - Gateway used
   - Transaction ID
   - Order ID
   - Amount
   - Status (Success/Failed)
   - Response time

## Analytics Dashboard (Premium)

1. Go to **SA Gateways > Analytics** tab
2. Select time period (7, 30, or 90 days)
3. View comparison charts:
   - **Fees Comparison**: Percentage and fixed fees
   - **Success Rates**: Transaction success percentages
   - **Response Times**: Average API response times
   - **Timeline**: Daily performance trends

## Troubleshooting

### Gateway Not Connecting

1. Verify your API credentials are correct
2. Check if you're using sandbox credentials in production mode
3. Ensure your server can make outbound HTTPS requests
4. Check firewall settings
5. Review the gateway's API documentation for any changes

### License Activation Fails

1. Verify your license key is correct
2. Check your internet connection
3. Ensure your WordPress site can make outbound HTTPS requests
4. Contact support if the issue persists

### Database Tables Not Created

1. Deactivate the plugin
2. Delete the plugin folder
3. Reinstall the plugin
4. The tables will be created on activation

### WooCommerce Integration

The plugin integrates with WooCommerce at the gateway level. To use these gateways in WooCommerce:

1. Configure the gateway in SA Payment Gateway Switcher
2. The gateway will be available in WooCommerce checkout
3. Transactions will be logged in the plugin's log system

## Support

For support, feature requests, or bug reports:
- Email: support@example.com
- Documentation: [Link to documentation]
- GitHub Issues: [Link to GitHub]

## Security Notes

- Never share your API keys or credentials
- Use sandbox mode for testing
- Keep the plugin updated
- Use strong passwords for your WordPress admin account
- Enable SSL/HTTPS on your site

## Uninstallation

1. Go to **Plugins** in WordPress admin
2. Deactivate **SA Payment Gateway Switcher**
3. Click **Delete**
4. Optionally, manually remove database tables:
   - `wp_sapgs_logs`
   - `wp_sapgs_tests`

## Changelog

See `readme.txt` for version history and changelog.

