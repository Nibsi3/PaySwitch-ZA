<?php
/**
 * Webhook Listener
 * 
 * Listens for and stores test mode webhook events
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_WebhookListener {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sapgs_webhook_events';
    }
    
    /**
     * Record incoming webhook event
     */
    public function record_event($gateway_id, $event_type, $payload, $signature_valid = false) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'gateway_id' => sanitize_text_field($gateway_id),
                'event_type' => sanitize_text_field($event_type),
                'payload' => is_array($payload) ? json_encode($payload) : sanitize_textarea_field($payload),
                'signature_valid' => $signature_valid ? 1 : 0,
                'received_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get recent webhook events
     */
    public function get_recent_events($gateway_id = null, $limit = 50) {
        global $wpdb;
        
        if ($gateway_id) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->table_name}
                 WHERE gateway_id = %s
                 ORDER BY received_at DESC
                 LIMIT %d",
                $gateway_id,
                $limit
            ), ARRAY_A);
        } else {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->table_name}
                 ORDER BY received_at DESC
                 LIMIT %d",
                $limit
            ), ARRAY_A);
        }
        
        // Decode JSON payloads
        foreach ($results as &$result) {
            if ($result['payload']) {
                $decoded = json_decode($result['payload'], true);
                if ($decoded) {
                    $result['payload'] = $decoded;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get events by type
     */
    public function get_events_by_type($event_type, $limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE event_type = %s
             ORDER BY received_at DESC
             LIMIT %d",
            $event_type,
            $limit
        ), ARRAY_A);
    }
}

