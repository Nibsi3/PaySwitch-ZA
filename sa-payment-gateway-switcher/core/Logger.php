<?php
/**
 * Logger
 * 
 * Handles transaction logging
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_Logger {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sapgs_logs';
    }
    
    /**
     * Log a transaction
     */
    public function log($gateway_id, $data) {
        global $wpdb;
        
        $license_manager = new SAPGS_LicenseManager();
        $is_premium = $license_manager->is_premium_active();
        
        // Check log limit for free users
        if (!$is_premium) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name}"
            ));
            
            if ($count >= 20) {
                // Remove oldest log
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$this->table_name} ORDER BY created_at ASC LIMIT 1"
                ));
            }
        }
        
        $wpdb->insert(
            $this->table_name,
            array(
                'gateway_id' => sanitize_text_field($gateway_id),
                'transaction_id' => isset($data['transaction_id']) ? sanitize_text_field($data['transaction_id']) : null,
                'order_id' => isset($data['order_id']) ? intval($data['order_id']) : null,
                'amount' => isset($data['amount']) ? floatval($data['amount']) : null,
                'currency' => isset($data['currency']) ? sanitize_text_field($data['currency']) : 'ZAR',
                'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'pending',
                'response_time' => isset($data['response_time']) ? intval($data['response_time']) : null,
                'error_message' => isset($data['error_message']) ? sanitize_textarea_field($data['error_message']) : null,
                'request_data' => isset($data['request_data']) ? json_encode($data['request_data']) : null,
                'response_data' => isset($data['response_data']) ? json_encode($data['response_data']) : null
            ),
            array('%s', '%s', '%d', '%f', '%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get logs
     */
    public function get_logs($gateway_id = null, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $where = '';
        if ($gateway_id) {
            $where = $wpdb->prepare("WHERE gateway_id = %s", $gateway_id);
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A);
        
        // Decode JSON fields
        foreach ($results as &$result) {
            if ($result['request_data']) {
                $result['request_data'] = json_decode($result['request_data'], true);
            }
            if ($result['response_data']) {
                $result['response_data'] = json_decode($result['response_data'], true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get log statistics
     */
    public function get_stats($gateway_id = null, $days = 7) {
        global $wpdb;
        
        $where = $wpdb->prepare("WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        if ($gateway_id) {
            $where .= $wpdb->prepare(" AND gateway_id = %s", $gateway_id);
        }
        
        $stats = array(
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'total_amount' => 0,
            'avg_response_time' => 0
        );
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} {$where}");
        $success = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} {$where} AND status = 'success'");
        $failed = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} {$where} AND status = 'failed'");
        $total_amount = $wpdb->get_var("SELECT SUM(amount) FROM {$this->table_name} {$where} AND status = 'success'");
        $avg_response = $wpdb->get_var("SELECT AVG(response_time) FROM {$this->table_name} {$where} AND response_time IS NOT NULL");
        
        $stats['total'] = intval($total);
        $stats['success'] = intval($success);
        $stats['failed'] = intval($failed);
        $stats['total_amount'] = floatval($total_amount);
        $stats['avg_response_time'] = floatval($avg_response);
        $stats['success_rate'] = $total > 0 ? ($success / $total) * 100 : 0;
        
        return $stats;
    }
}

