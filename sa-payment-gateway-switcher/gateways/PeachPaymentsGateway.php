<?php
/**
 * Peach Payments Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_PeachPaymentsGateway implements SAPGS_GatewayInterface {
    
    private $id = 'peach_payments';
    private $config;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_name() {
        return 'Peach Payments';
    }
    
    public function get_description() {
        return 'Peach Payments provides comprehensive payment solutions including cards, EFT, and mobile payments.';
    }
    
    public function get_config_fields() {
        return array(
            'entity_id' => array(
                'label' => 'Entity ID',
                'type' => 'text',
                'required' => true
            ),
            'user_id' => array(
                'label' => 'User ID',
                'type' => 'text',
                'required' => true
            ),
            'password' => array(
                'label' => 'Password',
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
        return !empty($config['entity_id']) && !empty($config['user_id']) && !empty($config['password']);
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
        $api_url = $sandbox ? 'https://test.peachpayments.com' : 'https://oppwa.com';
        
        $response = wp_remote_get($api_url . '/v1/checkouts', array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . base64_encode($config['user_id'] . ':' . $config['password'])
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
            'success' => $code === 200 || $code === 401,
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
        $sandbox = isset($config['sandbox']) && $config['sandbox'];
        $api_url = $sandbox ? 'https://test.peachpayments.com' : 'https://oppwa.com';
        
        $start_time = microtime(true);
        
        $params = array(
            'entityId' => $config['entity_id'],
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'ZAR',
            'paymentType' => 'DB',
            'merchantTransactionId' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time()
        );
        
        $response = wp_remote_post($api_url . '/v1/checkouts', array(
            'body' => http_build_query($params),
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Bearer ' . base64_encode($config['user_id'] . ':' . $config['password'])
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
            'status' => isset($result['result']['code']) && strpos($result['result']['code'], '000') === 0 ? 'success' : 'failed',
            'transaction_id' => $result['id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'amount' => $amount,
            'response_time' => $response_time,
            'error_message' => isset($result['result']['code']) && strpos($result['result']['code'], '000') !== 0 ? ($result['result']['description'] ?? 'Payment failed') : null,
            'request_data' => $params,
            'response_data' => $result
        ));
        
        if ($code === 201 && isset($result['id'])) {
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
            'message' => $result['result']['description'] ?? 'Payment failed',
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
        $api_url = $sandbox ? 'https://test.peachpayments.com' : 'https://oppwa.com';
        
        $params = array(
            'entityId' => $config['entity_id'],
            'paymentType' => 'RF'
        );
        
        if ($amount !== null) {
            $params['amount'] = number_format($amount, 2, '.', '');
        }
        
        $response = wp_remote_post($api_url . '/v1/payments/' . $transaction_id, array(
            'body' => http_build_query($params),
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Bearer ' . base64_encode($config['user_id'] . ':' . $config['password'])
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
        
        if (isset($result['result']['code']) && strpos($result['result']['code'], '000') === 0) {
            return array(
                'success' => true,
                'refund_id' => $result['id'] ?? $transaction_id,
                'message' => 'Refund processed'
            );
        }
        
        return array(
            'success' => false,
            'message' => $result['result']['description'] ?? 'Refund failed'
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
            return 'https://test.peachpayments.com';
        }
        return 'https://www.peachpayments.com';
    }
}

