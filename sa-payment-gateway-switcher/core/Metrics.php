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
        
        $uptime_monitor = new SAPGS_UptimeMonitor();
        
        $data = array(
            'gateways' => array(),
            'comparison' => array(
                'fees' => array(),
                'success_rates' => array(),
                'avg_response_times' => array(),
                'total_transactions' => array(),
                'total_amounts' => array(),
                'approval_rates' => array(),
                'downtime_percentages' => array()
            ),
            'timeline' => array(),
            'downtime_patterns' => array(),
            'seven_day_performance' => array()
        );
        
        // Get fee monitor for latest fees
        $fee_monitor = new SAPGS_FeeMonitor();
        
        // Get data for each gateway
        foreach ($all_gateways as $id => $gateway) {
            $stats = $this->logger->get_stats($id, $days);
            
            // Try to get latest fees from FeeMonitor, fallback to gateway's get_fees()
            $latest_fees = $fee_monitor->get_latest_fees($id);
            $fees = $latest_fees ? array(
                'percentage' => $latest_fees['percentage'],
                'fixed' => $latest_fees['fixed'],
                'checked_at' => $latest_fees['checked_at']
            ) : $gateway->get_fees();
            
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
            
            // Approval rate (same as success rate for now, but can be different)
            $data['comparison']['approval_rates'][$id] = $stats['success_rate'];
            
            // Get uptime stats
            $uptime_stats = $uptime_monitor->get_uptime_stats($id, $days);
            $data['comparison']['downtime_percentages'][$id] = $uptime_stats['downtime_percentage'];
            
            // Get downtime patterns
            $data['downtime_patterns'][$id] = $uptime_monitor->get_downtime_patterns($id, $days);
        }
        
        // Get timeline data (daily breakdown)
        $data['timeline'] = $this->get_timeline_data($days);
        
        // Get 7-day performance data
        $data['seven_day_performance'] = $this->get_seven_day_performance();
        
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
    
    /**
     * Get 7-day performance data
     */
    private function get_seven_day_performance() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sapgs_logs';
        
        $results = $wpdb->get_results(
            "SELECT 
                DATE(created_at) as date,
                gateway_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                AVG(response_time) as avg_response_time,
                SUM(amount) as total_amount
            FROM {$table_name}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at), gateway_id
            ORDER BY date ASC, gateway_id ASC",
            ARRAY_A
        );
        
        $performance = array();
        foreach ($results as $row) {
            if (!isset($performance[$row['gateway_id']])) {
                $performance[$row['gateway_id']] = array();
            }
            
            $performance[$row['gateway_id']][] = array(
                'date' => $row['date'],
                'total' => intval($row['total']),
                'success' => intval($row['success']),
                'success_rate' => $row['total'] > 0 ? ($row['success'] / $row['total']) * 100 : 0,
                'avg_response_time' => floatval($row['avg_response_time']),
                'total_amount' => floatval($row['total_amount'])
            );
        }
        
        return $performance;
    }
    
    /**
     * Get sorting data for all gateways
     */
    public function get_sorting_data() {
        $gateway_manager = new SAPGS_GatewayManager();
        $all_gateways = $gateway_manager->get_all_gateways();
        $uptime_monitor = new SAPGS_UptimeMonitor();
        
        $sorting_data = array();
        
        // Get fee monitor for latest fees
        $fee_monitor = new SAPGS_FeeMonitor();
        
        foreach ($all_gateways as $id => $gateway) {
            $stats = $this->logger->get_stats($id, 30);
            
            // Try to get latest fees from FeeMonitor, fallback to gateway's get_fees()
            $latest_fees = $fee_monitor->get_latest_fees($id);
            $fees = $latest_fees ? array(
                'percentage' => $latest_fees['percentage'],
                'fixed' => $latest_fees['fixed']
            ) : $gateway->get_fees();
            
            $uptime_stats = $uptime_monitor->get_uptime_stats($id, 30);
            
            // Calculate fee-adjusted cost per approval
            // For a R100 transaction: cost = (100 * percentage/100) + fixed
            // Cost per approval = cost / success_rate (if success_rate > 0)
            $test_amount = 100;
            $fee_cost = ($test_amount * ($fees['percentage'] / 100)) + $fees['fixed'];
            $fee_adjusted_cost = $stats['success_rate'] > 0 ? ($fee_cost / ($stats['success_rate'] / 100)) : 999999;
            
            // Calculate smart weighted score
            $smart_score = $this->calculate_smart_weighted_score($stats, $fees, $uptime_stats);
            
            $sorting_data[$id] = array(
                'approval_rate' => $stats['success_rate'],
                'success_rate' => $stats['success_rate'],
                'fee_cost' => $fee_cost,
                'fee_adjusted_cost' => $fee_adjusted_cost,
                'response_time' => $stats['avg_response_time'] ?: 9999,
                'uptime' => 100 - ($uptime_stats['downtime_percentage'] ?? 0),
                'smart_score' => $smart_score
            );
        }
        
        return $sorting_data;
    }
    
    /**
     * Calculate smart weighted score
     * Combines multiple factors: approval rate, response time, fees, uptime
     */
    private function calculate_smart_weighted_score($stats, $fees, $uptime_stats) {
        $score = 0;
        
        // Approval rate (40% weight) - 0-100 scale
        $approval_score = $stats['success_rate'];
        $score += $approval_score * 0.4;
        
        // Response time (20% weight) - faster is better, normalized to 0-100
        $max_response_time = 5000; // 5 seconds
        $response_time = $stats['avg_response_time'] ?: $max_response_time;
        $response_score = max(0, 100 - (($response_time / $max_response_time) * 100));
        $score += $response_score * 0.2;
        
        // Uptime (20% weight) - 0-100 scale
        $uptime = 100 - ($uptime_stats['downtime_percentage'] ?? 0);
        $score += $uptime * 0.2;
        
        // Fee efficiency (20% weight) - lower fees are better, normalized to 0-100
        // Compare against max fee (assuming 5% is max)
        $max_fee_percentage = 5.0;
        $fee_efficiency = max(0, 100 - (($fees['percentage'] / $max_fee_percentage) * 100));
        $score += $fee_efficiency * 0.2;
        
        return round($score, 2);
    }
}

