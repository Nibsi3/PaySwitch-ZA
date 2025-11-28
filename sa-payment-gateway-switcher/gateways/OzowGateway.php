<?php
/**
 * Ozow Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_OzowGateway implements SAPGS_GatewayInterface {
    
    private $id = 'ozow';
    private $config;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_name() {
        return 'Ozow';
    }
    
    public function get_description() {
        return 'Ozow provides instant EFT payments and card processing for South African businesses.';
    }
    
    public function get_config_fields() {
        return array(
            'site_code' => array(
                'label' => 'Site Code',
                'type' => 'text',
                'required' => true
            ),
            'api_key' => array(
                'label' => 'API Key',
                'type' => 'password',
                'required' => true
            ),
            'private_key' => array(
                'label' => 'Private Key',
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
        return !empty($config['site_code']) && !empty($config['api_key']) && !empty($config['private_key']);
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
        $api_url = $sandbox ? 'https://api.ozow.com' : 'https://api.ozow.com';
        
        $response = wp_remote_get($api_url . '/GetBankList', array(
            'timeout' => 10,
            'headers' => array(
                'ApiKey' => $config['api_key'],
                'SiteCode' => $config['site_code']
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
        $api_url = $sandbox ? 'https://api.ozow.com' : 'https://api.ozow.com';
        
        $start_time = microtime(true);
        
        $params = array(
            'SiteCode' => $config['site_code'],
            'CountryCode' => 'ZA',
            'CurrencyCode' => 'ZAR',
            'Amount' => number_format($amount, 2, '.', ''),
            'TransactionReference' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time(),
            'BankReference' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time(),
            'Customer' => array(
                'EmailAddress' => $data['customer_email'] ?? '',
                'FirstName' => $data['customer_first_name'] ?? '',
                'LastName' => $data['customer_last_name'] ?? ''
            ),
            'CancelUrl' => isset($data['cancel_url']) ? $data['cancel_url'] : home_url(),
            'SuccessUrl' => isset($data['return_url']) ? $data['return_url'] : home_url(),
            'NotifyUrl' => isset($data['notify_url']) ? $data['notify_url'] : home_url('/?sapgs_webhook=ozow')
        );
        
        // Generate hash
        $hashString = $config['site_code'] . $params['CountryCode'] . $params['CurrencyCode'] . 
                     $params['Amount'] . $params['TransactionReference'] . $params['BankReference'] . 
                     $params['CancelUrl'] . $params['SuccessUrl'] . $params['NotifyUrl'] . $config['private_key'];
        $hash = hash('sha512', $hashString);
        $params['HashCheck'] = $hash;
        
        $response = wp_remote_post($api_url . '/PostPaymentRequest', array(
            'body' => json_encode($params),
            'headers' => array(
                'Content-Type' => 'application/json',
                'ApiKey' => $config['api_key']
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
            'transaction_id' => $result['TransactionId'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'amount' => $amount,
            'response_time' => $response_time,
            'error_message' => $code !== 200 ? ($result['ErrorMessage'] ?? 'HTTP ' . $code) : null,
            'request_data' => $params,
            'response_data' => $result
        ));
        
        if ($code === 200 && isset($result['Url'])) {
            return array(
                'success' => true,
                'transaction_id' => $result['TransactionId'] ?? null,
                'payment_url' => $result['Url'],
                'message' => 'Payment initiated',
                'response_time' => $response_time
            );
        }
        
        return array(
            'success' => false,
            'message' => $result['ErrorMessage'] ?? 'Payment failed',
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
        $api_url = 'https://api.ozow.com';
        
        $params = array(
            'SiteCode' => $config['site_code'],
            'TransactionId' => $transaction_id
        );
        
        if ($amount !== null) {
            $params['Amount'] = number_format($amount, 2, '.', '');
        }
        
        $hashString = $config['site_code'] . $params['TransactionId'] . 
                     ($params['Amount'] ?? '') . $config['private_key'];
        $hash = hash('sha512', $hashString);
        $params['HashCheck'] = $hash;
        
        $response = wp_remote_post($api_url . '/RefundPayment', array(
            'body' => json_encode($params),
            'headers' => array(
                'Content-Type' => 'application/json',
                'ApiKey' => $config['api_key']
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
        
        if ($code === 200 && isset($result['Success']) && $result['Success']) {
            return array(
                'success' => true,
                'refund_id' => $result['RefundId'] ?? $transaction_id,
                'message' => 'Refund processed'
            );
        }
        
        return array(
            'success' => false,
            'message' => $result['ErrorMessage'] ?? 'Refund failed'
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
            'percentage' => 2.5,
            'fixed' => 1.50
        );
    }
    
    public function get_credential_url($test_mode = false) {
        return 'https://dashboard.ozow.com';
    }
}

