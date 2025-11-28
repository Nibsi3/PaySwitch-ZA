<?php
/**
 * Uptime Monitor
 * 
 * Monitors API uptime and stores history
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_UptimeMonitor {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sapgs_uptime';
        
        // Schedule hourly checks
        if (!wp_next_scheduled('sapgs_hourly_uptime_check')) {
            wp_schedule_event(time(), 'hourly', 'sapgs_hourly_uptime_check');
        }
        add_action('sapgs_hourly_uptime_check', array($this, 'check_all_gateways'));
    }
    
    /**
     * Check all enabled gateways
     */
    public function check_all_gateways() {
        $license_manager = new SAPGS_LicenseManager();
        
        if (!$license_manager->is_premium_active()) {
            return;
        }
        
        $gateway_manager = new SAPGS_GatewayManager();
        $enabled_gateways = $gateway_manager->get_enabled_gateways();
        
        foreach ($enabled_gateways as $id => $gateway) {
            if ($gateway->is_configured()) {
                $this->check_gateway($id);
            }
        }
    }
    
    /**
     * Check a single gateway
     */
    public function check_gateway($gateway_id) {
        $gateway_manager = new SAPGS_GatewayManager();
        $gateway = $gateway_manager->get_gateway($gateway_id);
        
        if (!$gateway || !$gateway->is_configured()) {
            return false;
        }
        
        $start_time = microtime(true);
        $test_result = $gateway->test_connection();
        $response_time = round((microtime(true) - $start_time) * 1000);
        
        $is_up = $test_result['success'] ?? false;
        
        $this->record_uptime($gateway_id, $is_up, $response_time);
        
        return $is_up;
    }
    
    /**
     * Record uptime check
     */
    private function record_uptime($gateway_id, $is_up, $response_time) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'gateway_id' => sanitize_text_field($gateway_id),
                'is_up' => $is_up ? 1 : 0,
                'response_time' => intval($response_time),
                'checked_at' => current_time('mysql')
            ),
            array('%s', '%d', '%d', '%s')
        );
    }
    
    /**
     * Get uptime statistics
     */
    public function get_uptime_stats($gateway_id, $days = 7) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(checked_at) as date,
                HOUR(checked_at) as hour,
                AVG(is_up) as uptime_percentage,
                AVG(response_time) as avg_response_time,
                COUNT(*) as checks
            FROM {$this->table_name}
            WHERE gateway_id = %s 
            AND checked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(checked_at), HOUR(checked_at)
            ORDER BY date DESC, hour DESC",
            $gateway_id,
            $days
        ), ARRAY_A);
        
        // Calculate overall uptime
        $total_checks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE gateway_id = %s 
            AND checked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $gateway_id,
            $days
        ));
        
        $up_checks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE gateway_id = %s 
            AND is_up = 1
            AND checked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $gateway_id,
            $days
        ));
        
        $overall_uptime = $total_checks > 0 ? ($up_checks / $total_checks) * 100 : 0;
        
        return array(
            'overall_uptime' => round($overall_uptime, 2),
            'total_checks' => intval($total_checks),
            'up_checks' => intval($up_checks),
            'downtime_percentage' => round(100 - $overall_uptime, 2),
            'hourly_data' => $results
        );
    }
    
    /**
     * Get downtime patterns
     */
    public function get_downtime_patterns($gateway_id, $days = 30) {
        global $wpdb;
        
        $downtimes = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                checked_at,
                response_time
            FROM {$this->table_name}
            WHERE gateway_id = %s 
            AND is_up = 0
            AND checked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            ORDER BY checked_at DESC",
            $gateway_id,
            $days
        ), ARRAY_A);
        
        // Group by day of week and hour
        $patterns = array();
        foreach ($downtimes as $downtime) {
            $timestamp = strtotime($downtime['checked_at']);
            $day = date('w', $timestamp); // 0 = Sunday
            $hour = date('G', $timestamp);
            
            if (!isset($patterns[$day])) {
                $patterns[$day] = array();
            }
            if (!isset($patterns[$day][$hour])) {
                $patterns[$day][$hour] = 0;
            }
            $patterns[$day][$hour]++;
        }
        
        return $patterns;
    }
}

