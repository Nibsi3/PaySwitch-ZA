<?php
/**
 * Gateway Ranking
 * 
 * Provides ranking UI for all gateways
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_GatewayRanking {
    
    private $logger;
    private $metrics;
    private $uptime_monitor;
    private $fee_monitor;
    
    public function __construct() {
        $this->logger = new SAPGS_Logger();
        $this->metrics = new SAPGS_Metrics();
        $this->uptime_monitor = new SAPGS_UptimeMonitor();
        $this->fee_monitor = new SAPGS_FeeMonitor();
    }
    
    /**
     * Get ranking data for all gateways
     */
    public function get_rankings($use_live_data = false) {
        $gateway_manager = new SAPGS_GatewayManager();
        $all_gateways = $gateway_manager->get_all_gateways();
        
        $rankings = array();
        
        foreach ($all_gateways as $gateway_id => $gateway) {
            if ($use_live_data) {
                // Use real store data (Premium)
                $stats = $this->logger->get_stats($gateway_id, 30);
                $uptime_stats = $this->uptime_monitor->get_uptime_stats($gateway_id, 30);
                $fees = $this->fee_monitor->get_latest_fees($gateway_id);
                
                $rankings[$gateway_id] = array(
                    'name' => $gateway->get_name(),
                    'success_rate' => $stats['success_rate'] ?? 0,
                    'response_time' => $stats['avg_response_time'] ?? 0,
                    'uptime' => $uptime_stats['overall_uptime'] ?? 0,
                    'fee_percentage' => $fees['percentage'] ?? 0,
                    'fee_fixed' => $fees['fixed'] ?? 0,
                    'weighted_score' => $this->calculate_weighted_score($stats, $uptime_stats, $fees)
                );
            } else {
                // Use benchmark data (Free)
                $benchmark = SAPGS_BenchmarkData::get_gateway_benchmark($gateway_id);
                
                $rankings[$gateway_id] = array(
                    'name' => $gateway->get_name(),
                    'success_rate' => $benchmark['success_rate']['rate'] ?? 0,
                    'response_time' => $benchmark['response_time']['avg'] ?? 0,
                    'uptime' => 95, // Estimated from benchmark
                    'fee_percentage' => $this->extract_fee_percentage($benchmark['fees']['percentage'] ?? '0%'),
                    'fee_fixed' => 0,
                    'weighted_score' => $this->calculate_benchmark_score($benchmark)
                );
            }
        }
        
        // Sort by weighted score
        uasort($rankings, function($a, $b) {
            return $b['weighted_score'] - $a['weighted_score'];
        });
        
        // Add rank numbers
        $rank = 1;
        foreach ($rankings as &$ranking) {
            $ranking['rank'] = $rank++;
        }
        
        return $rankings;
    }
    
    /**
     * Calculate weighted score from live data
     */
    private function calculate_weighted_score($stats, $uptime_stats, $fees) {
        $success_weight = 0.35;
        $uptime_weight = 0.30;
        $speed_weight = 0.20;
        $fee_weight = 0.15;
        
        $success_score = ($stats['success_rate'] ?? 0) * $success_weight;
        $uptime_score = ($uptime_stats['overall_uptime'] ?? 0) * $uptime_weight;
        
        // Speed score (inverse - faster is better, max 1000ms = 0, 0ms = 100)
        $response_time = $stats['avg_response_time'] ?? 1000;
        $speed_score = max(0, (1000 - $response_time) / 10) * $speed_weight;
        
        // Fee score (lower is better, assuming max 5% = 0, 0% = 100)
        $fee_percentage = $fees['percentage'] ?? 5;
        $fee_score = max(0, (5 - $fee_percentage) * 20) * $fee_weight;
        
        return round($success_score + $uptime_score + $speed_score + $fee_score, 2);
    }
    
    /**
     * Calculate score from benchmark data
     */
    private function calculate_benchmark_score($benchmark) {
        $success_rate = $benchmark['success_rate']['rate'] ?? 0;
        $response_time = $benchmark['response_time']['avg'] ?? 1000;
        $fee_percentage = $this->extract_fee_percentage($benchmark['fees']['percentage'] ?? '0%');
        
        $success_score = $success_rate * 0.35;
        $uptime_score = 95 * 0.30; // Estimated
        $speed_score = max(0, (1000 - $response_time) / 10) * 0.20;
        $fee_score = max(0, (5 - $fee_percentage) * 20) * 0.15;
        
        return round($success_score + $uptime_score + $speed_score + $fee_score, 2);
    }
    
    /**
     * Extract fee percentage from string like "2.5-3.0%"
     */
    private function extract_fee_percentage($fee_string) {
        if (preg_match('/(\d+\.?\d*)/', $fee_string, $matches)) {
            return floatval($matches[1]);
        }
        return 0;
    }
}

