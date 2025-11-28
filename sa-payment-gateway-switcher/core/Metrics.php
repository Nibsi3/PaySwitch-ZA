<?php
/**
 * Metrics
 * 
 * Handles analytics and performance metrics
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_Metrics {
    
    private $logger;
    
    public function __construct() {
        $this->logger = new SAPGS_Logger();
    }
    
    /**
     * Get analytics data
     */
    public function get_analytics_data($days = 7) {
        $license_manager = new SAPGS_LicenseManager();
        
        if (!$license_manager->is_premium_active()) {
            return array(
                'error' => 'Analytics is a premium feature'
            );
        }
        
        $gateway_manager = new SAPGS_GatewayManager();
        $all_gateways = $gateway_manager->get_all_gateways();
        
        $data = array(
            'gateways' => array(),
            'comparison' => array(
                'fees' => array(),
                'success_rates' => array(),
                'avg_response_times' => array(),
                'total_transactions' => array(),
                'total_amounts' => array()
            ),
            'timeline' => array()
        );
        
        // Get data for each gateway
        foreach ($all_gateways as $id => $gateway) {
            $stats = $this->logger->get_stats($id, $days);
            $fees = $gateway->get_fees();
            
            $data['gateways'][$id] = array(
                'name' => $gateway->get_name(),
                'stats' => $stats,
                'fees' => $fees,
                'status' => $gateway_manager->get_gateway_status($id)
            );
            
            $data['comparison']['fees'][$id] = $fees;
            $data['comparison']['success_rates'][$id] = $stats['success_rate'];
            $data['comparison']['avg_response_times'][$id] = $stats['avg_response_time'];
            $data['comparison']['total_transactions'][$id] = $stats['total'];
            $data['comparison']['total_amounts'][$id] = $stats['total_amount'];
        }
        
        // Get timeline data (daily breakdown)
        $data['timeline'] = $this->get_timeline_data($days);
        
        return $data;
    }
    
    /**
     * Get timeline data
     */
    private function get_timeline_data($days) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sapgs_logs';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                gateway_id,
                COUNT(*) as count,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
                AVG(response_time) as avg_response_time
            FROM {$table_name}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(created_at), gateway_id
            ORDER BY date ASC",
            $days
        ), ARRAY_A);
        
        $timeline = array();
        foreach ($results as $row) {
            if (!isset($timeline[$row['date']])) {
                $timeline[$row['date']] = array();
            }
            $timeline[$row['date']][$row['gateway_id']] = array(
                'count' => intval($row['count']),
                'success_count' => intval($row['success_count']),
                'avg_response_time' => floatval($row['avg_response_time'])
            );
        }
        
        return $timeline;
    }
    
    /**
     * Get gateway performance score
     */
    public function get_performance_score($gateway_id) {
        $stats = $this->logger->get_stats($gateway_id, 30);
        
        $score = 0;
        
        // Success rate (40 points)
        $score += ($stats['success_rate'] / 100) * 40;
        
        // Response time (30 points) - faster is better
        if ($stats['avg_response_time'] > 0) {
            $max_time = 5000; // 5 seconds
            $time_score = max(0, 1 - ($stats['avg_response_time'] / $max_time));
            $score += $time_score * 30;
        }
        
        // Transaction volume (30 points) - more is better
        $volume_score = min(1, $stats['total'] / 100); // 100 transactions = full score
        $score += $volume_score * 30;
        
        return round($score, 1);
    }
}

