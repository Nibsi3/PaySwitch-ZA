<?php
/**
 * Fee Monitor
 * 
 * Monitors payment gateway fees and stores history
 * Checks fees daily to help users identify the best pricing
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_FeeMonitor {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sapgs_fees';
        
        // Schedule daily fee checks
        if (!wp_next_scheduled('sapgs_daily_fee_check')) {
            wp_schedule_event(time(), 'daily', 'sapgs_daily_fee_check');
        }
        add_action('sapgs_daily_fee_check', array($this, 'check_all_gateways_fees'));
        
        // Check fees on first activation if no data exists
        $this->maybe_initial_fee_check();
    }
    
    /**
     * Perform initial fee check if no data exists
     */
    private function maybe_initial_fee_check() {
        global $wpdb;
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        if ($count == 0) {
            // Run initial check in background (don't block page load)
            add_action('admin_init', array($this, 'check_all_gateways_fees'), 99);
        }
    }
    
    /**
     * Check fees for all enabled gateways
     */
    public function check_all_gateways_fees() {
        $license_manager = new SAPGS_LicenseManager();
        
        if (!$license_manager->is_premium_active()) {
            return;
        }
        
        $gateway_manager = new SAPGS_GatewayManager();
        $all_gateways = $gateway_manager->get_all_gateways();
        
        foreach ($all_gateways as $id => $gateway) {
            $this->check_gateway_fees($id);
        }
    }
    
    /**
     * Check fees for a single gateway
     */
    public function check_gateway_fees($gateway_id) {
        $gateway_manager = new SAPGS_GatewayManager();
        $gateway = $gateway_manager->get_gateway($gateway_id);
        
        if (!$gateway) {
            return false;
        }
        
        // Try to fetch current fees from gateway
        $current_fees = $this->fetch_gateway_fees($gateway);
        
        if ($current_fees) {
            $this->record_fees($gateway_id, $current_fees);
            return $current_fees;
        }
        
        // Fallback to hardcoded fees if fetch fails
        $fallback_fees = $gateway->get_fees();
        $this->record_fees($gateway_id, $fallback_fees);
        
        return $fallback_fees;
    }
    
    /**
     * Fetch current fees from gateway
     * Attempts to get fees via API or web scraping
     */
    private function fetch_gateway_fees($gateway) {
        // Check if gateway has a method to fetch current fees
        if (method_exists($gateway, 'fetch_current_fees')) {
            try {
                $fees = $gateway->fetch_current_fees();
                if ($fees && isset($fees['percentage']) && isset($fees['fixed'])) {
                    return $fees;
                }
            } catch (Exception $e) {
                // Log error but continue
                error_log('SAPGS FeeMonitor: Failed to fetch fees for ' . $gateway->get_id() . ': ' . $e->getMessage());
            }
        }
        
        // Try web scraping for known fee pages
        $fees = $this->scrape_gateway_fees($gateway);
        if ($fees) {
            return $fees;
        }
        
        return null;
    }
    
    /**
     * Scrape gateway fees from their websites
     */
    private function scrape_gateway_fees($gateway) {
        $gateway_id = $gateway->get_id();
        
        // Known fee URLs for each gateway
        $fee_urls = array(
            'payfast' => 'https://www.payfast.co.za/pricing/',
            'ozow' => 'https://ozow.com/pricing',
            'yoco' => 'https://www.yoco.com/za/pricing/',
            'peach_payments' => 'https://www.peachpayments.com/pricing',
            'paygate' => 'https://www.paygate.co.za/pricing/',
            'paystack_za' => 'https://paystack.com/pricing',
            'snapscan' => 'https://www.snapscan.co.za/pricing',
            'zapper' => 'https://www.zapper.com/pricing',
            'stitch' => 'https://www.stitch.money/pricing'
        );
        
        if (!isset($fee_urls[$gateway_id])) {
            return null;
        }
        
        // Attempt to fetch and parse the pricing page
        $response = wp_remote_get($fee_urls[$gateway_id], array(
            'timeout' => 15,
            'user-agent' => 'Mozilla/5.0 (compatible; PaySwitch ZA Fee Monitor)'
        ));
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return null;
        }
        
        // Parse fees based on gateway-specific patterns
        return $this->parse_fees_from_html($gateway_id, $body);
    }
    
    /**
     * Parse fees from HTML content
     */
    private function parse_fees_from_html($gateway_id, $html) {
        // This is a simplified parser - in production, you'd want more robust parsing
        // For now, we'll use regex patterns to find percentage and fixed fees
        
        $patterns = array(
            'payfast' => array(
                'percentage' => '/(?:2\.9|2\.90|2\.95|3\.0|3\.00)[%]?/',
                'fixed' => '/R?\s*([0-9]+\.[0-9]{2})/'
            ),
            'yoco' => array(
                'percentage' => '/(?:2\.95|2\.9|3\.0)[%]?/',
                'fixed' => '/R?\s*([0-9]+\.[0-9]{2})/'
            ),
            // Add more patterns as needed
        );
        
        if (!isset($patterns[$gateway_id])) {
            return null;
        }
        
        $fees = array('percentage' => null, 'fixed' => null);
        
        // Extract percentage
        if (preg_match($patterns[$gateway_id]['percentage'], $html, $matches)) {
            $fees['percentage'] = floatval($matches[0]);
        }
        
        // Extract fixed fee
        if (preg_match($patterns[$gateway_id]['fixed'], $html, $matches)) {
            $fees['fixed'] = floatval($matches[1]);
        }
        
        // Only return if we found both values
        if ($fees['percentage'] !== null && $fees['fixed'] !== null) {
            return $fees;
        }
        
        return null;
    }
    
    /**
     * Record fees in database
     */
    private function record_fees($gateway_id, $fees) {
        global $wpdb;
        
        // Check if we already have fees for today
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
            WHERE gateway_id = %s 
            AND DATE(checked_at) = CURDATE()",
            $gateway_id
        ));
        
        if ($existing) {
            // Update existing record
            $wpdb->update(
                $this->table_name,
                array(
                    'percentage_fee' => floatval($fees['percentage']),
                    'fixed_fee' => floatval($fees['fixed']),
                    'checked_at' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%f', '%f', '%s'),
                array('%d')
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $this->table_name,
                array(
                    'gateway_id' => sanitize_text_field($gateway_id),
                    'percentage_fee' => floatval($fees['percentage']),
                    'fixed_fee' => floatval($fees['fixed']),
                    'checked_at' => current_time('mysql')
                ),
                array('%s', '%f', '%f', '%s')
            );
        }
    }
    
    /**
     * Get latest fees for a gateway
     */
    public function get_latest_fees($gateway_id) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT percentage_fee, fixed_fee, checked_at 
            FROM {$this->table_name} 
            WHERE gateway_id = %s 
            ORDER BY checked_at DESC 
            LIMIT 1",
            $gateway_id
        ), ARRAY_A);
        
        if ($result) {
            return array(
                'percentage' => floatval($result['percentage_fee']),
                'fixed' => floatval($result['fixed_fee']),
                'checked_at' => $result['checked_at']
            );
        }
        
        return null;
    }
    
    /**
     * Get fee history for a gateway
     */
    public function get_fee_history($gateway_id, $days = 30) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(checked_at) as date,
                percentage_fee,
                fixed_fee,
                checked_at
            FROM {$this->table_name}
            WHERE gateway_id = %s 
            AND checked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            ORDER BY checked_at DESC",
            $gateway_id,
            $days
        ), ARRAY_A);
        
        return $results;
    }
    
    /**
     * Get all current fees for comparison
     */
    public function get_all_current_fees() {
        global $wpdb;
        
        // Get the latest fees for each gateway
        $results = $wpdb->get_results(
            "SELECT 
                f1.gateway_id,
                f1.percentage_fee,
                f1.fixed_fee,
                f1.checked_at
            FROM {$this->table_name} f1
            INNER JOIN (
                SELECT gateway_id, MAX(checked_at) as max_date
                FROM {$this->table_name}
                GROUP BY gateway_id
            ) f2 ON f1.gateway_id = f2.gateway_id AND f1.checked_at = f2.max_date
            ORDER BY (f1.percentage_fee * 100 + f1.fixed_fee) ASC",
            ARRAY_A
        );
        
        $fees = array();
        foreach ($results as $result) {
            $fees[$result['gateway_id']] = array(
                'percentage' => floatval($result['percentage_fee']),
                'fixed' => floatval($result['fixed_fee']),
                'checked_at' => $result['checked_at']
            );
        }
        
        return $fees;
    }
    
    /**
     * Get best fee gateway for a transaction amount
     */
    public function get_best_fee_gateway($amount = 100) {
        $all_fees = $this->get_all_current_fees();
        
        if (empty($all_fees)) {
            return null;
        }
        
        $best_gateway = null;
        $best_cost = null;
        
        foreach ($all_fees as $gateway_id => $fees) {
            $cost = ($amount * ($fees['percentage'] / 100)) + $fees['fixed'];
            
            if ($best_cost === null || $cost < $best_cost) {
                $best_cost = $cost;
                $best_gateway = array(
                    'gateway_id' => $gateway_id,
                    'cost' => $cost,
                    'fees' => $fees
                );
            }
        }
        
        return $best_gateway;
    }
    
    /**
     * Calculate fee cost for a transaction amount
     */
    public function calculate_fee_cost($gateway_id, $amount) {
        $fees = $this->get_latest_fees($gateway_id);
        
        if (!$fees) {
            // Fallback to gateway's get_fees() method
            $gateway_manager = new SAPGS_GatewayManager();
            $gateway = $gateway_manager->get_gateway($gateway_id);
            if ($gateway) {
                $fees = $gateway->get_fees();
            } else {
                return null;
            }
        }
        
        return ($amount * ($fees['percentage'] / 100)) + $fees['fixed'];
    }
}

