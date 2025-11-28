<?php
/**
 * Zapper Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_ZapperGateway implements SAPGS_GatewayInterface {
    
    private $id = 'zapper';
    private $config;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_name() {
        return 'Zapper';
    }
    
    public function get_description() {
        return 'Zapper enables QR code payments and mobile wallet transactions in South Africa.';
    }
    
    public function get_config_fields() {
        return array(
            'site_id' => array(
                'label' => 'Site ID',
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
        return !empty($config['site_id']) && !empty($config['api_key']);
    }
    
    public function connect() {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'Gateway is not configured'
            );
        }
        
        $config = $this->get_config();
        $api_url = 'https://api.zapper.co.za';
        
        $response = wp_remote_get($api_url . '/v1/sites/' . $config['site_id'], array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $config['api_key']
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
        $api_url = 'https://api.zapper.co.za';
        
        $start_time = microtime(true);
        
        $params = array(
            'siteId' => $config['site_id'],
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'ZAR',
            'reference' => isset($data['order_id']) ? 'ORDER-' . $data['order_id'] : 'TXN-' . time(),
            'returnUrl' => isset($data['return_url']) ? $data['return_url'] : home_url(),
            'notifyUrl' => isset($data['notify_url']) ? $data['notify_url'] : home_url('/?sapgs_webhook=zapper')
        );
        
        $response = wp_remote_post($api_url . '/v1/payments', array(
            'body' => json_encode($params),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $config['api_key']
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
            'status' => isset($result['status']) && $result['status'] === 'success' ? 'success' : 'pending',
            'transaction_id' => $result['paymentId'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'amount' => $amount,
            'response_time' => $response_time,
            'error_message' => isset($result['status']) && $result['status'] !== 'success' ? ($result['message'] ?? 'Payment pending') : null,
            'request_data' => $params,
            'response_data' => $result
        ));
        
        if ($code === 200 && isset($result['qrCode'])) {
            return array(
                'success' => true,
                'transaction_id' => $result['paymentId'] ?? null,
                'payment_url' => $result['qrCode'],
                'qr_code' => $result['qrCode'],
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
        $api_url = 'https://api.zapper.co.za';
        
        $params = array(
            'paymentId' => $transaction_id
        );
        
        if ($amount !== null) {
            $params['amount'] = number_format($amount, 2, '.', '');
        }
        
        $response = wp_remote_post($api_url . '/v1/refunds', array(
            'body' => json_encode($params),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $config['api_key']
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
            'percentage' => 2.5,
            'fixed' => 0.00
        );
    }
    
    public function get_credential_url($test_mode = false) {
        return 'https://www.zapper.co.za/merchant';
    }
}

