<?php
/**
 * Yoco Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_YocoGateway implements SAPGS_GatewayInterface {
    
    private $id = 'yoco';
    private $config;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_name() {
        return 'Yoco';
    }
    
    public function get_description() {
        return 'Yoco offers card payment solutions with competitive rates for South African businesses.';
    }
    
    public function get_config_fields() {
        return array(
            'secret_key' => array(
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true
            ),
            'public_key' => array(
                'label' => 'Public Key',
                'type' => 'text',
                'required' => true
            ),
            'sandbox' => array(
                'label' => 'Sandbox Mode',
                'type' => 'checkbox',
                'default' => true
            )
        );
    }
    
    public function get_config() {
        if ($this->config === null) {
            $this->config = get_option('sapgs_gateway_' . $this->id, array());
        }
        return $this->config;
    }
    
    public function save_config($config) {
        $this->config = $config;
        update_option('sapgs_gateway_' . $this->id, $config);
        return true;
    }
    
    public function is_configured() {
        $config = $this->get_config();
        $test_mode = isset($config['test_mode']) && ($config['test_mode'] === '1' || $config['test_mode'] === true || $config['test_mode'] === 1);
        
        if ($test_mode) {
            return !empty($config['test_secret_key']) && !empty($config['test_public_key']);
        } else {
            return !empty($config['secret_key']) && !empty($config['public_key']);
        }
    }
    
    public function connect() {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'Gateway is not configured'
            );
        }
        
        $config = $this->get_config();
        $test_mode = isset($config['test_mode']) && ($config['test_mode'] === '1' || $config['test_mode'] === true || $config['test_mode'] === 1);
        $secret_key = $test_mode ? ($config['test_secret_key'] ?? '') : ($config['secret_key'] ?? '');
        $api_url = 'https://api.yoco.com/v1';
        
        $response = wp_remote_get($api_url . '/charges', array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        return array(
            'success' => $code === 200 || $code === 401, // 401 means auth works but no charges
            'message' => ($code === 200 || $code === 401) ? 'Connected successfully' : 'Connection failed'
        );
    }
    
    public function charge($amount, $data = array()) {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'Gateway is not configured'
            );
        }
        
        $config = $this->get_config();
        $test_mode = isset($config['test_mode']) && ($config['test_mode'] === '1' || $config['test_mode'] === true || $config['test_mode'] === 1);
        $secret_key = $test_mode ? ($config['test_secret_key'] ?? '') : ($config['secret_key'] ?? '');
        $api_url = 'https://api.yoco.com/v1';
        
        $start_time = microtime(true);
        
        $params = array(
            'amount' => intval($amount * 100), // Amount in cents
            'currency' => 'ZAR',
            'metadata' => array(
                'order_id' => $data['order_id'] ?? null,
                'customer_email' => $data['customer_email'] ?? ''
            )
        );
        
        if (isset($data['token'])) {
            $params['token'] = $data['token'];
        }
        
        $response = wp_remote_post($api_url . '/charges', array(
            'body' => json_encode($params),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $secret_key
            ),
            'timeout' => 30
        ));
        
        $response_time = round((microtime(true) - $start_time) * 1000);
        
        if (is_wp_error($response)) {
            $logger = new SAPGS_Logger();
            $logger->log($this->id, array(
                'status' => 'failed',
                'amount' => $amount,
                'response_time' => $response_time,
                'error_message' => $response->get_error_message(),
                'request_data' => $params
            ));
            
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
                'response_time' => $response_time
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        $result = json_decode($body, true);
        
        $logger = new SAPGS_Logger();
        $logger->log($this->id, array(
            'status' => isset($result['status']) && $result['status'] === 'successful' ? 'success' : 'failed',
            'transaction_id' => $result['id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'amount' => $amount,
            'response_time' => $response_time,
            'error_message' => isset($result['status']) && $result['status'] !== 'successful' ? ($result['message'] ?? 'Payment failed') : null,
            'request_data' => $params,
            'response_data' => $result
        ));
        
        if ($code === 201 && isset($result['status']) && $result['status'] === 'successful') {
            return array(
                'success' => true,
                'transaction_id' => $result['id'],
                'message' => 'Payment successful',
                'response_time' => $response_time
            );
        }
        
        return array(
            'success' => false,
            'message' => $result['message'] ?? 'Payment failed',
            'response_time' => $response_time
        );
    }
    
    public function refund($transaction_id, $amount = null) {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'Gateway is not configured'
            );
        }
        
        $config = $this->get_config();
        $test_mode = isset($config['test_mode']) && ($config['test_mode'] === '1' || $config['test_mode'] === true || $config['test_mode'] === 1);
        $secret_key = $test_mode ? ($config['test_secret_key'] ?? '') : ($config['secret_key'] ?? '');
        $api_url = 'https://api.yoco.com/v1';
        
        $params = array();
        if ($amount !== null) {
            $params['amount'] = intval($amount * 100);
        }
        
        $response = wp_remote_post($api_url . '/charges/' . $transaction_id . '/refund', array(
            'body' => json_encode($params),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $secret_key
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        $result = json_decode($body, true);
        
        if ($code === 201 && isset($result['status']) && $result['status'] === 'successful') {
            return array(
                'success' => true,
                'refund_id' => $result['id'] ?? $transaction_id,
                'message' => 'Refund processed'
            );
        }
        
        return array(
            'success' => false,
            'message' => $result['message'] ?? 'Refund failed'
        );
    }
    
    public function test_connection() {
        return $this->connect();
    }
    
    public function get_logs($limit = 50) {
        $logger = new SAPGS_Logger();
        return $logger->get_logs($this->id, $limit);
    }
    
    public function get_fees() {
        return array(
            'percentage' => 2.95,
            'fixed' => 0.00
        );
    }
}

