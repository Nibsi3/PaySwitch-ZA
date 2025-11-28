<?php
/**
 * Sandbox Tester
 * 
 * Handles sandbox testing for gateways
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_SandboxTester {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sapgs_tests';
    }
    
    /**
     * Test a gateway
     */
    public function test_gateway($gateway_id) {
        $gateway_manager = new SAPGS_GatewayManager();
        $gateway = $gateway_manager->get_gateway($gateway_id);
        
        if (!$gateway) {
            return array(
                'success' => false,
                'message' => 'Gateway not found'
            );
        }
        
        if (!$gateway->is_configured()) {
            return array(
                'success' => false,
                'message' => 'Gateway is not configured',
                'health_score' => 0
            );
        }
        
        $start_time = microtime(true);
        
        // Run connection test
        $connection_test = $gateway->test_connection();
        
        $response_time = round((microtime(true) - $start_time) * 1000);
        
        // Run additional tests
        $dns_test = $this->test_dns($gateway_id);
        $tls_test = $this->test_tls($gateway_id);
        $webhook_test = $this->test_webhook($gateway_id);
        
        // Calculate health score
        $health_score = $this->calculate_health_score(array(
            'connection' => $connection_test['success'] ?? false,
            'response_time' => $response_time,
            'dns' => $dns_test['success'] ?? false,
            'tls' => $tls_test['success'] ?? false,
            'webhook' => $webhook_test['success'] ?? false
        ));
        
        // Save test result
        $this->save_test_result($gateway_id, 'full', $connection_test['success'] ?? false, $response_time, $health_score, array(
            'connection' => $connection_test,
            'dns' => $dns_test,
            'tls' => $tls_test,
            'webhook' => $webhook_test
        ));
        
        return array(
            'success' => $connection_test['success'] ?? false,
            'response_time' => $response_time,
            'health_score' => $health_score,
            'message' => $connection_test['message'] ?? 'Test completed',
            'tests' => array(
                'connection' => $connection_test,
                'dns' => $dns_test,
                'tls' => $tls_test,
                'webhook' => $webhook_test
            )
        );
    }
    
    /**
     * Test DNS resolution
     */
    private function test_dns($gateway_id) {
        $gateway_manager = new SAPGS_GatewayManager();
        $gateway = $gateway_manager->get_gateway($gateway_id);
        $config = $gateway->get_config();
        
        $api_url = $config['api_url'] ?? '';
        if (empty($api_url)) {
            return array('success' => false, 'message' => 'No API URL configured');
        }
        
        $host = parse_url($api_url, PHP_URL_HOST);
        if (!$host) {
            return array('success' => false, 'message' => 'Invalid API URL');
        }
        
        $ip = gethostbyname($host);
        
        return array(
            'success' => $ip !== $host,
            'message' => $ip !== $host ? "DNS resolved to {$ip}" : "DNS resolution failed",
            'ip' => $ip !== $host ? $ip : null
        );
    }
    
    /**
     * Test TLS/SSL
     */
    private function test_tls($gateway_id) {
        $gateway_manager = new SAPGS_GatewayManager();
        $gateway = $gateway_manager->get_gateway($gateway_id);
        $config = $gateway->get_config();
        
        $api_url = $config['api_url'] ?? '';
        if (empty($api_url)) {
            return array('success' => false, 'message' => 'No API URL configured');
        }
        
        $host = parse_url($api_url, PHP_URL_HOST);
        if (!$host) {
            return array('success' => false, 'message' => 'Invalid API URL');
        }
        
        $context = stream_context_create(array(
            'ssl' => array(
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        ));
        
        $port = parse_url($api_url, PHP_URL_PORT) ?: (parse_url($api_url, PHP_URL_SCHEME) === 'https' ? 443 : 80);
        
        $socket = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);
        
        if ($socket) {
            $cert = stream_context_get_params($socket)['options']['ssl']['peer_certificate'] ?? null;
            fclose($socket);
            
            return array(
                'success' => $cert !== null,
                'message' => $cert ? 'TLS connection successful' : 'TLS connection failed',
                'certificate' => $cert ? 'Valid' : null
            );
        }
        
        return array(
            'success' => false,
            'message' => "Could not connect: {$errstr}"
        );
    }
    
    /**
     * Test webhook endpoint
     */
    private function test_webhook($gateway_id) {
        // This would test if webhook endpoint is accessible
        // For now, return a basic check
        $webhook_url = add_query_arg(array(
            'sapgs_webhook' => $gateway_id,
            'test' => '1'
        ), home_url('/'));
        
        $response = wp_remote_get($webhook_url, array(
            'timeout' => 5,
            'sslverify' => false
        ));
        
        $success = !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 500;
        
        return array(
            'success' => $success,
            'message' => $success ? 'Webhook endpoint accessible' : 'Webhook endpoint not accessible',
            'url' => $webhook_url
        );
    }
    
    /**
     * Calculate health score
     */
    private function calculate_health_score($tests) {
        $score = 0;
        $max_score = 100;
        
        // Connection test (40 points)
        if ($tests['connection']) {
            $score += 40;
        }
        
        // Response time (30 points) - faster is better
        if (isset($tests['response_time'])) {
            $max_time = 3000; // 3 seconds
            $time_score = max(0, 1 - ($tests['response_time'] / $max_time));
            $score += $time_score * 30;
        }
        
        // DNS test (15 points)
        if ($tests['dns']) {
            $score += 15;
        }
        
        // TLS test (10 points)
        if ($tests['tls']) {
            $score += 10;
        }
        
        // Webhook test (5 points)
        if ($tests['webhook']) {
            $score += 5;
        }
        
        return min($max_score, round($score));
    }
    
    /**
     * Save test result
     */
    private function save_test_result($gateway_id, $test_type, $success, $response_time, $health_score, $test_data) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'gateway_id' => sanitize_text_field($gateway_id),
                'test_type' => sanitize_text_field($test_type),
                'success' => $success ? 1 : 0,
                'response_time' => intval($response_time),
                'health_score' => intval($health_score),
                'test_data' => json_encode($test_data)
            ),
            array('%s', '%s', '%d', '%d', '%d', '%s')
        );
    }
    
    /**
     * Get test history
     */
    public function get_test_history($gateway_id, $limit = 10) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE gateway_id = %s 
            ORDER BY created_at DESC 
            LIMIT %d",
            $gateway_id,
            $limit
        ), ARRAY_A);
        
        foreach ($results as &$result) {
            if ($result['test_data']) {
                $result['test_data'] = json_decode($result['test_data'], true);
            }
        }
        
        return $results;
    }
}

