<?php
/**
 * Dashboard Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_Dashboard {
    
    /**
     * Get gateway logo URL
     */
    private static function get_gateway_logo($gateway_id) {
        $logo_path = SAPGS_PLUGIN_DIR . 'admin/assets/gateway-logos/' . $gateway_id . '.svg';
        $logo_url = SAPGS_PLUGIN_URL . 'admin/assets/gateway-logos/' . $gateway_id . '.svg';
        
        // Check if SVG exists, otherwise try PNG
        if (!file_exists($logo_path)) {
            $logo_path = SAPGS_PLUGIN_DIR . 'admin/assets/gateway-logos/' . $gateway_id . '.png';
            $logo_url = SAPGS_PLUGIN_URL . 'admin/assets/gateway-logos/' . $gateway_id . '.png';
        }
        
        // If still doesn't exist, return null (will use fallback)
        if (!file_exists($logo_path)) {
            return null;
        }
        
        return $logo_url;
    }
    
    public static function render() {
        $plugin = SA_Payment_Gateway_Switcher::get_instance();
        $gateway_manager = $plugin->gateway_manager;
        $license_manager = $plugin->license_manager;
        
        $all_gateways = $gateway_manager->get_all_gateways();
        $enabled_gateways = $gateway_manager->get_enabled_gateways();
        $default_gateway = $gateway_manager->get_default_gateway();
        $statuses = $gateway_manager->get_all_statuses();
        $license_info = $license_manager->get_license_info();
        
        // Get saved sort order and type
        $saved_order = get_option('sapgs_gateway_order', array());
        $sort_type = get_option('sapgs_gateway_sort_type', 'smart_weighted');
        
        // Apply sorting if premium and sort type is not manual
        if ($license_info['is_premium'] && $sort_type !== 'manual' && !empty($saved_order)) {
            // Reorder gateways based on saved order
            $sorted_gateways = array();
            foreach ($saved_order as $gateway_id) {
                if (isset($all_gateways[$gateway_id])) {
                    $sorted_gateways[$gateway_id] = $all_gateways[$gateway_id];
                }
            }
            // Add any gateways not in the saved order
            foreach ($all_gateways as $gateway_id => $gateway) {
                if (!isset($sorted_gateways[$gateway_id])) {
                    $sorted_gateways[$gateway_id] = $gateway;
                }
            }
            $all_gateways = $sorted_gateways;
        }
        
        ?>
        <div class="wrap sapgs-dashboard">
            <h1>
                <a href="https://www.idrive.com/idrive/sh/sh?k=d9m7z2x4y8" target="_blank" rel="noopener noreferrer" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
                    <span class="sapgs-icon-wrapper">
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="32" height="32" rx="6" fill="rgba(255, 255, 255, 0.25)"/>
                            <path d="M16 8L20 12H18V20H14V12H12L16 8Z" fill="white" opacity="0.9"/>
                            <path d="M8 22L12 18H10V14H14V18H16L12 22H8Z" fill="white" opacity="0.7"/>
                            <path d="M24 22L20 18H22V14H18V18H16L20 22H24Z" fill="white" opacity="0.7"/>
                            <circle cx="16" cy="16" r="2" fill="white"/>
                        </svg>
                    </span>
                    <?php echo esc_html__('PaySwitch ZA', 'sapgs'); ?>
                </a>
            </h1>
            
            <div class="sapgs-tabs">
                <nav class="sapgs-tab-nav">
                    <a href="#gateways" class="sapgs-tab-link active" data-tab="gateways"><?php echo esc_html__('Gateways', 'sapgs'); ?></a>
                    <a href="#testing" class="sapgs-tab-link" data-tab="testing"><?php echo esc_html__('Testing', 'sapgs'); ?></a>
                    <a href="#logs" class="sapgs-tab-link" data-tab="logs"><?php echo esc_html__('Logs', 'sapgs'); ?></a>
                    <?php if ($license_info['is_premium']): ?>
                    <a href="#analytics" class="sapgs-tab-link" data-tab="analytics"><?php echo esc_html__('Analytics', 'sapgs'); ?></a>
                    <?php endif; ?>
                    <a href="#premium" class="sapgs-tab-link" data-tab="premium"><?php echo esc_html__('Premium', 'sapgs'); ?></a>
                    <a href="#settings" class="sapgs-tab-link" data-tab="settings"><?php echo esc_html__('Settings', 'sapgs'); ?></a>
                </nav>
                
                <!-- Gateways Tab -->
                <div id="gateways" class="sapgs-tab-content active">
                    <?php if ($license_info['is_premium']): ?>
                    <div class="sapgs-sort-controls" style="margin-bottom: 20px; display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--sapgs-card-bg); border-radius: 12px; box-shadow: var(--sapgs-shadow);">
                        <label for="sapgs-sort-by" style="font-weight: 600; color: var(--sapgs-text); margin: 0;"><?php echo esc_html__('Sort by:', 'sapgs'); ?></label>
                        <select id="sapgs-sort-by" style="padding: 8px 12px; border: 1px solid var(--sapgs-border); border-radius: 8px; background: white; color: var(--sapgs-text); font-size: 14px; min-width: 220px;">
                            <option value="smart_weighted" <?php selected($sort_type, 'smart_weighted'); ?>><?php echo esc_html__('Smart Weighted Score (Recommended)', 'sapgs'); ?></option>
                            <option value="approval_rate" <?php selected($sort_type, 'approval_rate'); ?>><?php echo esc_html__('Highest Approval Rate', 'sapgs'); ?></option>
                            <option value="success_rate" <?php selected($sort_type, 'success_rate'); ?>><?php echo esc_html__('Highest Success Rate', 'sapgs'); ?></option>
                            <option value="lowest_fees" <?php selected($sort_type, 'lowest_fees'); ?>><?php echo esc_html__('Lowest Fees', 'sapgs'); ?></option>
                            <option value="fastest_response" <?php selected($sort_type, 'fastest_response'); ?>><?php echo esc_html__('Fastest Response Time', 'sapgs'); ?></option>
                            <option value="highest_uptime" <?php selected($sort_type, 'highest_uptime'); ?>><?php echo esc_html__('Highest Uptime', 'sapgs'); ?></option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="sapgs-gateways-grid">
                        <?php foreach ($all_gateways as $gateway_id => $gateway): 
                            $is_enabled = isset($enabled_gateways[$gateway_id]);
                            $is_default = $default_gateway && $default_gateway->get_id() === $gateway_id;
                            $raw_status = $statuses[$gateway_id] ?? 'not_configured';
                            $is_configured = $gateway->is_configured();
                            
                            // If gateway is enabled and configured, show as connected (green)
                            // Otherwise use the actual status
                            if ($is_enabled && $is_configured) {
                                $status = 'connected';
                            } else {
                                $status = $raw_status;
                            }
                        ?>
                        <?php
                            $enabled_count = count($enabled_gateways);
                            $is_premium = $license_info['is_premium'];
                            $max_free_gateways = 2;
                            $can_enable_more = $is_premium || ($enabled_count < $max_free_gateways) || $is_enabled;
                        ?>
                        <div class="sapgs-gateway-card" data-gateway-id="<?php echo esc_attr($gateway_id); ?>" data-enabled="<?php echo $is_enabled ? 'true' : 'false'; ?>" data-original-status="<?php echo esc_attr($raw_status); ?>">
                            <div class="sapgs-gateway-header">
                                <div class="sapgs-gateway-title-wrapper">
                                    <?php 
                                    $logo_url = self::get_gateway_logo($gateway_id);
                                    if ($logo_url): 
                                    ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($gateway->get_name()); ?>" class="sapgs-gateway-logo" />
                                    <?php endif; ?>
                                    <h3><?php echo esc_html($gateway->get_name()); ?></h3>
                                </div>
                                <span class="sapgs-status-indicator status-<?php echo esc_attr($status); ?>"></span>
                            </div>
                            <p class="sapgs-gateway-desc"><?php echo esc_html($gateway->get_description()); ?></p>
                            
                            <div class="sapgs-gateway-actions">
                                <label class="sapgs-toggle">
                                    <input type="checkbox" class="sapgs-enable-gateway" 
                                           data-gateway-id="<?php echo esc_attr($gateway_id); ?>"
                                           <?php checked($is_enabled); ?>
                                           <?php disabled(!$is_configured || (!$can_enable_more && !$is_enabled)); ?>>
                                    <span class="sapgs-toggle-slider"></span>
                                    <span class="sapgs-toggle-label"><?php echo $is_enabled ? __('Enabled', 'sapgs') : __('Disabled', 'sapgs'); ?></span>
                                </label>
                                <?php if (!$is_premium && !$is_enabled && $enabled_count >= $max_free_gateways): ?>
                                <span class="sapgs-premium-badge" style="font-size: 11px; color: var(--sapgs-primary); font-weight: 600;">Premium</span>
                                <?php endif; ?>
                                
                                <?php if ($is_enabled && !$is_default): ?>
                                <button class="button sapgs-set-default" data-gateway-id="<?php echo esc_attr($gateway_id); ?>">
                                    <?php echo esc_html__('Set as Default', 'sapgs'); ?>
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($is_default): ?>
                                <span class="sapgs-badge sapgs-badge-primary"><?php echo esc_html__('Default', 'sapgs'); ?></span>
                                <?php endif; ?>
                                
                                <button class="button sapgs-configure-gateway" data-gateway-id="<?php echo esc_attr($gateway_id); ?>">
                                    <?php echo esc_html__('Configure', 'sapgs'); ?>
                                </button>
                            </div>
                            
                            <?php if (!$is_configured): ?>
                            <div class="sapgs-notice sapgs-notice-warning">
                                <?php echo esc_html__('Gateway is not configured', 'sapgs'); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Testing Tab -->
                <div id="testing" class="sapgs-tab-content">
                    <h2><?php echo esc_html__('Sandbox Testing', 'sapgs'); ?></h2>
                    <p><?php echo esc_html__('Test each gateway connection and performance', 'sapgs'); ?></p>
                    
                    <?php if ($license_info['is_premium']): ?>
                    <div class="sapgs-bulk-test-section" style="margin-bottom: 30px; padding: 20px; background: var(--sapgs-card-bg); border-radius: 12px; border: 1px solid var(--sapgs-border);">
                        <h3><?php echo esc_html__('Bulk Testing (Premium)', 'sapgs'); ?></h3>
                        <p><?php echo esc_html__('Test all enabled gateways at once', 'sapgs'); ?></p>
                        <button class="button button-primary sapgs-bulk-test" id="sapgs-bulk-test-btn">
                            <?php echo esc_html__('Test All Enabled Gateways', 'sapgs'); ?>
                        </button>
                        <div id="sapgs-bulk-test-results" style="margin-top: 20px; display: none;"></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="sapgs-test-results">
                        <?php foreach ($all_gateways as $gateway_id => $gateway): ?>
                        <div class="sapgs-test-card" data-gateway-id="<?php echo esc_attr($gateway_id); ?>">
                            <div class="sapgs-test-header">
                                <h3><?php echo esc_html($gateway->get_name()); ?></h3>
                                <button class="button button-primary sapgs-test-gateway" data-gateway-id="<?php echo esc_attr($gateway_id); ?>">
                                    <?php echo esc_html__('Run Test', 'sapgs'); ?>
                                </button>
                            </div>
                            <div class="sapgs-test-result" style="display: none;">
                                <div class="sapgs-test-metrics">
                                    <div class="sapgs-metric">
                                        <span class="sapgs-metric-label"><?php echo esc_html__('Status', 'sapgs'); ?>:</span>
                                        <span class="sapgs-metric-value sapgs-test-status"></span>
                                    </div>
                                    <div class="sapgs-metric">
                                        <span class="sapgs-metric-label"><?php echo esc_html__('Response Time', 'sapgs'); ?>:</span>
                                        <span class="sapgs-metric-value sapgs-test-response-time"></span>
                                    </div>
                                    <div class="sapgs-metric">
                                        <span class="sapgs-metric-label"><?php echo esc_html__('Health Score', 'sapgs'); ?>:</span>
                                        <span class="sapgs-metric-value sapgs-test-health-score"></span>
                                    </div>
                                </div>
                                <div class="sapgs-test-message"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Logs Tab -->
                <div id="logs" class="sapgs-tab-content">
                    <h2><?php echo esc_html__('Transaction Logs', 'sapgs'); ?></h2>
                    
                    <?php if (!$license_info['is_premium']): ?>
                    <div class="sapgs-free-limit-notice" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); border-left: 4px solid var(--sapgs-primary); padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                        <strong><?php echo esc_html__('Free Plan Limit:', 'sapgs'); ?></strong>
                        <p style="margin: 8px 0 0 0;"><?php echo esc_html__('Free plan shows last 20 transaction logs. Upgrade to Premium for unlimited logging and full transaction history.', 'sapgs'); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="sapgs-logs-filter">
                        <select id="sapgs-logs-gateway-filter">
                            <option value=""><?php echo esc_html__('All Gateways', 'sapgs'); ?></option>
                            <?php foreach ($all_gateways as $gateway_id => $gateway): ?>
                            <option value="<?php echo esc_attr($gateway_id); ?>"><?php echo esc_html($gateway->get_name()); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped sapgs-logs-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Date', 'sapgs'); ?></th>
                                <th><?php echo esc_html__('Gateway', 'sapgs'); ?></th>
                                <th><?php echo esc_html__('Transaction ID', 'sapgs'); ?></th>
                                <th><?php echo esc_html__('Order ID', 'sapgs'); ?></th>
                                <th><?php echo esc_html__('Amount', 'sapgs'); ?></th>
                                <th><?php echo esc_html__('Status', 'sapgs'); ?></th>
                                <th><?php echo esc_html__('Response Time', 'sapgs'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="sapgs-logs-tbody">
                            <tr>
                                <td colspan="7" class="sapgs-loading"><?php echo esc_html__('Loading logs...', 'sapgs'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Analytics Tab (Premium) -->
                <?php if ($license_info['is_premium']): ?>
                <div id="analytics" class="sapgs-tab-content">
                    <h2><?php echo esc_html__('Analytics & Comparison', 'sapgs'); ?></h2>
                    
                    <div class="sapgs-analytics-controls">
                        <select id="sapgs-analytics-days">
                            <option value="7"><?php echo esc_html__('Last 7 days', 'sapgs'); ?></option>
                            <option value="30"><?php echo esc_html__('Last 30 days', 'sapgs'); ?></option>
                            <option value="90"><?php echo esc_html__('Last 90 days', 'sapgs'); ?></option>
                        </select>
                        <button class="button button-primary" id="sapgs-refresh-analytics"><?php echo esc_html__('Refresh', 'sapgs'); ?></button>
                    </div>
                    
                    <div class="sapgs-analytics-charts">
                        <div class="sapgs-chart-container">
                            <h4><?php echo esc_html__('Fees Comparison', 'sapgs'); ?></h4>
                            <canvas id="sapgs-chart-fees"></canvas>
                        </div>
                        <div class="sapgs-chart-container">
                            <h4><?php echo esc_html__('Success Rates', 'sapgs'); ?></h4>
                            <canvas id="sapgs-chart-success-rates"></canvas>
                        </div>
                        <div class="sapgs-chart-container">
                            <h4><?php echo esc_html__('Response Times', 'sapgs'); ?></h4>
                            <canvas id="sapgs-chart-response-times"></canvas>
                        </div>
                        <div class="sapgs-chart-container">
                            <h4><?php echo esc_html__('Approval Rates', 'sapgs'); ?></h4>
                            <canvas id="sapgs-chart-approval-rates"></canvas>
                        </div>
                        <div class="sapgs-chart-container">
                            <h4><?php echo esc_html__('Downtime Patterns', 'sapgs'); ?></h4>
                            <canvas id="sapgs-chart-downtime"></canvas>
                        </div>
                        <div class="sapgs-chart-container">
                            <h4><?php echo esc_html__('7-Day Performance', 'sapgs'); ?></h4>
                            <canvas id="sapgs-chart-seven-day"></canvas>
                        </div>
                        <div class="sapgs-chart-container">
                            <h4><?php echo esc_html__('Timeline', 'sapgs'); ?></h4>
                            <canvas id="sapgs-chart-timeline"></canvas>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div id="analytics" class="sapgs-tab-content">
                    <div class="sapgs-premium-locked">
                        <h2><?php echo esc_html__('Analytics is a Premium Feature', 'sapgs'); ?></h2>
                        <p><?php echo esc_html__('Upgrade to premium to unlock advanced analytics, comparison charts, and performance insights.', 'sapgs'); ?></p>
                        <a href="#premium" class="button button-primary sapgs-tab-link" data-tab="premium"><?php echo esc_html__('Upgrade Now', 'sapgs'); ?></a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Premium Tab -->
                <div id="premium" class="sapgs-tab-content">
                    <h2><?php echo esc_html__('Premium License', 'sapgs'); ?></h2>
                    
                    <?php if ($license_info['is_premium']): ?>
                    <div class="sapgs-license-active">
                        <h3><?php echo esc_html__('License Active', 'sapgs'); ?></h3>
                        <p><strong><?php echo esc_html__('Type', 'sapgs'); ?>:</strong> <?php echo esc_html(ucfirst($license_info['type'])); ?></p>
                        <?php if ($license_info['expires']): ?>
                        <p><strong><?php echo esc_html__('Expires', 'sapgs'); ?>:</strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($license_info['expires']))); ?></p>
                        <?php else: ?>
                        <p><strong><?php echo esc_html__('Type', 'sapgs'); ?>:</strong> <?php echo esc_html__('Lifetime', 'sapgs'); ?></p>
                        <?php endif; ?>
                        <button class="button sapgs-deactivate-license"><?php echo esc_html__('Deactivate License', 'sapgs'); ?></button>
                    </div>
                    <?php else: ?>
                    <div class="sapgs-license-form">
                        <h3><?php echo esc_html__('Upgrade to Premium', 'sapgs'); ?></h3>
                        <p><?php echo esc_html__('Unlock all features with a monthly premium subscription:', 'sapgs'); ?></p>
                        
                        <div class="sapgs-pricing-comparison" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0;">
                            <div class="sapgs-plan-card" style="border: 2px solid var(--sapgs-border); border-radius: 12px; padding: 24px; background: var(--sapgs-card-bg);">
                                <h4 style="margin-top: 0; color: var(--sapgs-text);">Free Plan</h4>
                                <div style="font-size: 32px; font-weight: 700; margin: 16px 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">R0<span style="font-size: 18px; font-weight: 400;">/month</span></div>
                                <ul style="list-style: none; padding: 0; margin: 20px 0;">
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Up to 2 payment gateways</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Last 20 transaction logs</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Basic gateway testing</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Gateway configuration</li>
                                </ul>
                            </div>
                            
                            <div class="sapgs-plan-card" style="border: 2px solid var(--sapgs-primary); border-radius: 12px; padding: 24px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%); position: relative;">
                                <div style="position: absolute; top: -12px; right: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase;">Premium</div>
                                <h4 style="margin-top: 0; color: var(--sapgs-text);">Premium Plan</h4>
                                <div style="font-size: 32px; font-weight: 700; margin: 16px 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">R299<span style="font-size: 18px; font-weight: 400;">/month</span></div>
                                <ul style="list-style: none; padding: 0; margin: 20px 0;">
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ All 9 payment gateways</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Unlimited transaction logs</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Advanced analytics & charts</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Automatic failover routing</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Load balancing & routing modes</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Scheduled daily tests</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Optimization suggestions</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Uptime monitoring</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="sapgs-test-license-notice" style="background: #EFF6FF; border: 1px solid #3B82F6; border-radius: 8px; padding: 16px; margin: 20px 0;">
                            <strong><?php echo esc_html__('Testing Mode:', 'sapgs'); ?></strong>
                            <p style="margin: 8px 0 0 0;"><?php echo esc_html__('Use license key: TEST-PREMIUM-LICENSE-2024 to test all premium features', 'sapgs'); ?></p>
                        </div>
                        
                        <div style="background: var(--sapgs-bg); padding: 20px; border-radius: 8px; margin: 20px 0;">
                            <h4 style="margin-top: 0;"><?php echo esc_html__('Get Your Premium License', 'sapgs'); ?></h4>
                            <p><?php echo esc_html__('Purchase a monthly premium license to unlock all features. Your license key will be emailed to you after purchase.', 'sapgs'); ?></p>
                            <a href="#" class="button button-primary" style="margin-top: 10px;" onclick="alert('Purchase link would go here'); return false;"><?php echo esc_html__('Purchase Premium License (R299/month)', 'sapgs'); ?></a>
                        </div>
                        <form id="sapgs-activate-license-form">
                            <input type="text" id="sapgs-license-key" class="regular-text" placeholder="<?php echo esc_attr__('Enter license key', 'sapgs'); ?>">
                            <button type="submit" class="button button-primary"><?php echo esc_html__('Activate', 'sapgs'); ?></button>
                        </form>
                        <div id="sapgs-license-message"></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Settings Tab -->
                <div id="settings" class="sapgs-tab-content">
                    <h2><?php echo esc_html__('Settings', 'sapgs'); ?></h2>
                    
                    <?php if ($license_info['is_premium']): ?>
                    <div class="sapgs-optimization-suggestions" style="margin-bottom: 30px; padding: 20px; background: var(--sapgs-card-bg); border-radius: 12px; border: 1px solid var(--sapgs-border);">
                        <h3><?php echo esc_html__('Optimization Suggestions', 'sapgs'); ?></h3>
                        <p><?php echo esc_html__('Get intelligent suggestions to optimize your payment gateway performance', 'sapgs'); ?></p>
                        <button class="button button-primary" id="sapgs-load-suggestions">
                            <?php echo esc_html__('Load Suggestions', 'sapgs'); ?>
                        </button>
                        <div id="sapgs-suggestions-container" style="margin-top: 20px;"></div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('sapgs_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <?php echo esc_html__('Failover Routing', 'sapgs'); ?>
                                    <span class="sapgs-help-tooltip" data-tooltip="<?php echo esc_attr__('If the primary gateway fails, automatically try backup gateways in order. This helps ensure payment processing continues even if one gateway is down.', 'sapgs'); ?>">?</span>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="sapgs_failover_enabled" value="1" 
                                               <?php checked(get_option('sapgs_failover_enabled', false)); ?>
                                               <?php disabled(!$license_info['is_premium']); ?>>
                                        <?php echo esc_html__('Enable automatic failover to backup gateway', 'sapgs'); ?>
                                    </label>
                                </td>
                            </tr>
                            <?php if ($license_info['is_premium']): ?>
                            <tr>
                                <th scope="row">
                                    <?php echo esc_html__('Routing Mode', 'sapgs'); ?>
                                    <span class="sapgs-help-tooltip" data-tooltip="<?php echo esc_attr__('Determines how payments are distributed across multiple enabled gateways. Default uses primary gateway first, then failover. Approval Rate routes to gateway with highest success rate. Load Balancing distributes evenly.', 'sapgs'); ?>">?</span>
                                </th>
                                <td>
                                    <select name="sapgs_routing_mode">
                                        <option value="default" <?php selected(get_option('sapgs_routing_mode', 'default'), 'default'); ?>>
                                            <?php echo esc_html__('Default (Primary + Failover)', 'sapgs'); ?>
                                        </option>
                                        <option value="approval_rate" <?php selected(get_option('sapgs_routing_mode', 'default'), 'approval_rate'); ?>>
                                            <?php echo esc_html__('Highest Approval Rate', 'sapgs'); ?>
                                        </option>
                                        <option value="load_balance" <?php selected(get_option('sapgs_routing_mode', 'default'), 'load_balance'); ?>>
                                            <?php echo esc_html__('Load Balancing', 'sapgs'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Gateway Configuration Modal -->
        <div id="sapgs-config-modal" class="sapgs-modal" style="display: none;">
            <div class="sapgs-modal-content">
                <button type="button" class="sapgs-modal-close" onclick="jQuery('#sapgs-config-modal').data('close-function') && jQuery('#sapgs-config-modal').data('close-function')();" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: var(--sapgs-text-light); width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 4px; transition: all 0.2s;">&times;</button>
                <h2 id="sapgs-config-modal-title"><?php echo esc_html__('Configure Gateway', 'sapgs'); ?></h2>
                <form id="sapgs-config-form">
                    <div id="sapgs-test-mode-toggle" style="margin-bottom: 20px; padding: 16px; background: var(--sapgs-bg); border-radius: 8px; border: 1px solid var(--sapgs-border);">
                        <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin: 0;">
                            <input type="checkbox" id="sapgs-test-mode" name="test_mode" style="width: 20px; height: 20px; cursor: pointer;">
                            <span style="font-weight: 600; color: var(--sapgs-text);">Test Mode</span>
                            <span class="sapgs-help-tooltip" data-tooltip="Enable test mode to use test/sandbox credentials instead of live production keys.">?</span>
                        </label>
                    </div>
                    <div id="sapgs-config-fields"></div>
                    <input type="hidden" id="sapgs-config-gateway-id">
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php echo esc_html__('Save Configuration', 'sapgs'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
}

