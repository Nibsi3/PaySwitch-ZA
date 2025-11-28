<?php
/**
 * Stitch Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_StitchGateway implements SAPGS_GatewayInterface {
    
    private $id = 'stitch';
    private $config;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_name() {
        return 'Stitch';
    }
    
    public function get_description() {
        return 'Stitch provides payment infrastructure and bank account linking for South African businesses.';
    }
    
    public function get_config_fields() {
        return array(
            'client_id' => array(
                'label' => 'Client ID',
                'type' => 'text',
                'required' => true
            ),
            'client_secret' => array(
                'label' => 'Client Secret',
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
        return !empty($config['client_id']) && !empty($config['client_secret']);
    }
    
    private function get_access_token() {
        $config = $this->get_config();
        $sandbox = isset($config['sandbox']) && $config['sandbox'];
        $api_url = $sandbox ? 'https://api.stitch.money' : 'https://api.stitch.money';
        
        $response = wp_remote_post($api_url . '/connect/token', array(
            'body' => array(
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'grant_type' => 'client_credentials',
                'scope' => 'payments'
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        return $result['access_token'] ?? null;
    }
    
    public function connect() {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'Gateway is not configured'
            );
        }
        
        $token = $this->get_access_token();
        
        if ($token) {
            return array(
                'success' => true,
                'message' => 'Connected successfully'
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Failed to authenticate'
        );
    }
    
    public function charge($amount, $data = array()) {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'Gateway is not configured'
            );
        }
        
        $token = $this->get_access_token();
        if (!$token) {
            return array(
                'success' => false,
                'message' => 'Failed to authenticate'
            );
        }
        
        $config = $this->get_config();
        $sandbox = isset($config['sandbox']) && $config['sandbox'];
        $api_url = $sandbox ? 'https://api.stitch.money' : 'https://api.stitch.money';
        
        $start_time = microtime(true);
        
        $params = array(
            'amount' => array(
                'quantity' => number_format($amount, 2, '.', ''),
                'currency' => 'ZAR'
            ),
            'payerReference' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time(),
            'beneficiaryReference' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time(),
            'externalReference' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time()
        );
        
        $response = wp_remote_post($api_url . '/payments', array(
            'body' => json_encode($params),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
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
            'status' => isset($result['status']) && $result['status'] === 'Completed' ? 'success' : 'pending',
            'transaction_id' => $result['id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'amount' => $amount,
            'response_time' => $response_time,
            'error_message' => isset($result['status']) && $result['status'] !== 'Completed' ? ($result['message'] ?? 'Payment pending') : null,
            'request_data' => $params,
            'response_data' => $result
        ));
        
        if ($code === 200 && isset($result['id'])) {
            return array(
                'success' => true,
                'transaction_id' => $result['id'],
                'payment_url' => $result['redirectUrl'] ?? null,
                'message' => 'Payment initiated',
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
        
        $token = $this->get_access_token();
        if (!$token) {
            return array(
                'success' => false,
                'message' => 'Failed to authenticate'
            );
        }
        
        $config = $this->get_config();
        $api_url = 'https://api.stitch.money';
        
        $params = array();
        if ($amount !== null) {
            $params['amount'] = array(
                'quantity' => number_format($amount, 2, '.', ''),
                'currency' => 'ZAR'
            );
        }
        
        $response = wp_remote_post($api_url . '/payments/' . $transaction_id . '/refund', array(
            'body' => json_encode($params),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
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
        
        if ($code === 200 && isset($result['status']) && $result['status'] === 'Completed') {
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
            'percentage' => 2.0,
            'fixed' => 1.00
        );
    }
}

