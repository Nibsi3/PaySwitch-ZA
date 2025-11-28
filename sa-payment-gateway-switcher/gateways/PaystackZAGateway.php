<?php
/**
 * Paystack ZA Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_PaystackZAGateway implements SAPGS_GatewayInterface {
    
    private $id = 'paystack_za';
    private $config;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_name() {
        return 'Paystack ZA';
    }
    
    public function get_description() {
        return 'Paystack provides payment infrastructure for businesses in South Africa with support for cards and bank transfers.';
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
        return !empty($config['secret_key']) && !empty($config['public_key']);
    }
    
    public function connect() {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'Gateway is not configured'
            );
        }
        
        $config = $this->get_config();
        $api_url = 'https://api.paystack.co';
        
        $response = wp_remote_get($api_url . '/bank', array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $config['secret_key']
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
        $api_url = 'https://api.paystack.co';
        
        $start_time = microtime(true);
        
        $params = array(
            'amount' => intval($amount * 100), // Amount in kobo/cents
            'currency' => 'ZAR',
            'reference' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time(),
            'email' => $data['customer_email'] ?? '',
            'callback_url' => isset($data['notify_url']) ? $data['notify_url'] : home_url('/?sapgs_webhook=paystack_za'),
            'metadata' => array(
                'order_id' => $data['order_id'] ?? null,
                'custom_fields' => array()
            )
        );
        
        $response = wp_remote_post($api_url . '/transaction/initialize', array(
            'body' => json_encode($params),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $config['secret_key']
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
            'status' => isset($result['status']) && $result['status'] ? 'success' : 'failed',
            'transaction_id' => $result['data']['reference'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'amount' => $amount,
            'response_time' => $response_time,
            'error_message' => isset($result['status']) && !$result['status'] ? ($result['message'] ?? 'Payment failed') : null,
            'request_data' => $params,
            'response_data' => $result
        ));
        
        if ($code === 200 && isset($result['status']) && $result['status']) {
            return array(
                'success' => true,
                'transaction_id' => $result['data']['reference'],
                'payment_url' => $result['data']['authorization_url'],
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
        
        $config = $this->get_config();
        $api_url = 'https://api.paystack.co';
        
        $params = array();
        if ($amount !== null) {
            $params['amount'] = intval($amount * 100);
        }
        
        $response = wp_remote_post($api_url . '/refund', array(
            'body' => json_encode(array_merge(array('transaction' => $transaction_id), $params)),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $config['secret_key']
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
        
        if ($code === 200 && isset($result['status']) && $result['status']) {
            return array(
                'success' => true,
                'refund_id' => $result['data']['id'] ?? $transaction_id,
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
            'percentage' => 2.9,
            'fixed' => 2.00
        );
    }
    
    public function get_credential_url($test_mode = false) {
        return 'https://dashboard.paystack.com/#/settings/developer';
    }
}

