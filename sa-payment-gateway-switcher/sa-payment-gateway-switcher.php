<?php
/**
 * Plugin Name: SA Payment Gateway Switcher
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
        add_action('wp_ajax_sapgs_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_sapgs_get_logs', array($this, 'ajax_get_logs'));
    }
    
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('SA Payment Gateway Switcher requires WooCommerce to be installed and active.', 'sapgs');
                echo '</p></div>';
            });
            return;
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('SA Payment Gateways', 'sapgs'),
            __('SA Gateways', 'sapgs'),
            'manage_options',
            'sapgs-dashboard',
            array('SAPGS_Dashboard', 'render'),
            'dashicons-money-alt',
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
            SAPGS_VERSION
        );
        
        wp_enqueue_script(
            'sapgs-admin',
            SAPGS_PLUGIN_URL . 'admin/assets/admin.js',
            array('jquery', 'wp-util'),
            SAPGS_VERSION,
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
        
        // Free users can only enable one gateway
        if ($enabled && !$this->license_manager->is_premium_active()) {
            $enabled_gateways = array(); // Disable all others
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
            'is_premium' => $this->license_manager->is_premium_active()
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
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        
        $logs = $this->logger->get_logs($gateway_id, $limit);
        
        wp_send_json_success($logs);
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
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_logs);
    dbDelta($sql_tests);
    
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

