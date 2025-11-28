<?php
/**
 * SnapScan Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_SnapScanGateway implements SAPGS_GatewayInterface {
    
    private $id = 'snapscan';
    private $config;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_name() {
        return 'SnapScan';
    }
    
    public function get_description() {
        return 'SnapScan enables QR code and mobile payments for South African businesses.';
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
        $api_url = $sandbox ? 'https://pos.snapscan.io/merchant/api/v1' : 'https://pos.snapscan.io/merchant/api/v1';
        
        $response = wp_remote_get($api_url . '/status', array(
            'timeout' => 10,
            'headers' => array(
                'X-Auth' => $config['api_key']
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
        $api_url = 'https://pos.snapscan.io/merchant/api/v1';
        
        $start_time = microtime(true);
        
        $params = array(
            'merchantReference' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time(),
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'ZAR',
            'notifyUrl' => isset($data['notify_url']) ? $data['notify_url'] : home_url('/?sapgs_webhook=snapscan')
        );
        
        $response = wp_remote_post($api_url . '/payments', array(
            'body' => json_encode($params),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Auth' => $config['api_key']
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
            'status' => isset($result['status']) && $result['status'] === 'completed' ? 'success' : 'pending',
            'transaction_id' => $result['snapCode'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'amount' => $amount,
            'response_time' => $response_time,
            'error_message' => isset($result['status']) && $result['status'] !== 'completed' ? ($result['message'] ?? 'Payment pending') : null,
            'request_data' => $params,
            'response_data' => $result
        ));
        
        if ($code === 200 && isset($result['snapCode'])) {
            return array(
                'success' => true,
                'transaction_id' => $result['snapCode'],
                'payment_url' => $result['snapCode'], // QR code reference
                'qr_code' => $result['snapCode'],
                'message' => 'QR code generated',
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
        $api_url = 'https://pos.snapscan.io/merchant/api/v1';
        
        $params = array(
            'snapCode' => $transaction_id
        );
        
        if ($amount !== null) {
            $params['amount'] = number_format($amount, 2, '.', '');
        }
        
        $response = wp_remote_post($api_url . '/refunds', array(
            'body' => json_encode($params),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Auth' => $config['api_key']
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
        
        if ($code === 200 && isset($result['status']) && $result['status'] === 'completed') {
            return array(
                'success' => true,
                'refund_id' => $result['refundId'] ?? $transaction_id,
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
            'percentage' => 2.75,
            'fixed' => 0.00
        );
    }
    
    public function get_credential_url($test_mode = false) {
        return 'https://merchant.snapscan.co.za';
    }
}

