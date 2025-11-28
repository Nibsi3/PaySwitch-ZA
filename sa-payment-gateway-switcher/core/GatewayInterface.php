<?php
/**
 * Gateway Interface
 * 
 * All payment gateways must implement this interface
 */

if (!defined('ABSPATH')) {
    exit;
}

interface SAPGS_GatewayInterface {
    
    /**
     * Get gateway ID
     */
    public function get_id();
    
    /**
     * Get gateway name
     */
    public function get_name();
    
    /**
     * Get gateway description
     */
    public function get_description();
    
    /**
     * Connect to gateway API
     * @return array Result with 'success' and 'message'
     */
    public function connect();
    
    /**
     * Process a charge/payment
     * @param float $amount Amount to charge
     * @param array $data Payment data (order_id, customer, etc.)
     * @return array Result with 'success', 'transaction_id', 'message'
     */
    public function charge($amount, $data = array());
    
    /**
     * Process a refund
     * @param string $transaction_id Original transaction ID
     * @param float $amount Refund amount (optional, full refund if not provided)
     * @return array Result with 'success', 'refund_id', 'message'
     */
    public function refund($transaction_id, $amount = null);
    
    /**
     * Test connection to gateway
     * @return array Result with 'success', 'response_time', 'message', 'health_score'
     */
    public function test_connection();
    
    /**
     * Get transaction logs
     * @param int $limit Number of logs to retrieve
     * @return array Array of log entries
     */
    public function get_logs($limit = 50);
    
    /**
     * Get gateway configuration fields
     * @return array Array of field definitions
     */
    public function get_config_fields();
    
    /**
     * Save gateway configuration
     * @param array $config Configuration data
     * @return bool Success
     */
    public function save_config($config);
    
    /**
     * Get gateway configuration
     * @return array Configuration data
     */
    public function get_config();
    
    /**
     * Check if gateway is configured
     * @return bool
     */
    public function is_configured();
    
    /**
     * Get gateway fees (percentage and fixed)
     * @return array ['percentage' => float, 'fixed' => float]
     */
    public function get_fees();
    
    /**
     * Get credential URL for test or live mode
     * @param bool $test_mode Whether to get test or live credentials URL
     * @return string URL to the gateway's credentials/dashboard page
     */
    public function get_credential_url($test_mode = false);
}

