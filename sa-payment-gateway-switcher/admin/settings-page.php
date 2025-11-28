<?php
/**
 * Settings Page
 * 
 * This is a separate settings page, but most settings are handled in the dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_Settings_Page {
    
    public static function render() {
        // Redirect to dashboard for now, or show additional settings
        wp_redirect(admin_url('admin.php?page=sapgs-dashboard'));
        exit;
    }
}

