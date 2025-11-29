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
    
    /**
     * Get gateway strengths and weaknesses
     */
    private static function get_gateway_info($gateway_id) {
        $info = array(
            'payfast' => array(
                'strengths' => array(
                    'Most established and trusted payment gateway in South Africa',
                    'Supports credit cards, debit cards, and EFT payments',
                    'Excellent documentation and developer support',
                    'Wide merchant acceptance and customer familiarity'
                ),
                'weaknesses' => array(
                    'Higher transaction fees compared to newer competitors',
                    'Settlement times typically 2-3 business days',
                    'Less modern API compared to newer gateways'
                )
            ),
            'paygate' => array(
                'strengths' => array(
                    'Comprehensive payment solutions with flexible integration',
                    'Robust security with advanced encryption',
                    'Good scalability for growing businesses',
                    'Reliable customer support'
                ),
                'weaknesses' => array(
                    'Settlement times can be 2-3 business days',
                    'Lower brand recognition in some markets',
                    'May require more technical setup'
                )
            ),
            'paystack_za' => array(
                'strengths' => array(
                    'Modern API with excellent developer experience',
                    'Fast transaction processing',
                    'Good documentation and developer tools',
                    'Competitive transaction fees'
                ),
                'weaknesses' => array(
                    'Newer to South African market',
                    'Less established brand recognition',
                    'Limited payment method options compared to others'
                )
            ),
            'paystackza' => array(
                'strengths' => array(
                    'Modern API with excellent developer experience',
                    'Fast transaction processing',
                    'Good documentation and developer tools',
                    'Competitive transaction fees'
                ),
                'weaknesses' => array(
                    'Newer to South African market',
                    'Less established brand recognition',
                    'Limited payment method options compared to others'
                )
            ),
            'ozow' => array(
                'strengths' => array(
                    'Instant EFT payments for faster settlements',
                    'Competitive fees, especially for EFT',
                    'Modern, user-friendly interface',
                    'Good for recurring payments'
                ),
                'weaknesses' => array(
                    'Primarily focused on EFT, limited card options',
                    'Less suitable for international transactions',
                    'Newer platform with less market history'
                )
            ),
            'snapscan' => array(
                'strengths' => array(
                    'Popular mobile payment solution',
                    'Quick and easy QR code payments',
                    'Good for small businesses and markets',
                    'Low barrier to entry'
                ),
                'weaknesses' => array(
                    'Limited to mobile payments only',
                    'Smaller transaction volume capacity',
                    'Less suitable for large e-commerce sites'
                )
            ),
            'zapper' => array(
                'strengths' => array(
                    'Widely recognized mobile payment brand',
                    'Easy QR code payment integration',
                    'Good customer adoption in South Africa',
                    'Simple setup process'
                ),
                'weaknesses' => array(
                    'Mobile payment focused, limited other options',
                    'May have transaction limits',
                    'Less comprehensive than full payment gateways'
                )
            ),
            'peach_payments' => array(
                'strengths' => array(
                    'Comprehensive payment gateway with multiple options',
                    'Good international payment support',
                    'Strong security and compliance',
                    'Flexible integration options'
                ),
                'weaknesses' => array(
                    'Can be more complex to set up',
                    'May have higher fees for some transaction types',
                    'Less brand recognition than Payfast'
                )
            ),
            'peachpayments' => array(
                'strengths' => array(
                    'Comprehensive payment gateway with multiple options',
                    'Good international payment support',
                    'Strong security and compliance',
                    'Flexible integration options'
                ),
                'weaknesses' => array(
                    'Can be more complex to set up',
                    'May have higher fees for some transaction types',
                    'Less brand recognition than Payfast'
                )
            ),
            'stitch' => array(
                'strengths' => array(
                    'Modern API-first payment infrastructure',
                    'Fast integration and developer-friendly',
                    'Good for subscription and recurring payments',
                    'Competitive pricing structure'
                ),
                'weaknesses' => array(
                    'Newer platform with less market history',
                    'Limited payment method variety',
                    'May require technical expertise for setup'
                )
            ),
            'yoco' => array(
                'strengths' => array(
                    'Great for small to medium businesses',
                    'Simple, transparent pricing',
                    'Good point-of-sale integration',
                    'User-friendly dashboard and reporting'
                ),
                'weaknesses' => array(
                    'Primarily focused on card payments',
                    'Less suitable for large enterprise needs',
                    'Limited international payment options'
                )
            ),
            'ikhokha' => array(
                'strengths' => array(
                    'Affordable payment solutions for small businesses',
                    'Easy setup and integration',
                    'Good customer support',
                    'Competitive transaction fees'
                ),
                'weaknesses' => array(
                    'Newer to the market',
                    'Limited payment method options',
                    'Less established than major gateways'
                )
            ),
            'instant_eft' => array(
                'strengths' => array(
                    'Instant EFT payments with real-time verification',
                    'Lower fees than card payments (typically 1.5-2%)',
                    'Faster settlement times',
                    'Good for South African banks'
                ),
                'weaknesses' => array(
                    'Limited to EFT payments only',
                    'Requires bank account verification',
                    'Less suitable for international customers'
                )
            ),
            'payu' => array(
                'strengths' => array(
                    'Comprehensive payment gateway solution',
                    'Supports multiple payment methods',
                    'Good for international transactions',
                    'Robust API and documentation'
                ),
                'weaknesses' => array(
                    'Can be complex to set up',
                    'May have higher fees for some transaction types',
                    'Less brand recognition in South Africa'
                )
            )
        );
        
        return isset($info[$gateway_id]) ? $info[$gateway_id] : array('strengths' => array(), 'weaknesses' => array());
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
                    <a href="#rankings" class="sapgs-tab-link" data-tab="rankings"><?php echo esc_html__('Rankings', 'sapgs'); ?></a>
                    <a href="#analytics" class="sapgs-tab-link" data-tab="analytics">
                        <?php echo esc_html__('Analytics', 'sapgs'); ?>
                        <?php if (!$license_info['is_premium']): ?>
                        <span style="font-size: 10px; margin-left: 4px; opacity: 0.7;">(Preview)</span>
                        <?php endif; ?>
                    </a>
                    <?php if ($license_info['is_premium']): ?>
                    <a href="#failover-report" class="sapgs-tab-link" data-tab="failover-report"><?php echo esc_html__('Failover Report', 'sapgs'); ?></a>
                    <?php endif; ?>
                    <a href="#webhooks" class="sapgs-tab-link" data-tab="webhooks"><?php echo esc_html__('Webhooks', 'sapgs'); ?></a>
                    <a href="#premium" class="sapgs-tab-link" data-tab="premium"><?php echo esc_html__('Premium', 'sapgs'); ?></a>
                    <a href="#settings" class="sapgs-tab-link" data-tab="settings"><?php echo esc_html__('Settings', 'sapgs'); ?></a>
                </nav>
                
                <!-- Gateways Tab -->
                <div id="gateways" class="sapgs-tab-content active">
                    <div class="sapgs-sort-controls" style="margin-bottom: 20px; display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--sapgs-card-bg); border-radius: 12px; box-shadow: var(--sapgs-shadow);">
                        <label for="sapgs-sort-by" style="font-weight: 600; color: var(--sapgs-text); margin: 0;"><?php echo esc_html__('Sort by:', 'sapgs'); ?></label>
                        <select id="sapgs-sort-by" style="padding: 8px 12px; border: 1px solid var(--sapgs-border); border-radius: 8px; background: white; color: var(--sapgs-text); font-size: 14px; min-width: 220px;" <?php disabled(!$license_info['is_premium']); ?>>
                            <option value="smart_weighted" <?php selected($sort_type, 'smart_weighted'); ?>><?php echo esc_html__('Smart Weighted Score (Recommended)', 'sapgs'); ?></option>
                            <option value="approval_rate" <?php selected($sort_type, 'approval_rate'); ?>><?php echo esc_html__('Highest Approval Rate', 'sapgs'); ?></option>
                            <option value="success_rate" <?php selected($sort_type, 'success_rate'); ?>><?php echo esc_html__('Highest Success Rate', 'sapgs'); ?></option>
                            <option value="lowest_fees" <?php selected($sort_type, 'lowest_fees'); ?>><?php echo esc_html__('Lowest Fees', 'sapgs'); ?></option>
                            <option value="fastest_response" <?php selected($sort_type, 'fastest_response'); ?>><?php echo esc_html__('Fastest Response Time', 'sapgs'); ?></option>
                            <option value="highest_uptime" <?php selected($sort_type, 'highest_uptime'); ?>><?php echo esc_html__('Highest Uptime', 'sapgs'); ?></option>
                        </select>
                        <?php if (!$license_info['is_premium']): ?>
                        <span class="sapgs-premium-badge" style="font-size: 11px; color: var(--sapgs-primary); font-weight: 600; margin-left: 8px;"><?php echo esc_html__('Premium Feature', 'sapgs'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="sapgs-gateways-grid">
                        <?php foreach ($all_gateways as $gateway_id => $gateway): 
                            $is_enabled = isset($enabled_gateways[$gateway_id]);
                            $is_default = $default_gateway && $default_gateway->get_id() === $gateway_id;
                            $raw_status = $statuses[$gateway_id] ?? 'not_configured';
                            $is_configured = $gateway->is_configured();
                            $config = $gateway->get_config();
                            // Check both test_mode and sandbox fields (different gateways use different field names)
                            $is_test_mode = false;
                            if (isset($config['test_mode'])) {
                                $is_test_mode = ($config['test_mode'] === '1' || $config['test_mode'] === true || $config['test_mode'] === 1 || $config['test_mode'] === 'on');
                            } elseif (isset($config['sandbox'])) {
                                $is_test_mode = ($config['sandbox'] === '1' || $config['sandbox'] === true || $config['sandbox'] === 1 || $config['sandbox'] === 'on');
                            }
                            
                            // If gateway is enabled and configured, show status based on test mode
                            // Test mode = orange, Live mode = green
                            // Otherwise use the actual status
                            if ($is_enabled && $is_configured) {
                                if ($is_test_mode) {
                                    $status = 'test_mode'; // Orange for test mode
                                } else {
                                    $status = 'connected'; // Green for live mode
                                }
                            } else {
                                $status = $raw_status;
                            }
                        ?>
                        <?php
                            $enabled_count = count($enabled_gateways);
                            $is_premium = $license_info['is_premium'];
                            $max_free_gateways = 1;
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
                                <div class="sapgs-gateway-header-right">
                                    <?php 
                                    $gateway_info = self::get_gateway_info($gateway_id);
                                    $tooltip_lines = array();
                                    
                                    if (!empty($gateway_info['strengths']) || !empty($gateway_info['weaknesses'])) {
                                        if (!empty($gateway_info['strengths'])) {
                                            $tooltip_lines[] = 'STRENGTHS:';
                                            foreach ($gateway_info['strengths'] as $strength) {
                                                $tooltip_lines[] = '• ' . $strength;
                                            }
                                        }
                                        if (!empty($gateway_info['weaknesses'])) {
                                            if (!empty($tooltip_lines)) {
                                                $tooltip_lines[] = '';
                                            }
                                            $tooltip_lines[] = 'WEAKNESSES:';
                                            foreach ($gateway_info['weaknesses'] as $weakness) {
                                                $tooltip_lines[] = '• ' . $weakness;
                                            }
                                        }
                                    } else {
                                        // Default tooltip if no specific info available
                                        $tooltip_lines[] = 'Gateway Information';
                                        $tooltip_lines[] = '';
                                        $tooltip_lines[] = 'This payment gateway is available for configuration.';
                                        $tooltip_lines[] = 'Configure the gateway to see detailed information.';
                                    }
                                    
                                    $tooltip_content = implode("\n", $tooltip_lines);
                                    ?>
                                    <span class="sapgs-gateway-info-icon" data-tooltip="<?php echo esc_attr($tooltip_content); ?>">i</span>
                                    <span class="sapgs-status-indicator status-<?php echo esc_attr($status); ?>"></span>
                                </div>
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
                                
                                <?php if ($is_configured): ?>
                                <span class="sapgs-badge <?php echo $is_test_mode ? 'sapgs-badge-warning' : 'sapgs-badge-success'; ?>">
                                    <?php echo $is_test_mode ? esc_html__('Test', 'sapgs') : esc_html__('Live', 'sapgs'); ?>
                                </span>
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
                                <div class="sapgs-performance-badge" style="margin-top: 15px; padding: 12px; border-radius: 8px; display: none;">
                                    <strong style="display: block; margin-bottom: 8px;"><?php echo esc_html__('Performance Badge:', 'sapgs'); ?></strong>
                                    <span class="sapgs-badge-text" style="display: inline-block; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 13px;"></span>
                                </div>
                                <div class="sapgs-test-message"></div>
                                <?php if (!$license_info['is_premium']): ?>
                                <div class="sapgs-test-receipt" style="margin-top: 20px; padding: 16px; background: var(--sapgs-bg); border-radius: 8px; border: 1px solid var(--sapgs-border); display: none;">
                                    <h4 style="margin-top: 0;"><?php echo esc_html__('Test Summary', 'sapgs'); ?></h4>
                                    <div class="sapgs-receipt-content"></div>
                                </div>
                                <?php endif; ?>
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
                
                <!-- Rankings Tab -->
                <div id="rankings" class="sapgs-tab-content">
                    <h2><?php echo esc_html__('Gateway Rankings', 'sapgs'); ?></h2>
                    <p><?php echo esc_html__('See how payment gateways compare based on performance metrics', 'sapgs'); ?></p>
                    
                    <div style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center;">
                        <label>
                            <input type="checkbox" id="sapgs-use-live-data" <?php checked($license_info['is_premium']); ?> <?php disabled(!$license_info['is_premium']); ?>>
                            <?php echo esc_html__('Use Live Store Data', 'sapgs'); ?>
                        </label>
                        <?php if (!$license_info['is_premium']): ?>
                        <span class="sapgs-premium-badge" style="font-size: 11px; color: var(--sapgs-primary); font-weight: 600;"><?php echo esc_html__('Premium Feature', 'sapgs'); ?></span>
                        <?php endif; ?>
                        <button class="button" id="sapgs-refresh-rankings"><?php echo esc_html__('Refresh Rankings', 'sapgs'); ?></button>
                    </div>
                    
                    <div id="sapgs-rankings-container" style="background: var(--sapgs-card-bg); border-radius: 12px; padding: 24px; box-shadow: var(--sapgs-shadow);">
                        <div class="sapgs-loading"><?php echo esc_html__('Loading rankings...', 'sapgs'); ?></div>
                    </div>
                </div>
                
                <!-- Failover Report Tab (Premium) -->
                <?php if ($license_info['is_premium']): ?>
                <div id="failover-report" class="sapgs-tab-content">
                    <h2><?php echo esc_html__('Failover Report', 'sapgs'); ?></h2>
                    <p><?php echo esc_html__('Track and analyze failover events to identify unreliable gateways', 'sapgs'); ?></p>
                    
                    <div style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center;">
                        <select id="sapgs-failover-days">
                            <option value="7"><?php echo esc_html__('Last 7 days', 'sapgs'); ?></option>
                            <option value="30" selected><?php echo esc_html__('Last 30 days', 'sapgs'); ?></option>
                            <option value="90"><?php echo esc_html__('Last 90 days', 'sapgs'); ?></option>
                        </select>
                        <button class="button button-primary" id="sapgs-load-failover-report"><?php echo esc_html__('Load Report', 'sapgs'); ?></button>
                    </div>
                    
                    <div id="sapgs-failover-report-container" style="background: var(--sapgs-card-bg); border-radius: 12px; padding: 24px; box-shadow: var(--sapgs-shadow);">
                        <p style="color: var(--sapgs-text-light);"><?php echo esc_html__('Click "Load Report" to view failover statistics', 'sapgs'); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Webhooks Tab -->
                <div id="webhooks" class="sapgs-tab-content">
                    <h2><?php echo esc_html__('Webhook Management', 'sapgs'); ?></h2>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                        <!-- Webhook Health Checks -->
                        <div style="background: var(--sapgs-card-bg); border-radius: 12px; padding: 20px; box-shadow: var(--sapgs-shadow);">
                            <h3 style="margin-top: 0;"><?php echo esc_html__('Webhook Health Checks', 'sapgs'); ?></h3>
                            <p style="color: var(--sapgs-text-light); font-size: 14px;"><?php echo esc_html__('Check if webhook endpoints are reachable and signatures are valid', 'sapgs'); ?></p>
                            <button class="button button-primary" id="sapgs-check-all-webhooks" style="margin-top: 15px;">
                                <?php echo esc_html__('Check All Gateways', 'sapgs'); ?>
                            </button>
                            <div id="sapgs-webhook-health-results" style="margin-top: 20px;"></div>
                        </div>
                        
                        <!-- Test Mode Webhook Listener (Free) -->
                        <div style="background: var(--sapgs-card-bg); border-radius: 12px; padding: 20px; box-shadow: var(--sapgs-shadow);">
                            <h3 style="margin-top: 0;"><?php echo esc_html__('Test Mode Webhook Listener', 'sapgs'); ?></h3>
                            <p style="color: var(--sapgs-text-light); font-size: 14px;"><?php echo esc_html__('View incoming webhook events in test mode', 'sapgs'); ?></p>
                            <button class="button" id="sapgs-load-webhook-events" style="margin-top: 15px;">
                                <?php echo esc_html__('View Recent Events', 'sapgs'); ?>
                            </button>
                            <div id="sapgs-webhook-events-list" style="margin-top: 20px; max-height: 400px; overflow-y: auto;"></div>
                        </div>
                    </div>
                    
                    <!-- Payment Simulation (Premium) -->
                    <?php if ($license_info['is_premium']): ?>
                    <div style="background: var(--sapgs-card-bg); border-radius: 12px; padding: 20px; box-shadow: var(--sapgs-shadow); margin-top: 20px;">
                        <h3 style="margin-top: 0;"><?php echo esc_html__('Checkout Payment Simulation', 'sapgs'); ?></h3>
                        <p style="color: var(--sapgs-text-light); font-size: 14px;"><?php echo esc_html__('Simulate a full checkout flow to test connectivity, API response, and webhook roundtrip', 'sapgs'); ?></p>
                        <div style="display: flex; gap: 12px; align-items: center; margin-top: 15px;">
                            <select id="sapgs-simulate-gateway" style="min-width: 200px;">
                                <?php foreach ($all_gateways as $gateway_id => $gateway): ?>
                                <option value="<?php echo esc_attr($gateway_id); ?>"><?php echo esc_html($gateway->get_name()); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" id="sapgs-simulate-amount" value="100.00" step="0.01" min="0" style="width: 120px;" placeholder="Amount">
                            <button class="button button-primary" id="sapgs-run-simulation">
                                <?php echo esc_html__('Run Simulation', 'sapgs'); ?>
                            </button>
                        </div>
                        <div id="sapgs-simulation-results" style="margin-top: 20px;"></div>
                    </div>
                    <?php endif; ?>
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
                    
                    <!-- Fee Comparison Section -->
                    <div class="sapgs-fee-comparison-section" style="background: var(--sapgs-card-bg); border-radius: 12px; padding: 24px; margin: 20px 0; box-shadow: var(--sapgs-shadow);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="margin: 0;"><?php echo esc_html__('Current Best Fees', 'sapgs'); ?></h3>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <label style="font-weight: 600; color: var(--sapgs-text);"><?php echo esc_html__('Test Amount:', 'sapgs'); ?></label>
                                <input type="number" id="sapgs-fee-test-amount" value="100" min="1" step="0.01" style="width: 120px; padding: 8px; border: 1px solid var(--sapgs-border); border-radius: 8px;">
                                <button class="button button-secondary" id="sapgs-check-fees-now"><?php echo esc_html__('Check Fees Now', 'sapgs'); ?></button>
                            </div>
                        </div>
                        <p style="color: var(--sapgs-text-secondary); margin: 0 0 20px 0; font-size: 14px;">
                            <?php echo esc_html__('Fees are automatically checked daily. Compare current fees across all payment gateways to find the best option for your transaction amount.', 'sapgs'); ?>
                        </p>
                        <div id="sapgs-fee-comparison-results" style="min-height: 200px;">
                            <div style="text-align: center; padding: 40px; color: var(--sapgs-text-secondary);">
                                <p><?php echo esc_html__('Loading fee comparison...', 'sapgs'); ?></p>
                            </div>
                        </div>
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
                <?php else: 
                    // Show benchmark preview for free users
                    $benchmarks = SAPGS_BenchmarkData::get_all_benchmarks();
                ?>
                <div id="analytics" class="sapgs-tab-content">
                    <div style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(124, 58, 237, 0.05) 100%); border: 2px dashed var(--sapgs-border); border-radius: 16px; padding: 30px; margin-bottom: 30px;">
                        <h2 style="margin-top: 0; color: var(--sapgs-text);"><?php echo esc_html__('Gateway Benchmark Preview', 'sapgs'); ?></h2>
                        <p style="color: var(--sapgs-text-light); margin-bottom: 20px;">
                            <?php echo esc_html__('Below are nationwide average benchmarks for South African payment gateways. Upgrade to Premium to see your store-specific analytics, real-time performance data, and historical trends.', 'sapgs'); ?>
                        </p>
                        <a href="#premium" class="button button-primary sapgs-tab-link" data-tab="premium" style="margin-top: 10px;"><?php echo esc_html__('Upgrade to Premium for Real Analytics', 'sapgs'); ?></a>
                    </div>
                    
                    <!-- Benchmark Success Rates -->
                    <div class="sapgs-chart-container" style="position: relative;">
                        <div class="sapgs-premium-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(2px); z-index: 10; display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                            <div style="text-align: center; padding: 20px;">
                                <span class="dashicons dashicons-lock" style="font-size: 32px; color: var(--sapgs-primary); margin-bottom: 10px;"></span>
                                <p style="font-weight: 600; color: var(--sapgs-text); margin: 10px 0;"><?php echo esc_html__('Premium Feature', 'sapgs'); ?></p>
                                <p style="color: var(--sapgs-text-light); font-size: 13px;"><?php echo esc_html__('Unlock real-time analytics', 'sapgs'); ?></p>
                            </div>
                        </div>
                        <h4><?php echo esc_html__('Average Success Rates (Nationwide Benchmark)', 'sapgs'); ?></h4>
                        <div class="sapgs-benchmark-list" style="margin-top: 20px;">
                            <?php foreach ($benchmarks['success_rates'] as $gateway_id => $data): 
                                $gateway = $gateway_manager->get_gateway($gateway_id);
                                if (!$gateway) continue;
                            ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid var(--sapgs-border);">
                                <div>
                                    <strong><?php echo esc_html($gateway->get_name()); ?></strong>
                                    <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--sapgs-text-light);"><?php echo esc_html($data['description']); ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <strong style="font-size: 18px; color: var(--sapgs-primary);"><?php echo esc_html($data['rate']); ?>%</strong>
                                    <p style="margin: 4px 0 0 0; font-size: 11px; color: var(--sapgs-text-light);"><?php echo esc_html($data['range']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Benchmark Response Times -->
                    <div class="sapgs-chart-container" style="position: relative;">
                        <div class="sapgs-premium-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(2px); z-index: 10; display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                            <div style="text-align: center; padding: 20px;">
                                <span class="dashicons dashicons-lock" style="font-size: 32px; color: var(--sapgs-primary); margin-bottom: 10px;"></span>
                                <p style="font-weight: 600; color: var(--sapgs-text); margin: 10px 0;"><?php echo esc_html__('Premium Feature', 'sapgs'); ?></p>
                                <p style="color: var(--sapgs-text-light); font-size: 13px;"><?php echo esc_html__('Unlock real-time analytics', 'sapgs'); ?></p>
                            </div>
                        </div>
                        <h4><?php echo esc_html__('Average Response Times (Nationwide Benchmark)', 'sapgs'); ?></h4>
                        <div class="sapgs-benchmark-list" style="margin-top: 20px;">
                            <?php foreach ($benchmarks['response_times'] as $gateway_id => $data): 
                                $gateway = $gateway_manager->get_gateway($gateway_id);
                                if (!$gateway) continue;
                            ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid var(--sapgs-border);">
                                <div>
                                    <strong><?php echo esc_html($gateway->get_name()); ?></strong>
                                    <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--sapgs-text-light);"><?php echo esc_html($data['description']); ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <strong style="font-size: 18px; color: var(--sapgs-primary);"><?php echo esc_html($data['avg']); ?>ms</strong>
                                    <p style="margin: 4px 0 0 0; font-size: 11px; color: var(--sapgs-text-light);"><?php echo esc_html($data['range']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Benchmark Fee Ranges -->
                    <div class="sapgs-chart-container" style="position: relative;">
                        <div class="sapgs-premium-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(2px); z-index: 10; display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                            <div style="text-align: center; padding: 20px;">
                                <span class="dashicons dashicons-lock" style="font-size: 32px; color: var(--sapgs-primary); margin-bottom: 10px;"></span>
                                <p style="font-weight: 600; color: var(--sapgs-text); margin: 10px 0;"><?php echo esc_html__('Premium Feature', 'sapgs'); ?></p>
                                <p style="color: var(--sapgs-text-light); font-size: 13px;"><?php echo esc_html__('Unlock real-time analytics', 'sapgs'); ?></p>
                            </div>
                        </div>
                        <h4><?php echo esc_html__('Typical Fee Ranges (Nationwide Benchmark)', 'sapgs'); ?></h4>
                        <div class="sapgs-benchmark-list" style="margin-top: 20px;">
                            <?php foreach ($benchmarks['fee_ranges'] as $gateway_id => $data): 
                                $gateway = $gateway_manager->get_gateway($gateway_id);
                                if (!$gateway) continue;
                            ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid var(--sapgs-border);">
                                <div>
                                    <strong><?php echo esc_html($gateway->get_name()); ?></strong>
                                    <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--sapgs-text-light);"><?php echo esc_html($data['description']); ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <strong style="font-size: 14px; color: var(--sapgs-primary);"><?php echo esc_html($data['percentage']); ?></strong>
                                    <p style="margin: 4px 0 0 0; font-size: 11px; color: var(--sapgs-text-light);">+ <?php echo esc_html($data['fixed']); ?> fixed</p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
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
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ 1 active gateway (can switch)</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Last 20 transaction logs</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Basic gateway testing</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Gateway configuration</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Benchmark preview</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Basic uptime status</li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Manual fee calculator</li>
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
                                    <li style="padding: 8px 0; border-bottom: 1px solid var(--sapgs-border);">✓ Daily fee monitoring & comparison</li>
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
                    
                    <!-- Before Going Live Checklist (Free) -->
                    <div class="sapgs-live-checklist" style="margin-bottom: 30px; padding: 20px; background: var(--sapgs-card-bg); border-radius: 12px; border: 1px solid var(--sapgs-border);">
                        <h3 style="margin-top: 0;"><?php echo esc_html__('Before Going Live Checklist', 'sapgs'); ?></h3>
                        <p style="color: var(--sapgs-text-light);"><?php echo esc_html__('Verify your store is ready for production payments', 'sapgs'); ?></p>
                        <button class="button button-primary" id="sapgs-run-live-checklist" style="margin-top: 15px;">
                            <?php echo esc_html__('Run Checklist', 'sapgs'); ?>
                        </button>
                        <div id="sapgs-live-checklist-results" style="margin-top: 20px;"></div>
                    </div>
                    
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
                    
                    <!-- Routing Preview for Free Users -->
                    <?php if (!$license_info['is_premium']): ?>
                    <div class="sapgs-routing-preview" style="margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%); border: 2px dashed var(--sapgs-border); border-radius: 12px;">
                        <h3 style="margin-top: 0;"><?php echo esc_html__('Routing Modes (Premium Feature)', 'sapgs'); ?></h3>
                        <p><?php echo esc_html__('Upgrade to Premium to unlock intelligent routing modes:', 'sapgs'); ?></p>
                        <div style="margin-top: 20px;">
                            <div style="padding: 16px; background: white; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid var(--sapgs-primary);">
                                <strong><?php echo esc_html__('Default (Primary + Failover)', 'sapgs'); ?></strong>
                                <p style="margin: 8px 0 0 0; color: var(--sapgs-text-light); font-size: 14px;"><?php echo esc_html__('Uses your default gateway first. If it fails, automatically tries backup gateways in order. Ensures payments continue even if one gateway is down.', 'sapgs'); ?></p>
                            </div>
                            <div style="padding: 16px; background: white; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid var(--sapgs-primary);">
                                <strong><?php echo esc_html__('Highest Approval Rate', 'sapgs'); ?></strong>
                                <p style="margin: 8px 0 0 0; color: var(--sapgs-text-light); font-size: 14px;"><?php echo esc_html__('Automatically routes payments to the gateway with the highest success rate based on your store\'s historical data. Maximizes successful transactions.', 'sapgs'); ?></p>
                            </div>
                            <div style="padding: 16px; background: white; border-radius: 8px; border-left: 4px solid var(--sapgs-primary);">
                                <strong><?php echo esc_html__('Load Balancing', 'sapgs'); ?></strong>
                                <p style="margin: 8px 0 0 0; color: var(--sapgs-text-light); font-size: 14px;"><?php echo esc_html__('Distributes payments evenly across all enabled gateways. Prevents overloading a single gateway and provides redundancy.', 'sapgs'); ?></p>
                            </div>
                        </div>
                        <a href="#premium" class="button button-primary sapgs-tab-link" data-tab="premium" style="margin-top: 20px;"><?php echo esc_html__('Upgrade to Premium', 'sapgs'); ?></a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Manual Fee Calculator for Free Users -->
                    <?php if (!$license_info['is_premium']): ?>
                    <div class="sapgs-fee-calculator" style="margin-bottom: 30px; padding: 20px; background: var(--sapgs-card-bg); border-radius: 12px; border: 1px solid var(--sapgs-border);">
                        <h3 style="margin-top: 0;"><?php echo esc_html__('Fee Calculator', 'sapgs'); ?></h3>
                        <p style="color: var(--sapgs-text-light);"><?php echo esc_html__('Manually calculate and compare fees across gateways. Premium users get automatic fee tracking and historical data.', 'sapgs'); ?></p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600;"><?php echo esc_html__('Transaction Amount (R)', 'sapgs'); ?></label>
                                <input type="number" id="sapgs-calc-amount" value="100" step="0.01" min="0" style="width: 100%; padding: 10px; border: 1px solid var(--sapgs-border); border-radius: 8px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600;"><?php echo esc_html__('Select Gateway', 'sapgs'); ?></label>
                                <select id="sapgs-calc-gateway" style="width: 100%; padding: 10px; border: 1px solid var(--sapgs-border); border-radius: 8px;">
                                    <?php foreach ($all_gateways as $gateway_id => $gateway): ?>
                                    <option value="<?php echo esc_attr($gateway_id); ?>"><?php echo esc_html($gateway->get_name()); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top: 20px;">
                            <button type="button" class="button button-primary" id="sapgs-calculate-fee"><?php echo esc_html__('Calculate Fee', 'sapgs'); ?></button>
                            <div id="sapgs-fee-result" style="margin-top: 20px; padding: 16px; background: var(--sapgs-bg); border-radius: 8px; display: none;">
                                <strong><?php echo esc_html__('Fee Breakdown:', 'sapgs'); ?></strong>
                                <div id="sapgs-fee-breakdown" style="margin-top: 10px;"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Setup Audit Checklist for Free Users -->
                    <div class="sapgs-setup-audit" style="margin-bottom: 30px; padding: 20px; background: var(--sapgs-card-bg); border-radius: 12px; border: 1px solid var(--sapgs-border);">
                        <h3 style="margin-top: 0;"><?php echo esc_html__('Setup Audit Checklist', 'sapgs'); ?></h3>
                        <p style="color: var(--sapgs-text-light);"><?php echo esc_html__('Verify your store is properly configured for payment processing:', 'sapgs'); ?></p>
                        <div style="margin-top: 20px;">
                            <?php
                            $ssl_active = is_ssl();
                            $woocommerce_active = class_exists('WooCommerce');
                            $currency_zar = false;
                            $current_currency = 'N/A';
                            if ($woocommerce_active && function_exists('get_woocommerce_currency')) {
                                $current_currency = get_woocommerce_currency();
                                $currency_zar = $current_currency === 'ZAR';
                            }
                            ?>
                            <div style="display: flex; align-items: center; padding: 12px; border-bottom: 1px solid var(--sapgs-border);">
                                <span class="dashicons <?php echo $ssl_active ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>" style="color: <?php echo $ssl_active ? '#10b981' : '#ef4444'; ?>; margin-right: 12px; font-size: 20px;"></span>
                                <div style="flex: 1;">
                                    <strong><?php echo esc_html__('SSL Certificate Active', 'sapgs'); ?></strong>
                                    <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--sapgs-text-light);"><?php echo $ssl_active ? esc_html__('Your site is using HTTPS', 'sapgs') : esc_html__('SSL is required for payment processing', 'sapgs'); ?></p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; padding: 12px; border-bottom: 1px solid var(--sapgs-border);">
                                <span class="dashicons <?php echo $woocommerce_active ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>" style="color: <?php echo $woocommerce_active ? '#10b981' : '#ef4444'; ?>; margin-right: 12px; font-size: 20px;"></span>
                                <div style="flex: 1;">
                                    <strong><?php echo esc_html__('WooCommerce Active', 'sapgs'); ?></strong>
                                    <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--sapgs-text-light);"><?php echo $woocommerce_active ? esc_html__('WooCommerce is installed and active', 'sapgs') : esc_html__('WooCommerce is required', 'sapgs'); ?></p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; padding: 12px; border-bottom: 1px solid var(--sapgs-border);">
                                <span class="dashicons <?php echo $currency_zar ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" style="color: <?php echo $currency_zar ? '#10b981' : '#f59e0b'; ?>; margin-right: 12px; font-size: 20px;"></span>
                                <div style="flex: 1;">
                                    <strong><?php echo esc_html__('Currency: ZAR', 'sapgs'); ?></strong>
                                    <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--sapgs-text-light);"><?php echo $currency_zar ? esc_html__('Store currency is set to ZAR', 'sapgs') : sprintf(esc_html__('Current currency: %s (ZAR recommended)', 'sapgs'), $current_currency); ?></p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; padding: 12px;">
                                <span class="dashicons dashicons-info" style="color: var(--sapgs-primary); margin-right: 12px; font-size: 20px;"></span>
                                <div style="flex: 1;">
                                    <strong><?php echo esc_html__('Webhooks Configured', 'sapgs'); ?></strong>
                                    <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--sapgs-text-light);"><?php echo esc_html__('Configure webhooks in your gateway dashboard for payment notifications', 'sapgs'); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php if (!$license_info['is_premium']): ?>
                        <p style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--sapgs-border); color: var(--sapgs-text-light); font-size: 13px;">
                            <?php echo esc_html__('Premium users get deeper diagnostics, recommendations, and automated webhook configuration.', 'sapgs'); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Basic Uptime Status for Free Users -->
                    <?php if (!$license_info['is_premium']): ?>
                    <div class="sapgs-uptime-status" style="margin-bottom: 30px; padding: 20px; background: var(--sapgs-card-bg); border-radius: 12px; border: 1px solid var(--sapgs-border);">
                        <h3 style="margin-top: 0;"><?php echo esc_html__('Current Gateway Status', 'sapgs'); ?></h3>
                        <p style="color: var(--sapgs-text-light);"><?php echo esc_html__('Live status of your enabled gateway. Premium users get historical uptime data, downtime patterns, and alerts.', 'sapgs'); ?></p>
                        <div id="sapgs-uptime-status-list" style="margin-top: 20px;">
                            <?php 
                            $enabled_gateways = $gateway_manager->get_enabled_gateways();
                            if (empty($enabled_gateways)): 
                            ?>
                            <p style="color: var(--sapgs-text-light);"><?php echo esc_html__('No gateways enabled. Enable a gateway to see status.', 'sapgs'); ?></p>
                            <?php else: 
                                foreach ($enabled_gateways as $gateway_id => $gateway):
                                    $test_result = $gateway->test_connection();
                                    $is_up = $test_result['success'] ?? false;
                            ?>
                            <div style="display: flex; align-items: center; padding: 12px; border-bottom: 1px solid var(--sapgs-border);">
                                <span class="sapgs-status-indicator status-<?php echo $is_up ? 'connected' : 'offline'; ?>" style="margin-right: 12px;"></span>
                                <div style="flex: 1;">
                                    <strong><?php echo esc_html($gateway->get_name()); ?></strong>
                                    <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--sapgs-text-light);">
                                        <?php echo $is_up ? esc_html__('Online - Gateway is responding', 'sapgs') : esc_html__('Offline - Gateway is not responding', 'sapgs'); ?>
                                    </p>
                                </div>
                            </div>
                            <?php 
                                endforeach;
                            endif; 
                            ?>
                        </div>
                        <button type="button" class="button" id="sapgs-refresh-uptime" style="margin-top: 15px;"><?php echo esc_html__('Refresh Status', 'sapgs'); ?></button>
                    </div>
                    
                    <!-- Simulated Failover Demo for Free Users -->
                    <div class="sapgs-failover-demo" style="margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%); border: 2px dashed var(--sapgs-border); border-radius: 12px;">
                        <h3 style="margin-top: 0;"><?php echo esc_html__('Automatic Failover Demo (Simulated)', 'sapgs'); ?></h3>
                        <p style="color: var(--sapgs-text-light);"><?php echo esc_html__('See how automatic failover works when your primary gateway fails. This is a simulation using sample data. Premium users get real automatic failover with multiple gateways.', 'sapgs'); ?></p>
                        <button type="button" class="button button-primary" id="sapgs-run-failover-demo" style="margin-top: 15px;"><?php echo esc_html__('Run Failover Demo', 'sapgs'); ?></button>
                        <div id="sapgs-failover-demo-result" style="margin-top: 20px; display: none; padding: 16px; background: white; border-radius: 8px; border: 1px solid var(--sapgs-border);">
                            <h4 style="margin-top: 0;"><?php echo esc_html__('Simulated Payment Flow:', 'sapgs'); ?></h4>
                            <div id="sapgs-failover-demo-steps" style="font-size: 14px; line-height: 2;"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form id="sapgs-settings-form" method="post">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <?php echo esc_html__('Failover Routing', 'sapgs'); ?>
                                    <span class="sapgs-help-tooltip" data-tooltip="<?php echo esc_attr__('If the primary gateway fails, automatically try backup gateways in order. This helps ensure payment processing continues even if one gateway is down.', 'sapgs'); ?>">?</span>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="sapgs_failover_enabled" id="sapgs_failover_enabled" value="1" 
                                               <?php checked(get_option('sapgs_failover_enabled', false)); ?>
                                               <?php disabled(!$license_info['is_premium']); ?>>
                                        <?php echo esc_html__('Enable automatic failover to backup gateway', 'sapgs'); ?>
                                        <?php if (!$license_info['is_premium']): ?>
                                        <span class="sapgs-premium-badge" style="font-size: 11px; color: var(--sapgs-primary); font-weight: 600; margin-left: 8px;"><?php echo esc_html__('Premium', 'sapgs'); ?></span>
                                        <?php endif; ?>
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
                                    <select name="sapgs_routing_mode" id="sapgs_routing_mode">
                                        <option value="default" <?php selected(get_option('sapgs_routing_mode', 'default'), 'default'); ?>>
                                            <?php echo esc_html__('Default (Primary + Failover)', 'sapgs'); ?>
                                        </option>
                                        <option value="approval_rate" <?php selected(get_option('sapgs_routing_mode', 'default'), 'approval_rate'); ?>>
                                            <?php echo esc_html__('Highest Approval Rate', 'sapgs'); ?>
                                        </option>
                                        <option value="load_balance" <?php selected(get_option('sapgs_routing_mode', 'default'), 'load_balance'); ?>>
                                            <?php echo esc_html__('Load Balancing', 'sapgs'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary" id="sapgs-save-settings"><?php echo esc_html__('Save Settings', 'sapgs'); ?></button>
                            <span id="sapgs-settings-message" style="margin-left: 15px;"></span>
                        </p>
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

