<?php
/**
 * Failover Tracker
 * 
 * Tracks failover events for reporting
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_FailoverTracker {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sapgs_failovers';
    }
    
    /**
     * Record a failover event
     */
    public function record_failover($primary_gateway_id, $backup_gateway_id, $order_id = null, $amount = null, $error_message = null, $recovery_time = null) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'primary_gateway_id' => sanitize_text_field($primary_gateway_id),
                'backup_gateway_id' => sanitize_text_field($backup_gateway_id),
                'order_id' => $order_id ? intval($order_id) : null,
                'amount' => $amount ? floatval($amount) : null,
                'error_message' => $error_message ? sanitize_textarea_field($error_message) : null,
                'recovery_time' => $recovery_time ? intval($recovery_time) : null,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%f', '%s', '%d', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get failover statistics
     */
    public function get_statistics($days = 30) {
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Total failovers
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE created_at >= %s",
            $date_from
        ));
        
        // Failovers by primary gateway
        $by_primary = $wpdb->get_results($wpdb->prepare(
            "SELECT primary_gateway_id, COUNT(*) as count, AVG(recovery_time) as avg_recovery
             FROM {$this->table_name}
             WHERE created_at >= %s
             GROUP BY primary_gateway_id
             ORDER BY count DESC",
            $date_from
        ), ARRAY_A);
        
        // Failovers by backup gateway
        $by_backup = $wpdb->get_results($wpdb->prepare(
            "SELECT backup_gateway_id, COUNT(*) as count
             FROM {$this->table_name}
             WHERE created_at >= %s
             GROUP BY backup_gateway_id
             ORDER BY count DESC",
            $date_from
        ), ARRAY_A);
        
        // Failover timeline (daily)
        $timeline = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count
             FROM {$this->table_name}
             WHERE created_at >= %s
             GROUP BY DATE(created_at)
             ORDER BY date DESC",
            $date_from
        ), ARRAY_A);
        
        // Average recovery time
        $avg_recovery = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(recovery_time) FROM {$this->table_name} WHERE created_at >= %s AND recovery_time IS NOT NULL",
            $date_from
        ));
        
        return array(
            'total' => intval($total),
            'by_primary_gateway' => $by_primary,
            'by_backup_gateway' => $by_backup,
            'timeline' => $timeline,
            'avg_recovery_time' => $avg_recovery ? round($avg_recovery, 2) : 0,
            'days' => $days
        );
    }
    
    /**
     * Get recent failover events
     */
    public function get_recent_events($limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             ORDER BY created_at DESC
             LIMIT %d",
            $limit
        ), ARRAY_A);
    }
    
    /**
     * Get failover events for a specific gateway
     */
    public function get_gateway_failovers($gateway_id, $days = 30) {
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE (primary_gateway_id = %s OR backup_gateway_id = %s)
             AND created_at >= %s
             ORDER BY created_at DESC",
            $gateway_id,
            $gateway_id,
            $date_from
        ), ARRAY_A);
    }
}

