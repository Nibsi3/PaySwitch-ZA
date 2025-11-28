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
     * Process payment with routing modes
     */
    public function process_payment($amount, $data = array()) {
        $license_manager = new SAPGS_LicenseManager();
        $routing_mode = get_option('sapgs_routing_mode', 'default');
        $failover_enabled = get_option('sapgs_failover_enabled', false) && $license_manager->is_premium_active();
        
        $gateways_to_try = array();
        
        // Determine gateway order based on routing mode
        switch ($routing_mode) {
            case 'approval_rate':
                $gateways_to_try = $this->get_gateways_by_approval_rate();
                break;
                
            case 'load_balance':
                $gateways_to_try = $this->get_gateways_for_load_balance();
                break;
                
            case 'failover':
            default:
                // Default: use default gateway first, then failover
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
                break;
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
     * Get gateways sorted by approval rate (highest first)
     */
    private function get_gateways_by_approval_rate() {
        $license_manager = new SAPGS_LicenseManager();
        if (!$license_manager->is_premium_active()) {
            return $this->default_gateway ? array($this->default_gateway) : array();
        }
        
        $enabled = $this->get_enabled_gateways();
        $logger = new SAPGS_Logger();
        
        $gateways_with_rates = array();
        foreach ($enabled as $id => $gateway) {
            $stats = $logger->get_stats($id, 30);
            $gateways_with_rates[$id] = array(
                'gateway' => $gateway,
                'approval_rate' => $stats['success_rate'],
                'total' => $stats['total']
            );
        }
        
        // Sort by approval rate (descending), but only if we have enough data
        uasort($gateways_with_rates, function($a, $b) {
            // If one has no data, prefer the one with data
            if ($a['total'] < 10 && $b['total'] >= 10) return 1;
            if ($b['total'] < 10 && $a['total'] >= 10) return -1;
            
            // If both have data, sort by approval rate
            return $b['approval_rate'] <=> $a['approval_rate'];
        });
        
        $sorted = array();
        foreach ($gateways_with_rates as $item) {
            $sorted[] = $item['gateway'];
        }
        
        return $sorted;
    }
    
    /**
     * Get gateways for load balancing
     */
    private function get_gateways_for_load_balance() {
        $license_manager = new SAPGS_LicenseManager();
        if (!$license_manager->is_premium_active()) {
            return $this->default_gateway ? array($this->default_gateway) : array();
        }
        
        $enabled = $this->get_enabled_gateways();
        $gateways = array_values($enabled);
        
        // Simple round-robin: get last used gateway from option
        $last_used_index = get_option('sapgs_last_load_balance_index', 0);
        $next_index = ($last_used_index + 1) % count($gateways);
        update_option('sapgs_last_load_balance_index', $next_index);
        
        // Return gateways starting from next index
        $rotated = array_merge(
            array_slice($gateways, $next_index),
            array_slice($gateways, 0, $next_index)
        );
        
        return $rotated;
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

