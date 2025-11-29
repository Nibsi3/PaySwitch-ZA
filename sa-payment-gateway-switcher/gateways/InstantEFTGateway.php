<?php
/**
 * Instant EFT (SID) Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_InstantEFTGateway implements SAPGS_GatewayInterface {
    
    private $id = 'instant_eft';
    private $config;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_name() {
        return 'Instant EFT (SID)';
    }
    
    public function get_description() {
        return 'Instant EFT payments with real-time bank verification. Lower fees than card payments, typically 1.5-2% per transaction.';
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
        return !empty($config['merchant_id']) && !empty($config['api_key']);
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
        $api_url = $sandbox ? 'https://sandbox.sidpayment.com/api' : 'https://api.sidpayment.com';
        
        $response = wp_remote_get($api_url . '/v1/status', array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $config['api_key'],
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
        $api_url = $sandbox ? 'https://sandbox.sidpayment.com/api' : 'https://api.sidpayment.com';
        
        $start_time = microtime(true);
        
        $params = array(
            'merchant_id' => $config['merchant_id'],
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'ZAR',
            'reference' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time(),
            'return_url' => isset($data['return_url']) ? $data['return_url'] : home_url(),
            'cancel_url' => isset($data['cancel_url']) ? $data['cancel_url'] : home_url(),
            'notify_url' => isset($data['notify_url']) ? $data['notify_url'] : home_url('/?sapgs_webhook=instant_eft'),
            'customer_email' => $data['customer_email'] ?? '',
            'customer_name' => isset($data['customer_first_name']) ? ($data['customer_first_name'] . ' ' . ($data['customer_last_name'] ?? '')) : ''
        );
        
        $response = wp_remote_post($api_url . '/v1/payments', array(
            'body' => json_encode($params),
            'headers' => array(
                'Authorization' => 'Bearer ' . $config['api_key'],
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
                'transaction_id' => $result['reference'] ?? $params['reference'],
                'payment_url' => $result['payment_url'],
                'message' => 'Payment initiated',
                'response_time' => $response_time
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Payment failed: ' . ($result['message'] ?? 'Unknown error'),
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
        $api_url = $sandbox ? 'https://sandbox.sidpayment.com/api' : 'https://api.sidpayment.com';
        
        $params = array(
            'merchant_id' => $config['merchant_id'],
            'transaction_id' => $transaction_id
        );
        
        if ($amount !== null) {
            $params['amount'] = number_format($amount, 2, '.', '');
        }
        
        $response = wp_remote_post($api_url . '/v1/refunds', array(
            'body' => json_encode($params),
            'headers' => array(
                'Authorization' => 'Bearer ' . $config['api_key'],
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
        
        if ($code === 200) {
            return array(
                'success' => true,
                'refund_id' => $result['refund_id'] ?? $transaction_id,
                'message' => 'Refund processed'
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Refund failed: ' . ($result['message'] ?? 'Unknown error')
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
            'percentage' => 1.75, // Average between 1.5-2%
            'fixed' => 0.00
        );
    }
    
    public function get_credential_url($test_mode = false) {
        if ($test_mode) {
            return 'https://sandbox.sidpayment.com/merchant/login';
        }
        return 'https://www.sidpayment.com/merchant/login';
    }
}

