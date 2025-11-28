<?php
/**
 * Optimization Engine
 * 
 * Provides automatic transaction optimization suggestions
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_OptimizationEngine {
    
    private $logger;
    private $metrics;
    private $gateway_manager;
    
    public function __construct() {
        $this->logger = new SAPGS_Logger();
        $this->metrics = new SAPGS_Metrics();
        $this->gateway_manager = new SAPGS_GatewayManager();
    }
    
    /**
     * Get optimization suggestions
     */
    public function get_suggestions() {
        $license_manager = new SAPGS_LicenseManager();
        
        if (!$license_manager->is_premium_active()) {
            return array(
                'error' => 'Optimization suggestions are a premium feature'
            );
        }
        
        $suggestions = array();
        
        // Suggest default gateway based on performance
        $default_suggestion = $this->suggest_default_gateway();
        if ($default_suggestion) {
            $suggestions['default_gateway'] = $default_suggestion;
        }
        
        // Suggest fee optimization
        $fee_suggestion = $this->suggest_fee_optimization();
        if ($fee_suggestion) {
            $suggestions['fee_optimization'] = $fee_suggestion;
        }
        
        // Suggest based on success rate
        $success_suggestion = $this->suggest_by_success_rate();
        if ($success_suggestion) {
            $suggestions['success_rate'] = $success_suggestion;
        }
        
        // Suggest routing mode
        $routing_suggestion = $this->suggest_routing_mode();
        if ($routing_suggestion) {
            $suggestions['routing_mode'] = $routing_suggestion;
        }
        
        return $suggestions;
    }
    
    /**
     * Suggest best default gateway
     */
    private function suggest_default_gateway() {
        $all_gateways = $this->gateway_manager->get_all_gateways();
        $enabled_gateways = $this->gateway_manager->get_enabled_gateways();
        $current_default = $this->gateway_manager->get_default_gateway();
        
        if (empty($enabled_gateways)) {
            return null;
        }
        
        $best_gateway = null;
        $best_score = 0;
        
        foreach ($enabled_gateways as $id => $gateway) {
            $score = $this->metrics->get_performance_score($id);
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_gateway = $id;
            }
        }
        
        if ($best_gateway && (!$current_default || $current_default->get_id() !== $best_gateway)) {
            return array(
                'action' => 'switch_default',
                'gateway_id' => $best_gateway,
                'gateway_name' => $all_gateways[$best_gateway]->get_name(),
                'reason' => sprintf('Higher performance score (%d vs current)', round($best_score)),
                'score' => round($best_score, 1),
                'priority' => 'high'
            );
        }
        
        return null;
    }
    
    /**
     * Suggest fee optimization
     */
    private function suggest_fee_optimization() {
        $all_gateways = $this->gateway_manager->get_all_gateways();
        $enabled_gateways = $this->gateway_manager->get_enabled_gateways();
        
        if (count($enabled_gateways) < 2) {
            return null;
        }
        
        $fee_comparison = array();
        
        foreach ($enabled_gateways as $id => $gateway) {
            $fees = $gateway->get_fees();
            $stats = $this->logger->get_stats($id, 30);
            
            // Calculate estimated monthly cost for R100,000 in transactions
            $monthly_volume = 100000;
            $estimated_cost = ($monthly_volume * ($fees['percentage'] / 100)) + ($stats['total'] * $fees['fixed']);
            
            $fee_comparison[$id] = array(
                'gateway' => $gateway->get_name(),
                'percentage' => $fees['percentage'],
                'fixed' => $fees['fixed'],
                'estimated_monthly_cost' => $estimated_cost,
                'success_rate' => $stats['success_rate']
            );
        }
        
        // Sort by cost
        uasort($fee_comparison, function($a, $b) {
            return $a['estimated_monthly_cost'] <=> $b['estimated_monthly_cost'];
        });
        
        $cheapest = reset($fee_comparison);
        $current_default = $this->gateway_manager->get_default_gateway();
        
        if ($current_default) {
            $current_id = $current_default->get_id();
            if (isset($fee_comparison[$current_id])) {
                $current_cost = $fee_comparison[$current_id]['estimated_monthly_cost'];
                
                if ($cheapest['estimated_monthly_cost'] < $current_cost * 0.9) {
                    return array(
                        'action' => 'consider_switch',
                        'gateway_id' => array_key_first($fee_comparison),
                        'gateway_name' => $cheapest['gateway'],
                        'reason' => sprintf('Could save approximately R%.2f per month on fees', $current_cost - $cheapest['estimated_monthly_cost']),
                        'current_cost' => $current_cost,
                        'suggested_cost' => $cheapest['estimated_monthly_cost'],
                        'priority' => 'medium'
                    );
                }
            }
        }
        
        return null;
    }
    
    /**
     * Suggest based on success rate
     */
    private function suggest_by_success_rate() {
        $enabled_gateways = $this->gateway_manager->get_enabled_gateways();
        $current_default = $this->gateway_manager->get_default_gateway();
        
        if (count($enabled_gateways) < 2 || !$current_default) {
            return null;
        }
        
        $current_stats = $this->logger->get_stats($current_default->get_id(), 30);
        $current_success_rate = $current_stats['success_rate'];
        
        $better_gateways = array();
        
        foreach ($enabled_gateways as $id => $gateway) {
            if ($id === $current_default->get_id()) {
                continue;
            }
            
            $stats = $this->logger->get_stats($id, 30);
            
            if ($stats['success_rate'] > $current_success_rate + 5) {
                $better_gateways[$id] = array(
                    'gateway' => $gateway->get_name(),
                    'success_rate' => $stats['success_rate'],
                    'improvement' => $stats['success_rate'] - $current_success_rate
                );
            }
        }
        
        if (!empty($better_gateways)) {
            // Get the one with highest success rate
            uasort($better_gateways, function($a, $b) {
                return $b['success_rate'] <=> $a['success_rate'];
            });
            
            $best = reset($better_gateways);
            $best_id = array_key_first($better_gateways);
            
            return array(
                'action' => 'switch_for_success_rate',
                'gateway_id' => $best_id,
                'gateway_name' => $best['gateway'],
                'reason' => sprintf('Success rate is %.1f%% higher (%.1f%% vs %.1f%%)', 
                    $best['improvement'], 
                    $best['success_rate'], 
                    $current_success_rate),
                'current_rate' => $current_success_rate,
                'suggested_rate' => $best['success_rate'],
                'priority' => 'high'
            );
        }
        
        return null;
    }
    
    /**
     * Suggest routing mode
     */
    private function suggest_routing_mode() {
        $enabled_gateways = $this->gateway_manager->get_enabled_gateways();
        $failover_enabled = get_option('sapgs_failover_enabled', false);
        $routing_mode = get_option('sapgs_routing_mode', 'default');
        
        if (count($enabled_gateways) < 2) {
            return null;
        }
        
        // Check if approval rate mode would be better
        $success_rates = array();
        foreach ($enabled_gateways as $id => $gateway) {
            $stats = $this->logger->get_stats($id, 30);
            if ($stats['total'] >= 10) { // Need at least 10 transactions
                $success_rates[$id] = $stats['success_rate'];
            }
        }
        
        if (count($success_rates) >= 2) {
            $variance = max($success_rates) - min($success_rates);
            
            if ($variance > 10 && $routing_mode !== 'approval_rate') {
                return array(
                    'action' => 'enable_approval_rate_mode',
                    'reason' => sprintf('Success rate variance is %.1f%%. Approval rate routing mode could improve overall success.', $variance),
                    'priority' => 'medium'
                );
            }
        }
        
        return null;
    }
    
    /**
     * Apply suggestion
     */
    public function apply_suggestion($suggestion) {
        $license_manager = new SAPGS_LicenseManager();
        
        if (!$license_manager->is_premium_active()) {
            return array('success' => false, 'message' => 'Premium required');
        }
        
        switch ($suggestion['action']) {
            case 'switch_default':
                update_option('sapgs_default_gateway', $suggestion['gateway_id']);
                return array('success' => true, 'message' => 'Default gateway updated');
                
            case 'enable_approval_rate_mode':
                update_option('sapgs_routing_mode', 'approval_rate');
                return array('success' => true, 'message' => 'Routing mode updated');
                
            default:
                return array('success' => false, 'message' => 'Unknown action');
        }
    }
}

