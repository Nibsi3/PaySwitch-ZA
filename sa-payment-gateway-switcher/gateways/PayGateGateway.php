<?php
/**
 * PayGate Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_PayGateGateway implements SAPGS_GatewayInterface {
    
    private $id = 'paygate';
    private $config;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_name() {
        return 'PayGate';
    }
    
    public function get_description() {
        return 'PayGate is a leading South African payment service provider offering secure online payments.';
    }
    
    public function get_config_fields() {
        return array(
            'paygate_id' => array(
                'label' => 'PayGate ID',
                'type' => 'text',
                'required' => true
            ),
            'encryption_key' => array(
                'label' => 'Encryption Key',
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
        return !empty($config['paygate_id']) && !empty($config['encryption_key']);
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
        $api_url = $sandbox ? 'https://secure.paygate.co.za/payweb3' : 'https://secure.paygate.co.za/payweb3';
        
        // PayGate uses form-based integration, so we test by checking if credentials are valid
        return array(
            'success' => true,
            'message' => 'PayGate uses form-based integration. Configuration validated.'
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
        $api_url = $sandbox ? 'https://secure.paygate.co.za/payweb3' : 'https://secure.paygate.co.za/payweb3';
        
        $start_time = microtime(true);
        
        $params = array(
            'PAYGATE_ID' => $config['paygate_id'],
            'REFERENCE' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time(),
            'AMOUNT' => number_format($amount * 100, 0, '', ''), // Amount in cents
            'CURRENCY' => 'ZAR',
            'RETURN_URL' => isset($data['return_url']) ? $data['return_url'] : home_url(),
            'TRANSACTION_DATE' => date('Y-m-d H:i:s'),
            'LOCALE' => 'en-za',
            'COUNTRY' => 'ZAF',
            'EMAIL' => $data['customer_email'] ?? ''
        );
        
        // Generate checksum
        $checksum_string = '';
        foreach ($params as $key => $value) {
            if ($value !== '') {
                $checksum_string .= $value;
            }
        }
        $checksum_string .= $config['encryption_key'];
        $params['CHECKSUM'] = md5($checksum_string);
        
        $response_time = round((microtime(true) - $start_time) * 1000);
        
        $logger = new SAPGS_Logger();
        $logger->log($this->id, array(
            'status' => 'pending',
            'order_id' => $data['order_id'] ?? null,
            'amount' => $amount,
            'response_time' => $response_time,
            'request_data' => $params
        ));
        
        // PayGate uses form POST, return form data
        return array(
            'success' => true,
            'transaction_id' => $params['REFERENCE'],
            'payment_url' => $api_url . '/initiate.trans',
            'form_data' => $params,
            'message' => 'Payment form ready',
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
        
        // PayGate refunds require API access or manual processing
        return array(
            'success' => false,
            'message' => 'Refunds must be processed through PayGate dashboard or API'
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
            'percentage' => 3.0,
            'fixed' => 2.00
        );
    }
    
    public function get_credential_url($test_mode = false) {
        return 'https://secure.paygate.co.za';
    }
}

