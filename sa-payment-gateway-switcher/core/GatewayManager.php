<?php
/**
 * Gateway Manager
 * 
 * Manages all payment gateways
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_GatewayManager {
    
    private $gateways = array();
    private $default_gateway = null;
    
    public function __construct() {
        $this->register_gateways();
        $this->load_default_gateway();
    }
    
    /**
     * Register all available gateways
     */
    private function register_gateways() {
        $this->gateways = array(
            'payfast' => new SAPGS_PayfastGateway(),
            'ozow' => new SAPGS_OzowGateway(),
            'yoco' => new SAPGS_YocoGateway(),
            'peach_payments' => new SAPGS_PeachPaymentsGateway(),
            'paygate' => new SAPGS_PayGateGateway(),
            'paystack_za' => new SAPGS_PaystackZAGateway(),
            'snapscan' => new SAPGS_SnapScanGateway(),
            'zapper' => new SAPGS_ZapperGateway(),
            'stitch' => new SAPGS_StitchGateway()
        );
    }
    
    /**
     * Get all gateways
     */
    public function get_all_gateways() {
        return $this->gateways;
    }
    
    /**
     * Get a specific gateway
     */
    public function get_gateway($gateway_id) {
        return isset($this->gateways[$gateway_id]) ? $this->gateways[$gateway_id] : null;
    }
    
    /**
     * Get enabled gateways
     */
    public function get_enabled_gateways() {
        $enabled_ids = get_option('sapgs_enabled_gateways', array());
        $enabled = array();
        
        foreach ($enabled_ids as $id) {
            if (isset($this->gateways[$id])) {
                $enabled[$id] = $this->gateways[$id];
            }
        }
        
        return $enabled;
    }
    
    /**
     * Load default gateway
     */
    private function load_default_gateway() {
        $default_id = get_option('sapgs_default_gateway', '');
        if ($default_id && isset($this->gateways[$default_id])) {
            $this->default_gateway = $this->gateways[$default_id];
        }
    }
    
    /**
     * Get default gateway
     */
    public function get_default_gateway() {
        return $this->default_gateway;
    }
    
    /**
     * Process payment with failover
     */
    public function process_payment($amount, $data = array()) {
        $license_manager = new SAPGS_LicenseManager();
        $failover_enabled = get_option('sapgs_failover_enabled', false) && $license_manager->is_premium_active();
        
        $gateways_to_try = array();
        
        if ($this->default_gateway) {
            $gateways_to_try[] = $this->default_gateway;
        }
        
        if ($failover_enabled) {
            $enabled = $this->get_enabled_gateways();
            foreach ($enabled as $gateway) {
                if ($gateway !== $this->default_gateway) {
                    $gateways_to_try[] = $gateway;
                }
            }
        }
        
        $last_error = null;
        
        foreach ($gateways_to_try as $gateway) {
            try {
                $result = $gateway->charge($amount, $data);
                if ($result['success']) {
                    return $result;
                }
                $last_error = $result;
            } catch (Exception $e) {
                $last_error = array(
                    'success' => false,
                    'message' => $e->getMessage()
                );
            }
        }
        
        return $last_error ?: array(
            'success' => false,
            'message' => 'No payment gateways available'
        );
    }
    
    /**
     * Get gateway status
     */
    public function get_gateway_status($gateway_id) {
        $gateway = $this->get_gateway($gateway_id);
        if (!$gateway) {
            return 'not_found';
        }
        
        if (!$gateway->is_configured()) {
            return 'not_configured';
        }
        
        $test_result = $gateway->test_connection();
        
        if ($test_result['success']) {
            $health = $test_result['health_score'] ?? 0;
            if ($health >= 80) {
                return 'connected';
            } elseif ($health >= 50) {
                return 'intermittent';
            } else {
                return 'offline';
            }
        }
        
        return 'offline';
    }
    
    /**
     * Get all gateway statuses
     */
    public function get_all_statuses() {
        $statuses = array();
        foreach ($this->gateways as $id => $gateway) {
            $statuses[$id] = $this->get_gateway_status($id);
        }
        return $statuses;
    }
}

