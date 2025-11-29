<?php
/**
 * Webhook Health Checker
 * 
 * Checks webhook reachability and signature validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_WebhookHealthChecker {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sapgs_webhook_health';
    }
    
    /**
     * Check webhook health for a gateway
     */
    public function check_webhook_health($gateway_id) {
        $gateway_manager = new SAPGS_GatewayManager();
        $gateway = $gateway_manager->get_gateway($gateway_id);
        
        if (!$gateway || !$gateway->is_configured()) {
            return array(
                'success' => false,
                'message' => 'Gateway not configured'
            );
        }
        
        $start_time = microtime(true);
        
        // Get webhook URL for this gateway
        $webhook_url = $this->get_webhook_url($gateway_id);
        
        // Test webhook reachability
        $is_reachable = $this->test_webhook_reachability($webhook_url);
        
        // Test signature validation (if gateway supports it)
        $signature_valid = $this->test_signature_validation($gateway_id, $webhook_url);
        
        $response_time = round((microtime(true) - $start_time) * 1000);
        
        // Store result
        $this->record_check($gateway_id, $is_reachable, $signature_valid, $response_time);
        
        return array(
            'success' => true,
            'is_reachable' => $is_reachable,
            'signature_valid' => $signature_valid,
            'response_time' => $response_time,
            'webhook_url' => $webhook_url
        );
    }
    
    /**
     * Get webhook URL for gateway
     */
    private function get_webhook_url($gateway_id) {
        // Use the same format as gateways use: /?sapgs_webhook={gateway_id}
        $site_url = get_site_url();
        return $site_url . '/?sapgs_webhook=' . $gateway_id;
    }
    
    /**
     * Test webhook reachability
     */
    private function test_webhook_reachability($webhook_url) {
        // Try GET first (some endpoints accept GET for verification)
        $response = wp_remote_get($webhook_url, array(
            'timeout' => 10,
            'sslverify' => true
        ));
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            // 200, 201, or 405 (method not allowed but endpoint exists) are acceptable
            if (in_array($code, array(200, 201, 405))) {
                return true;
            }
        }
        
        // Try POST (webhooks typically use POST)
        $response = wp_remote_post($webhook_url, array(
            'timeout' => 10,
            'sslverify' => true,
            'body' => json_encode(array('test' => true))
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        // 200, 201, 400 (bad request but endpoint exists), or 405 are acceptable
        return in_array($code, array(200, 201, 400, 405));
    }
    
    /**
     * Test signature validation
     */
    private function test_signature_validation($gateway_id, $webhook_url) {
        // This would need gateway-specific implementation
        // For now, return null (unknown)
        return null;
    }
    
    /**
     * Record health check result
     */
    private function record_check($gateway_id, $is_reachable, $signature_valid, $response_time) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'gateway_id' => sanitize_text_field($gateway_id),
                'is_reachable' => $is_reachable ? 1 : 0,
                'signature_valid' => $signature_valid !== null ? ($signature_valid ? 1 : 0) : null,
                'response_time' => intval($response_time),
                'checked_at' => current_time('mysql')
            ),
            array('%s', '%d', '%d', '%d', '%s')
        );
    }
    
    /**
     * Get webhook health statistics
     */
    public function get_health_stats($gateway_id = null, $days = 7) {
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        if ($gateway_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    gateway_id,
                    AVG(is_reachable) as reachability_rate,
                    AVG(signature_valid) as signature_validity_rate,
                    AVG(response_time) as avg_response_time,
                    COUNT(*) as total_checks
                  FROM {$this->table_name}
                  WHERE checked_at >= %s AND gateway_id = %s
                  GROUP BY gateway_id",
                $date_from,
                $gateway_id
            ), ARRAY_A);
        } else {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    gateway_id,
                    AVG(is_reachable) as reachability_rate,
                    AVG(signature_valid) as signature_validity_rate,
                    AVG(response_time) as avg_response_time,
                    COUNT(*) as total_checks
                  FROM {$this->table_name}
                  WHERE checked_at >= %s
                  GROUP BY gateway_id",
                $date_from
            ), ARRAY_A);
        }
    }
}

