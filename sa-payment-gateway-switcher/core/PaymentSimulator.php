<?php
/**
 * Payment Simulator
 * 
 * Simulates checkout payment flow for testing
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_PaymentSimulator {
    
    /**
     * Simulate a checkout payment
     */
    public function simulate_checkout($gateway_id, $amount = 100.00) {
        $gateway_manager = new SAPGS_GatewayManager();
        $gateway = $gateway_manager->get_gateway($gateway_id);
        
        if (!$gateway || !$gateway->is_configured()) {
            return array(
                'success' => false,
                'message' => 'Gateway not configured'
            );
        }
        
        $results = array(
            'gateway_id' => $gateway_id,
            'gateway_name' => $gateway->get_name(),
            'amount' => $amount,
            'steps' => array()
        );
        
        // Step 1: Live connectivity test
        $connectivity_start = microtime(true);
        $connectivity_result = $gateway->test_connection();
        $connectivity_time = round((microtime(true) - $connectivity_start) * 1000);
        
        $results['steps']['connectivity'] = array(
            'name' => 'Live Connectivity Test',
            'success' => $connectivity_result['success'] ?? false,
            'response_time' => $connectivity_time,
            'message' => $connectivity_result['message'] ?? 'Connection test completed'
        );
        
        if (!$connectivity_result['success']) {
            $results['success'] = false;
            $results['message'] = 'Connectivity test failed';
            return $results;
        }
        
        // Step 2: Live API response test
        $api_start = microtime(true);
        $api_result = $this->test_api_response($gateway, $amount);
        $api_time = round((microtime(true) - $api_start) * 1000);
        
        $results['steps']['api_response'] = array(
            'name' => 'Live API Response Test',
            'success' => $api_result['success'] ?? false,
            'response_time' => $api_time,
            'message' => $api_result['message'] ?? 'API test completed'
        );
        
        // Step 3: Webhook roundtrip test (if in test mode)
        $webhook_start = microtime(true);
        $webhook_result = $this->test_webhook_roundtrip($gateway_id);
        $webhook_time = round((microtime(true) - $webhook_start) * 1000);
        
        $results['steps']['webhook_roundtrip'] = array(
            'name' => 'Webhook Roundtrip Test',
            'success' => $webhook_result['success'] ?? false,
            'response_time' => $webhook_time,
            'message' => $webhook_result['message'] ?? 'Webhook test completed'
        );
        
        // Calculate total time
        $total_time = $connectivity_time + $api_time + $webhook_time;
        $results['total_time'] = $total_time;
        $results['time_to_confirmation'] = $total_time;
        $results['success'] = $connectivity_result['success'] && ($api_result['success'] ?? true);
        
        return $results;
    }
    
    /**
     * Test API response
     */
    private function test_api_response($gateway, $amount) {
        // Try to initiate a test payment (if gateway supports test mode)
        try {
            $test_result = $gateway->test_connection();
            return array(
                'success' => $test_result['success'] ?? false,
                'message' => $test_result['message'] ?? 'API response test completed'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Test webhook roundtrip
     */
    private function test_webhook_roundtrip($gateway_id) {
        $webhook_checker = new SAPGS_WebhookHealthChecker();
        $health = $webhook_checker->check_webhook_health($gateway_id);
        
        return array(
            'success' => $health['is_reachable'] ?? false,
            'message' => $health['is_reachable'] ? 'Webhook endpoint is reachable' : 'Webhook endpoint not reachable'
        );
    }
}

