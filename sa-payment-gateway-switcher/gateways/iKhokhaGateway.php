<?php
/**
 * iKhokha Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_iKhokhaGateway implements SAPGS_GatewayInterface {
    
    private $id = 'ikhokha';
    private $config;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_name() {
        return 'iKhokha';
    }
    
    public function get_description() {
        return 'iKhokha is a growing South African payment provider offering card payments, EFT, and mobile payment solutions for businesses.';
    }
    
    public function get_config_fields() {
        return array(
            'merchant_id' => array(
                'label' => 'Merchant ID',
                'type' => 'text',
                'required' => true
            ),
            'api_key' => array(
                'label' => 'API Key',
                'type' => 'password',
                'required' => true
            ),
            'api_secret' => array(
                'label' => 'API Secret',
                'type' => 'password',
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
        return !empty($config['merchant_id']) && !empty($config['api_key']) && !empty($config['api_secret']);
    }
    
    public function connect() {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'Gateway is not configured'
            );
        }
        
        $config = $this->get_config();
        $sandbox = isset($config['sandbox']) && $config['sandbox'];
        $api_url = $sandbox ? 'https://sandbox.ikhokha.com/api' : 'https://api.ikhokha.com';
        
        $response = wp_remote_get($api_url . '/v1/merchant/status', array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $config['api_key'],
                'X-Merchant-ID' => $config['merchant_id'],
                'Content-Type' => 'application/json'
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
            'success' => $code === 200,
            'message' => $code === 200 ? 'Connected successfully' : 'Connection failed'
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
        $sandbox = isset($config['sandbox']) && $config['sandbox'];
        $api_url = $sandbox ? 'https://sandbox.ikhokha.com/api' : 'https://api.ikhokha.com';
        
        $start_time = microtime(true);
        
        $params = array(
            'merchant_id' => $config['merchant_id'],
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'ZAR',
            'reference' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time(),
            'return_url' => isset($data['return_url']) ? $data['return_url'] : home_url(),
            'cancel_url' => isset($data['cancel_url']) ? $data['cancel_url'] : home_url(),
            'notify_url' => isset($data['notify_url']) ? $data['notify_url'] : home_url('/?sapgs_webhook=ikhokha'),
            'customer' => array(
                'email' => $data['customer_email'] ?? '',
                'first_name' => $data['customer_first_name'] ?? '',
                'last_name' => $data['customer_last_name'] ?? ''
            ),
            'description' => isset($data['item_name']) ? $data['item_name'] : 'Order #' . ($data['order_id'] ?? '')
        );
        
        // Generate signature
        $signature_string = $config['merchant_id'] . $params['amount'] . $params['currency'] . $params['reference'] . $config['api_secret'];
        $params['signature'] = hash('sha256', $signature_string);
        
        $response = wp_remote_post($api_url . '/v1/payments', array(
            'body' => json_encode($params),
            'headers' => array(
                'Authorization' => 'Bearer ' . $config['api_key'],
                'X-Merchant-ID' => $config['merchant_id'],
                'Content-Type' => 'application/json'
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
            'status' => $code === 200 ? 'success' : 'failed',
            'amount' => $amount,
            'response_time' => $response_time,
            'request_data' => $params,
            'response_data' => $result
        ));
        
        if ($code === 200 && isset($result['payment_url'])) {
            return array(
                'success' => true,
                'transaction_id' => $result['transaction_id'] ?? $params['reference'],
                'payment_url' => $result['payment_url'],
                'message' => 'Payment initiated',
                'response_time' => $response_time
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Payment failed: ' . ($result['message'] ?? $result['error'] ?? 'Unknown error'),
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
        $sandbox = isset($config['sandbox']) && $config['sandbox'];
        $api_url = $sandbox ? 'https://sandbox.ikhokha.com/api' : 'https://api.ikhokha.com';
        
        $params = array(
            'merchant_id' => $config['merchant_id'],
            'transaction_id' => $transaction_id
        );
        
        if ($amount !== null) {
            $params['amount'] = number_format($amount, 2, '.', '');
        }
        
        // Generate signature
        $signature_string = $config['merchant_id'] . $transaction_id . ($amount !== null ? $params['amount'] : '') . $config['api_secret'];
        $params['signature'] = hash('sha256', $signature_string);
        
        $response = wp_remote_post($api_url . '/v1/refunds', array(
            'body' => json_encode($params),
            'headers' => array(
                'Authorization' => 'Bearer ' . $config['api_key'],
                'X-Merchant-ID' => $config['merchant_id'],
                'Content-Type' => 'application/json'
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
        
        if ($code === 200 && isset($result['status']) && $result['status'] === 'success') {
            return array(
                'success' => true,
                'refund_id' => $result['refund_id'] ?? $transaction_id,
                'message' => 'Refund processed'
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Refund failed: ' . ($result['message'] ?? $result['error'] ?? 'Unknown error')
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
            'percentage' => 2.75,
            'fixed' => 1.50
        );
    }
    
    public function get_credential_url($test_mode = false) {
        if ($test_mode) {
            return 'https://sandbox.ikhokha.com/merchant/login';
        }
        return 'https://www.ikhokha.com/merchant/login';
    }
}

