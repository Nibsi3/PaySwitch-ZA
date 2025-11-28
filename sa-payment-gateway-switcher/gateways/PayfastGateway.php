<?php
/**
 * Payfast Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_PayfastGateway implements SAPGS_GatewayInterface {
    
    private $id = 'payfast';
    private $config;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_name() {
        return 'Payfast';
    }
    
    public function get_description() {
        return 'Payfast is one of South Africa\'s leading payment gateways, supporting credit cards, debit cards, and EFT.';
    }
    
    public function get_config_fields() {
        return array(
            'merchant_id' => array(
                'label' => 'Merchant ID',
                'type' => 'text',
                'required' => true
            ),
            'merchant_key' => array(
                'label' => 'Merchant Key',
                'type' => 'text',
                'required' => true
            ),
            'passphrase' => array(
                'label' => 'Passphrase',
                'type' => 'password',
                'required' => false
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
        $test_mode = isset($config['test_mode']) && $config['test_mode'];
        
        if ($test_mode) {
            return !empty($config['test_merchant_id']) && !empty($config['test_merchant_key']);
        } else {
            return !empty($config['merchant_id']) && !empty($config['merchant_key']);
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
        $test_mode = isset($config['test_mode']) && $config['test_mode'];
        $api_url = $test_mode ? 'https://sandbox.payfast.co.za' : 'https://www.payfast.co.za';
        
        // Use test or live keys based on test mode
        $merchant_id = $test_mode ? (isset($config['test_merchant_id']) ? $config['test_merchant_id'] : '') : (isset($config['merchant_id']) ? $config['merchant_id'] : '');
        $merchant_key = $test_mode ? (isset($config['test_merchant_key']) ? $config['test_merchant_key'] : '') : (isset($config['merchant_key']) ? $config['merchant_key'] : '');
        
        $response = wp_remote_get($api_url . '/eng/query/validate', array(
            'timeout' => 10,
            'headers' => array(
                'merchant-id' => $merchant_id,
                'merchant-key' => $merchant_key
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
        $api_url = $sandbox ? 'https://sandbox.payfast.co.za' : 'https://www.payfast.co.za';
        
        $start_time = microtime(true);
        
        $params = array(
            'merchant_id' => $config['merchant_id'],
            'merchant_key' => $config['merchant_key'],
            'amount' => number_format($amount, 2, '.', ''),
            'item_name' => isset($data['item_name']) ? $data['item_name'] : 'Order #' . ($data['order_id'] ?? ''),
            'return_url' => isset($data['return_url']) ? $data['return_url'] : home_url(),
            'cancel_url' => isset($data['cancel_url']) ? $data['cancel_url'] : home_url(),
            'notify_url' => isset($data['notify_url']) ? $data['notify_url'] : home_url('/?sapgs_webhook=payfast')
        );
        
        // Generate signature
        if (!empty($config['passphrase'])) {
            $params['passphrase'] = $config['passphrase'];
        }
        
        $pfParamString = '';
        foreach ($params as $key => $val) {
            if ($val !== '') {
                $pfParamString .= $key . '=' . urlencode($val) . '&';
            }
        }
        $pfParamString = substr($pfParamString, 0, -1);
        
        $signature = md5($pfParamString);
        $params['signature'] = $signature;
        
        $response = wp_remote_post($api_url . '/eng/process', array(
            'body' => $params,
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
        
        $logger = new SAPGS_Logger();
        $logger->log($this->id, array(
            'status' => $code === 200 ? 'success' : 'failed',
            'transaction_id' => isset($data['transaction_id']) ? $data['transaction_id'] : null,
            'order_id' => $data['order_id'] ?? null,
            'amount' => $amount,
            'response_time' => $response_time,
            'error_message' => $code !== 200 ? 'HTTP ' . $code : null,
            'request_data' => $params,
            'response_data' => json_decode($body, true)
        ));
        
        if ($code === 200) {
            // Parse response to get payment URL
            parse_str($body, $result);
            
            return array(
                'success' => true,
                'transaction_id' => $result['pf_payment_id'] ?? null,
                'payment_url' => $api_url . '/eng/process?' . http_build_query($params),
                'message' => 'Payment initiated',
                'response_time' => $response_time
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Payment failed: ' . $body,
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
        $api_url = $sandbox ? 'https://sandbox.payfast.co.za' : 'https://www.payfast.co.za';
        
        $params = array(
            'merchant_id' => $config['merchant_id'],
            'merchant_key' => $config['merchant_key'],
            'pf_payment_id' => $transaction_id
        );
        
        if ($amount !== null) {
            $params['amount'] = number_format($amount, 2, '.', '');
        }
        
        $response = wp_remote_post($api_url . '/eng/refund', array(
            'body' => $params,
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
        
        if ($code === 200) {
            parse_str($body, $result);
            
            return array(
                'success' => true,
                'refund_id' => $result['refund_id'] ?? $transaction_id,
                'message' => 'Refund processed'
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Refund failed: ' . $body
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
}

