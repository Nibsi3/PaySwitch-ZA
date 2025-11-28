<?php
/**
 * Plugin Name: PaySwitch ZA
 * Plugin URI: https://example.com/sa-payment-gateway-switcher
 * Description: Manage, test, switch, and optimize South African payment gateways (Payfast, Paystack ZA, Ozow, Yoco, Peach Payments, PayGate, SnapScan, Zapper, Stitch) for WooCommerce.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sapgs
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('SAPGS_VERSION', '1.0.0');
define('SAPGS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SAPGS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SAPGS_PLUGIN_FILE', __FILE__);
define('SAPGS_LICENSE_SERVER', 'https://license.example.com/api'); // Change to your license server

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'SAPGS\\';
    $base_dir = SAPGS_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load core classes
require_once SAPGS_PLUGIN_DIR . 'core/GatewayInterface.php';
require_once SAPGS_PLUGIN_DIR . 'core/GatewayManager.php';
require_once SAPGS_PLUGIN_DIR . 'core/LicenseManager.php';
require_once SAPGS_PLUGIN_DIR . 'core/Logger.php';
require_once SAPGS_PLUGIN_DIR . 'core/Metrics.php';
require_once SAPGS_PLUGIN_DIR . 'core/SandboxTester.php';
require_once SAPGS_PLUGIN_DIR . 'core/OptimizationEngine.php';
require_once SAPGS_PLUGIN_DIR . 'core/UptimeMonitor.php';

// Load gateway classes
require_once SAPGS_PLUGIN_DIR . 'gateways/PayfastGateway.php';
require_once SAPGS_PLUGIN_DIR . 'gateways/OzowGateway.php';
require_once SAPGS_PLUGIN_DIR . 'gateways/YocoGateway.php';
require_once SAPGS_PLUGIN_DIR . 'gateways/PeachPaymentsGateway.php';
require_once SAPGS_PLUGIN_DIR . 'gateways/PayGateGateway.php';
require_once SAPGS_PLUGIN_DIR . 'gateways/PaystackZAGateway.php';
require_once SAPGS_PLUGIN_DIR . 'gateways/SnapScanGateway.php';
require_once SAPGS_PLUGIN_DIR . 'gateways/ZapperGateway.php';
require_once SAPGS_PLUGIN_DIR . 'gateways/StitchGateway.php';

// Load admin
require_once SAPGS_PLUGIN_DIR . 'admin/settings-page.php';
require_once SAPGS_PLUGIN_DIR . 'admin/dashboard.php';

/**
 * Main Plugin Class
 */
class SA_Payment_Gateway_Switcher {
    
    private static $instance = null;
    public $gateway_manager;
    public $license_manager;
    public $logger;
    public $metrics;
    public $sandbox_tester;
    public $optimization_engine;
    public $uptime_monitor;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'check_woocommerce'));
        
        // Initialize components
        $this->gateway_manager = new SAPGS_GatewayManager();
        $this->license_manager = new SAPGS_LicenseManager();
        $this->logger = new SAPGS_Logger();
        $this->metrics = new SAPGS_Metrics();
        $this->sandbox_tester = new SAPGS_SandboxTester();
        $this->optimization_engine = new SAPGS_OptimizationEngine();
        $this->uptime_monitor = new SAPGS_UptimeMonitor();
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('admin_init', array($this, 'register_settings'));
        }
        
        // AJAX handlers
        add_action('wp_ajax_sapgs_test_gateway', array($this, 'ajax_test_gateway'));
        add_action('wp_ajax_sapgs_toggle_gateway', array($this, 'ajax_toggle_gateway'));
        add_action('wp_ajax_sapgs_set_default', array($this, 'ajax_set_default'));
        add_action('wp_ajax_sapgs_activate_license', array($this, 'ajax_activate_license'));
        add_action('wp_ajax_sapgs_deactivate_license', array($this, 'ajax_deactivate_license'));
        add_action('wp_ajax_sapgs_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_sapgs_get_logs', array($this, 'ajax_get_logs'));
        add_action('wp_ajax_sapgs_bulk_test', array($this, 'ajax_bulk_test'));
        add_action('wp_ajax_sapgs_get_optimization_suggestions', array($this, 'ajax_get_optimization_suggestions'));
        add_action('wp_ajax_sapgs_apply_optimization', array($this, 'ajax_apply_optimization'));
        add_action('wp_ajax_sapgs_get_uptime_stats', array($this, 'ajax_get_uptime_stats'));
        add_action('wp_ajax_sapgs_test_webhook', array($this, 'ajax_test_webhook'));
        add_action('wp_ajax_sapgs_save_gateway_config', array($this, 'ajax_save_gateway_config'));
        add_action('wp_ajax_sapgs_get_gateway_config', array($this, 'ajax_get_gateway_config'));
        add_action('wp_ajax_sapgs_get_sorting_data', array($this, 'ajax_get_sorting_data'));
        add_action('wp_ajax_sapgs_save_gateway_order', array($this, 'ajax_save_gateway_order'));
    }
    
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('PaySwitch ZA requires WooCommerce to be installed and active.', 'sapgs');
                echo '</p></div>';
            });
            return;
        }
    }
    
    public function add_admin_menu() {
        // Add custom icon
        $icon_url = SAPGS_PLUGIN_URL . 'admin/assets/icon.svg';
        add_menu_page(
            __('PaySwitch ZA', 'sapgs'),
            __('SA Gateways', 'sapgs'),
            'manage_options',
            'sapgs-dashboard',
            array('SAPGS_Dashboard', 'render'),
            'data:image/svg+xml;base64,' . base64_encode(file_get_contents(SAPGS_PLUGIN_DIR . 'admin/assets/icon.svg')),
            56
        );
        
        add_submenu_page(
            'sapgs-dashboard',
            __('Settings', 'sapgs'),
            __('Settings', 'sapgs'),
            'manage_options',
            'sapgs-settings',
            array('SAPGS_Settings_Page', 'render')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'sapgs') === false) {
            return;
        }
        
        wp_enqueue_style(
            'sapgs-admin',
            SAPGS_PLUGIN_URL . 'admin/assets/admin.css',
            array(),
            SAPGS_VERSION . '.' . time() // Add timestamp to prevent caching
        );
        
        // Add jQuery UI for sortable drag and drop
        wp_enqueue_script('jquery-ui-sortable');
        
        wp_enqueue_script(
            'sapgs-admin',
            SAPGS_PLUGIN_URL . 'admin/assets/admin.js',
            array('jquery', 'jquery-ui-sortable', 'wp-util'),
            SAPGS_VERSION . '.' . time(), // Add timestamp to prevent caching
            true
        );
        
        wp_localize_script('sapgs-admin', 'sapgsData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sapgs_nonce'),
            'isPremium' => $this->license_manager->is_premium_active()
        ));
        
        // Add Chart.js for analytics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            array(),
            '4.4.0',
            true
        );
        
        // Add favicon
        add_action('admin_head', array($this, 'add_favicon'));
    }
    
    public function add_favicon() {
        $icon_url = SAPGS_PLUGIN_URL . 'admin/assets/icon.svg';
        echo '<link rel="icon" type="image/svg+xml" href="' . esc_url($icon_url) . '">';
        echo '<link rel="alternate icon" href="' . esc_url(SAPGS_PLUGIN_URL . 'admin/assets/icon.png') . '">';
    }
    
    /**
     * Run daily speed tests
     */
    public function run_daily_tests() {
        $license_manager = new SAPGS_LicenseManager();
        
        if (!$license_manager->is_premium_active()) {
            return;
        }
        
        $gateway_manager = new SAPGS_GatewayManager();
        $enabled_gateways = $gateway_manager->get_enabled_gateways();
        
        foreach ($enabled_gateways as $id => $gateway) {
            if ($gateway->is_configured()) {
                $this->sandbox_tester->test_gateway($id);
            }
        }
    }
    
    public function register_settings() {
        register_setting('sapgs_settings', 'sapgs_default_gateway');
        register_setting('sapgs_settings', 'sapgs_enabled_gateways', array(
            'type' => 'array',
            'default' => array()
        ));
        register_setting('sapgs_settings', 'sapgs_failover_enabled', array(
            'type' => 'boolean',
            'default' => false
        ));
    }
    
    public function ajax_test_gateway() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $gateway_id = sanitize_text_field($_POST['gateway_id']);
        $result = $this->sandbox_tester->test_gateway($gateway_id);
        
        wp_send_json_success($result);
    }
    
    public function ajax_toggle_gateway() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $gateway_id = sanitize_text_field($_POST['gateway_id']);
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === 'true';
        
        $enabled_gateways = get_option('sapgs_enabled_gateways', array());
        $is_premium = $this->license_manager->is_premium_active();
        
        // Free users can only enable 2 gateways max
        if ($enabled && !$is_premium) {
            // If already at limit (2 gateways) and trying to enable another
            if (count($enabled_gateways) >= 2 && !in_array($gateway_id, $enabled_gateways)) {
                wp_send_json_error(array(
                    'message' => 'Free plan allows only 2 payment gateways. Upgrade to Premium to enable all gateways.',
                    'limit_reached' => true,
                    'current_count' => count($enabled_gateways),
                    'max_free' => 2
                ));
            }
        }
        
        if ($enabled) {
            $enabled_gateways[] = $gateway_id;
            $enabled_gateways = array_unique($enabled_gateways);
        } else {
            $enabled_gateways = array_diff($enabled_gateways, array($gateway_id));
        }
        
        update_option('sapgs_enabled_gateways', $enabled_gateways);
        
        wp_send_json_success(array(
            'enabled_gateways' => $enabled_gateways,
            'is_premium' => $is_premium,
            'enabled_count' => count($enabled_gateways),
            'max_free' => 2
        ));
    }
    
    public function ajax_set_default() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $gateway_id = sanitize_text_field($_POST['gateway_id']);
        update_option('sapgs_default_gateway', $gateway_id);
        
        wp_send_json_success(array('message' => 'Default gateway updated'));
    }
    
    public function ajax_activate_license() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $license_key = sanitize_text_field($_POST['license_key']);
        $result = $this->license_manager->activate_license($license_key);
        
        wp_send_json($result);
    }
    
    public function ajax_deactivate_license() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $this->license_manager->deactivate_license();
        
        wp_send_json_success(array('message' => 'License deactivated successfully'));
    }
    
    public function ajax_get_analytics() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 7;
        $data = $this->metrics->get_analytics_data($days);
        
        wp_send_json_success($data);
    }
    
    public function ajax_get_logs() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $gateway_id = isset($_POST['gateway_id']) ? sanitize_text_field($_POST['gateway_id']) : null;
        $is_premium = $this->license_manager->is_premium_active();
        
        // Free users limited to 20 logs, premium unlimited
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : ($is_premium ? 50 : 20);
        if (!$is_premium) {
            $limit = min($limit, 20);
        }
        
        $logs = $this->logger->get_logs($gateway_id, $limit);
        
        wp_send_json_success($logs);
    }
    
    public function ajax_bulk_test() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $license_manager = new SAPGS_LicenseManager();
        if (!$license_manager->is_premium_active()) {
            wp_send_json_error(array('message' => 'Bulk testing is a premium feature'));
        }
        
        $gateway_ids = isset($_POST['gateway_ids']) ? array_map('sanitize_text_field', $_POST['gateway_ids']) : array();
        
        if (empty($gateway_ids)) {
            $gateway_manager = new SAPGS_GatewayManager();
            $enabled = $gateway_manager->get_enabled_gateways();
            $gateway_ids = array_keys($enabled);
        }
        
        $results = array();
        foreach ($gateway_ids as $gateway_id) {
            $result = $this->sandbox_tester->test_gateway($gateway_id);
            $results[$gateway_id] = $result;
        }
        
        wp_send_json_success($results);
    }
    
    public function ajax_get_optimization_suggestions() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $suggestions = $this->optimization_engine->get_suggestions();
        wp_send_json_success($suggestions);
    }
    
    public function ajax_apply_optimization() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $suggestion = isset($_POST['suggestion']) ? json_decode(stripslashes($_POST['suggestion']), true) : null;
        
        if (!$suggestion) {
            wp_send_json_error(array('message' => 'Invalid suggestion'));
        }
        
        $result = $this->optimization_engine->apply_suggestion($suggestion);
        wp_send_json($result);
    }
    
    public function ajax_get_uptime_stats() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $license_manager = new SAPGS_LicenseManager();
        if (!$license_manager->is_premium_active()) {
            wp_send_json_error(array('message' => 'Uptime monitoring is a premium feature'));
        }
        
        $gateway_id = isset($_POST['gateway_id']) ? sanitize_text_field($_POST['gateway_id']) : null;
        $days = isset($_POST['days']) ? intval($_POST['days']) : 7;
        
        if (!$gateway_id) {
            wp_send_json_error(array('message' => 'Gateway ID required'));
        }
        
        $stats = $this->uptime_monitor->get_uptime_stats($gateway_id, $days);
        $patterns = $this->uptime_monitor->get_downtime_patterns($gateway_id, $days);
        
        wp_send_json_success(array(
            'stats' => $stats,
            'patterns' => $patterns
        ));
    }
    
    public function ajax_test_webhook() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $gateway_id = isset($_POST['gateway_id']) ? sanitize_text_field($_POST['gateway_id']) : null;
        
        if (!$gateway_id) {
            wp_send_json_error(array('message' => 'Gateway ID required'));
        }
        
        $result = $this->sandbox_tester->test_webhook($gateway_id);
        wp_send_json_success($result);
    }
    
    public function ajax_save_gateway_config() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $gateway_id = isset($_POST['gateway_id']) ? sanitize_text_field($_POST['gateway_id']) : null;
        $config = isset($_POST['config']) ? json_decode(stripslashes($_POST['config']), true) : array();
        
        if (!$gateway_id) {
            wp_send_json_error(array('message' => 'Gateway ID required'));
        }
        
        $gateway = $this->gateway_manager->get_gateway($gateway_id);
        if (!$gateway) {
            wp_send_json_error(array('message' => 'Gateway not found'));
        }
        
        // Save config first
        $result = $gateway->save_config($config);
        
        // Validate credentials by testing connection
        $test_result = $gateway->test_connection();
        
        if (!$test_result['success']) {
            // Extract specific error message
            $error_message = $test_result['message'] ?? 'Connection test failed';
            
            // Provide more specific error messages based on common issues
            $detailed_message = $this->get_detailed_error_message($gateway_id, $error_message, $config);
            
            wp_send_json_error(array(
                'message' => $detailed_message,
                'error_type' => 'validation_failed',
                'raw_error' => $error_message
            ));
        }
        
        // If configuration is successful, automatically enable the gateway
        $enabled_gateways = get_option('sapgs_enabled_gateways', array());
        if (!in_array($gateway_id, $enabled_gateways)) {
            $enabled_gateways[] = $gateway_id;
            $enabled_gateways = array_unique($enabled_gateways);
            update_option('sapgs_enabled_gateways', $enabled_gateways);
        }
        
        // Get current sort type and apply sorting
        $sort_type = get_option('sapgs_gateway_sort_type', 'smart_weighted');
        $sorted_order = $this->get_sorted_gateway_order($sort_type);
        
        // Update the saved order to include this gateway in the correct position
        if (!empty($sorted_order)) {
            update_option('sapgs_gateway_order', $sorted_order);
        }
        
        wp_send_json_success(array(
            'message' => 'Configuration saved and validated successfully! Gateway has been enabled.',
            'gateway_enabled' => true,
            'gateway_id' => $gateway_id
        ));
    }
    
    /**
     * Get detailed error message for gateway configuration
     */
    private function get_detailed_error_message($gateway_id, $error_message, $config) {
        $gateway_name = ucfirst(str_replace('_', ' ', $gateway_id));
        $lower_message = strtolower($error_message);
        
        // Check for common error patterns
        if (strpos($lower_message, 'unauthorized') !== false || 
            strpos($lower_message, '401') !== false ||
            strpos($lower_message, 'authentication') !== false ||
            strpos($lower_message, 'invalid') !== false) {
            return sprintf(
                'Invalid credentials for %s. Please check your API keys/credentials. Common issues:<br>• Wrong API key or secret key<br>• Keys from wrong environment (test vs live)<br>• Expired or revoked keys<br>• Extra spaces in the key fields<br><br>Please verify your credentials and try again.',
                $gateway_name
            );
        }
        
        if (strpos($lower_message, 'forbidden') !== false || 
            strpos($lower_message, '403') !== false) {
            return sprintf(
                'Access forbidden for %s. Your credentials may be valid but lack required permissions. Please check:<br>• Account permissions and access levels<br>• API key permissions<br>• Account status (active/suspended)<br><br>Contact %s support if the issue persists.',
                $gateway_name,
                $gateway_name
            );
        }
        
        if (strpos($lower_message, 'not found') !== false || 
            strpos($lower_message, '404') !== false) {
            return sprintf(
                'Configuration error for %s. The API endpoint or merchant ID may be incorrect. Please verify:<br>• Merchant ID/Account ID is correct<br>• Using the correct environment (test vs live)<br>• Account is properly set up<br><br>Double-check your configuration and try again.',
                $gateway_name
            );
        }
        
        if (strpos($lower_message, 'timeout') !== false || 
            strpos($lower_message, 'connection') !== false) {
            return sprintf(
                'Connection timeout for %s. This could indicate:<br>• Network connectivity issues<br>• Gateway server is temporarily unavailable<br>• Firewall blocking the connection<br><br>Please try again in a few moments. If the problem persists, check your network connection.',
                $gateway_name
            );
        }
        
        // Default detailed message
        return sprintf(
            'Configuration validation failed for %s.<br><br><strong>Error:</strong> %s<br><br>Please verify:<br>• All required fields are filled correctly<br>• API keys are valid and active<br>• You are using the correct environment (test/live)<br>• There are no extra spaces in your credentials<br><br>If you continue to have issues, contact %s support.',
            $gateway_name,
            esc_html($error_message),
            $gateway_name
        );
    }
    
    /**
     * Get sorted gateway order based on sort type
     */
    private function get_sorted_gateway_order($sort_type) {
        if ($sort_type === 'manual') {
            // Return saved manual order if exists
            return get_option('sapgs_gateway_order', array());
        }
        
        // Get sorting data
        $sorting_data = $this->metrics->get_sorting_data();
        $all_gateways = $this->gateway_manager->get_all_gateways();
        $enabled_gateways = $this->gateway_manager->get_enabled_gateways();
        
        // Separate enabled and disabled
        $enabled = array();
        $disabled = array();
        
        foreach ($all_gateways as $gateway_id => $gateway) {
            if (isset($enabled_gateways[$gateway_id])) {
                $enabled[$gateway_id] = $gateway;
            } else {
                $disabled[$gateway_id] = $gateway;
            }
        }
        
        // Sort enabled gateways
        uasort($enabled, function($a, $b) use ($sorting_data, $sort_type) {
            $aId = $a->get_id();
            $bId = $b->get_id();
            $aData = $sorting_data[$aId] ?? array();
            $bData = $sorting_data[$bId] ?? array();
            
            return $this->compare_gateways_for_sort($aData, $bData, $sort_type);
        });
        
        // Sort disabled gateways
        uasort($disabled, function($a, $b) use ($sorting_data, $sort_type) {
            $aId = $a->get_id();
            $bId = $b->get_id();
            $aData = $sorting_data[$aId] ?? array();
            $bData = $sorting_data[$bId] ?? array();
            
            return $this->compare_gateways_for_sort($aData, $bData, $sort_type);
        });
        
        // Combine: enabled first, then disabled
        return array_merge(array_keys($enabled), array_keys($disabled));
    }
    
    /**
     * Compare gateways for sorting
     */
    private function compare_gateways_for_sort($aData, $bData, $sort_type) {
        switch($sort_type) {
            case 'approval_rate':
                return ($bData['approval_rate'] ?? 0) - ($aData['approval_rate'] ?? 0);
            case 'success_rate':
                return ($bData['success_rate'] ?? 0) - ($aData['success_rate'] ?? 0);
            case 'lowest_fees':
                return ($aData['fee_adjusted_cost'] ?? 999999) - ($bData['fee_adjusted_cost'] ?? 999999);
            case 'fastest_response':
                return ($aData['response_time'] ?? 9999) - ($bData['response_time'] ?? 9999);
            case 'highest_uptime':
                return ($bData['uptime'] ?? 0) - ($aData['uptime'] ?? 0);
            case 'smart_weighted':
            default:
                return ($bData['smart_score'] ?? 0) - ($aData['smart_score'] ?? 0);
        }
    }
    
    public function ajax_get_gateway_config() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $gateway_id = isset($_POST['gateway_id']) ? sanitize_text_field($_POST['gateway_id']) : null;
        
        if (!$gateway_id) {
            wp_send_json_error(array('message' => 'Gateway ID required'));
        }
        
        $gateway = $this->gateway_manager->get_gateway($gateway_id);
        if (!$gateway) {
            wp_send_json_error(array('message' => 'Gateway not found'));
        }
        
        try {
            $config = $gateway->get_config();
            $fields = $gateway->get_config_fields();
            
            // Ensure fields is an array
            if (!is_array($fields)) {
                $fields = array();
            }
            
            // Ensure config is an array
            if (!is_array($config)) {
                $config = array();
            }
            
            wp_send_json_success(array(
                'config' => $config,
                'fields' => $fields,
                'gateway_id' => $gateway_id,
                'gateway_name' => $gateway->get_name()
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error loading configuration: ' . $e->getMessage()));
        }
    }
    
    public function ajax_get_sorting_data() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        if (!$this->license_manager->is_premium_active()) {
            wp_send_json_error(array('message' => 'Sorting is a premium feature'));
        }
        
        $sorting_data = $this->metrics->get_sorting_data();
        wp_send_json_success($sorting_data);
    }
    
    public function ajax_save_gateway_order() {
        check_ajax_referer('sapgs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        if (!$this->license_manager->is_premium_active()) {
            wp_send_json_error(array('message' => 'Sorting is a premium feature'));
        }
        
        $order = isset($_POST['order']) ? json_decode(stripslashes($_POST['order']), true) : array();
        $sort_type = isset($_POST['sort_type']) ? sanitize_text_field($_POST['sort_type']) : 'manual';
        
        if (!is_array($order)) {
            wp_send_json_error(array('message' => 'Invalid order data'));
        }
        
        // Save the order
        update_option('sapgs_gateway_order', $order);
        update_option('sapgs_gateway_sort_type', $sort_type);
        
        wp_send_json_success(array('message' => 'Gateway order saved'));
    }
}

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Transaction logs table
    $table_logs = $wpdb->prefix . 'sapgs_logs';
    $sql_logs = "CREATE TABLE $table_logs (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        gateway_id varchar(50) NOT NULL,
        transaction_id varchar(100),
        order_id bigint(20),
        amount decimal(10,2),
        currency varchar(3) DEFAULT 'ZAR',
        status varchar(20),
        response_time int(11),
        error_message text,
        request_data text,
        response_data text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY gateway_id (gateway_id),
        KEY order_id (order_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Test results table
    $table_tests = $wpdb->prefix . 'sapgs_tests';
    $sql_tests = "CREATE TABLE $table_tests (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        gateway_id varchar(50) NOT NULL,
        test_type varchar(50),
        success tinyint(1) DEFAULT 0,
        response_time int(11),
        error_message text,
        health_score int(3),
        test_data text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY gateway_id (gateway_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Uptime monitoring table
    $table_uptime = $wpdb->prefix . 'sapgs_uptime';
    $sql_uptime = "CREATE TABLE $table_uptime (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        gateway_id varchar(50) NOT NULL,
        is_up tinyint(1) DEFAULT 0,
        response_time int(11),
        checked_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY gateway_id (gateway_id),
        KEY checked_at (checked_at),
        KEY is_up (is_up)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_logs);
    dbDelta($sql_tests);
    dbDelta($sql_uptime);
    
    // Set default options
    add_option('sapgs_version', SAPGS_VERSION);
    add_option('sapgs_enabled_gateways', array());
    add_option('sapgs_default_gateway', '');
});

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, function() {
    // Clean up scheduled events if any
    wp_clear_scheduled_hook('sapgs_daily_tests');
});

// Initialize plugin
SA_Payment_Gateway_Switcher::get_instance();

