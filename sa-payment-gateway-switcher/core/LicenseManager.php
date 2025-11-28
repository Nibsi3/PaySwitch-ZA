<?php
/**
 * License Manager
 * 
 * Handles premium license activation and validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_LicenseManager {
    
    private $license_key_option = 'sapgs_license_key';
    private $license_status_option = 'sapgs_license_status';
    private $license_expires_option = 'sapgs_license_expires';
    private $license_type_option = 'sapgs_license_type';
    
    public function __construct() {
        // Schedule daily license check
        if (!wp_next_scheduled('sapgs_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'sapgs_daily_license_check');
        }
        add_action('sapgs_daily_license_check', array($this, 'validate_license'));
    }
    
    /**
     * Activate license
     */
    public function activate_license($license_key) {
        $license_key = sanitize_text_field($license_key);
        
        if (empty($license_key)) {
            return array(
                'success' => false,
                'message' => 'License key is required'
            );
        }
        
        // Call license server
        $response = $this->call_license_server('activate', array(
            'license_key' => $license_key,
            'site_url' => home_url(),
            'plugin_version' => SAPGS_VERSION
        ));
        
        if ($response && isset($response['success']) && $response['success']) {
            update_option($this->license_key_option, $license_key);
            update_option($this->license_status_option, 'active');
            update_option($this->license_expires_option, $response['expires'] ?? null);
            update_option($this->license_type_option, $response['type'] ?? 'monthly');
            
            return array(
                'success' => true,
                'message' => 'License activated successfully',
                'type' => $response['type'] ?? 'monthly',
                'expires' => $response['expires'] ?? null
            );
        }
        
        return array(
            'success' => false,
            'message' => $response['message'] ?? 'Failed to activate license. Please check your license key.'
        );
    }
    
    /**
     * Deactivate license
     */
    public function deactivate_license() {
        $license_key = get_option($this->license_key_option);
        
        if ($license_key) {
            $this->call_license_server('deactivate', array(
                'license_key' => $license_key,
                'site_url' => home_url()
            ));
        }
        
        delete_option($this->license_key_option);
        delete_option($this->license_status_option);
        delete_option($this->license_expires_option);
        delete_option($this->license_type_option);
    }
    
    /**
     * Validate license
     */
    public function validate_license() {
        $license_key = get_option($this->license_key_option);
        $status = get_option($this->license_status_option);
        
        if (!$license_key || $status !== 'active') {
            return false;
        }
        
        $response = $this->call_license_server('validate', array(
            'license_key' => $license_key,
            'site_url' => home_url()
        ));
        
        if ($response && isset($response['success']) && $response['success']) {
            update_option($this->license_status_option, 'active');
            update_option($this->license_expires_option, $response['expires'] ?? null);
            return true;
        } else {
            // License invalid or expired
            update_option($this->license_status_option, 'inactive');
            return false;
        }
    }
    
    /**
     * Check if premium is active
     */
    public function is_premium_active() {
        $license_key = get_option($this->license_key_option);
        $status = get_option($this->license_status_option);
        $expires = get_option($this->license_expires_option);
        
        // Allow test license for premium features
        if ($license_key === 'TEST-PREMIUM-LICENSE-2024') {
            return true;
        }
        
        if ($status !== 'active') {
            return false;
        }
        
        // Check expiration for monthly licenses
        if ($expires && strtotime($expires) < time()) {
            // Check if in grace period (7 days)
            $grace_period = strtotime($expires . ' +7 days');
            if ($grace_period < time()) {
                update_option($this->license_status_option, 'expired');
                return false;
            }
            // In grace period
            return true;
        }
        
        return true;
    }
    
    /**
     * Get license info
     */
    public function get_license_info() {
        return array(
            'key' => get_option($this->license_key_option),
            'status' => get_option($this->license_status_option, 'inactive'),
            'expires' => get_option($this->license_expires_option),
            'type' => get_option($this->license_type_option, 'free'),
            'is_premium' => $this->is_premium_active()
        );
    }
    
    /**
     * Call license server API
     */
    private function call_license_server($action, $data) {
        $url = SAPGS_LICENSE_SERVER . '/' . $action;
        
        $args = array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15,
            'sslverify' => true
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            // For development/testing, return mock success
            // Also allow test license key for premium features testing
            if (defined('WP_DEBUG') && WP_DEBUG || $data['license_key'] === 'TEST-PREMIUM-LICENSE-2024') {
                return array(
                    'success' => true,
                    'type' => 'lifetime',
                    'expires' => null,
                    'message' => 'Test Premium License (Development Mode) - All premium features enabled'
                );
            }
            
            return array(
                'success' => false,
                'message' => 'Could not connect to license server'
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        return $result ?: array('success' => false, 'message' => 'Invalid response from license server');
    }
}

