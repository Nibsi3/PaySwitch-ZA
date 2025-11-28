<?php
/**
 * Dashboard Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_Dashboard {
    
    public static function render() {
        $plugin = SA_Payment_Gateway_Switcher::get_instance();
        $gateway_manager = $plugin->gateway_manager;
        $license_manager = $plugin->license_manager;
        
        $all_gateways = $gateway_manager->get_all_gateways();
        $enabled_gateways = $gateway_manager->get_enabled_gateways();
        $default_gateway = $gateway_manager->get_default_gateway();
        $statuses = $gateway_manager->get_all_statuses();
        $license_info = $license_manager->get_license_info();
        
        ?>
        <div class="wrap sapgs-dashboard">
            <h1><?php echo esc_html__('SA Payment Gateway Switcher', 'sapgs'); ?></h1>
            
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
                    <div class="sapgs-gateways-grid">
                        <?php foreach ($all_gateways as $gateway_id => $gateway): 
                            $is_enabled = isset($enabled_gateways[$gateway_id]);
                            $is_default = $default_gateway && $default_gateway->get_id() === $gateway_id;
                            $status = $statuses[$gateway_id] ?? 'not_configured';
                            $is_configured = $gateway->is_configured();
                        ?>
                        <div class="sapgs-gateway-card" data-gateway-id="<?php echo esc_attr($gateway_id); ?>">
                            <div class="sapgs-gateway-header">
                                <h3><?php echo esc_html($gateway->get_name()); ?></h3>
                                <span class="sapgs-status-indicator status-<?php echo esc_attr($status); ?>"></span>
                            </div>
                            <p class="sapgs-gateway-desc"><?php echo esc_html($gateway->get_description()); ?></p>
                            
                            <div class="sapgs-gateway-actions">
                                <label class="sapgs-toggle">
                                    <input type="checkbox" class="sapgs-enable-gateway" 
                                           data-gateway-id="<?php echo esc_attr($gateway_id); ?>"
                                           <?php checked($is_enabled); ?>
                                           <?php disabled(!$is_configured); ?>>
                                    <span class="sapgs-toggle-slider"></span>
                                    <span class="sapgs-toggle-label"><?php echo $is_enabled ? __('Enabled', 'sapgs') : __('Disabled', 'sapgs'); ?></span>
                                </label>
                                
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
                            <canvas id="sapgs-chart-fees"></canvas>
                        </div>
                        <div class="sapgs-chart-container">
                            <canvas id="sapgs-chart-success-rates"></canvas>
                        </div>
                        <div class="sapgs-chart-container">
                            <canvas id="sapgs-chart-response-times"></canvas>
                        </div>
                        <div class="sapgs-chart-container">
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
                        <h3><?php echo esc_html__('Activate Premium License', 'sapgs'); ?></h3>
                        <p><?php echo esc_html__('Enter your license key to unlock premium features:', 'sapgs'); ?></p>
                        <ul>
                            <li><?php echo esc_html__('Multiple gateways enabled simultaneously', 'sapgs'); ?></li>
                            <li><?php echo esc_html__('Advanced analytics and comparison charts', 'sapgs'); ?></li>
                            <li><?php echo esc_html__('Automatic failover routing', 'sapgs'); ?></li>
                            <li><?php echo esc_html__('Unlimited transaction logs', 'sapgs'); ?></li>
                            <li><?php echo esc_html__('Load balancing', 'sapgs'); ?></li>
                            <li><?php echo esc_html__('Scheduled daily tests', 'sapgs'); ?></li>
                        </ul>
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
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('sapgs_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php echo esc_html__('Failover Routing', 'sapgs'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="sapgs_failover_enabled" value="1" 
                                               <?php checked(get_option('sapgs_failover_enabled', false)); ?>
                                               <?php disabled(!$license_info['is_premium']); ?>>
                                        <?php echo esc_html__('Enable automatic failover to backup gateway', 'sapgs'); ?>
                                    </label>
                                    <?php if (!$license_info['is_premium']): ?>
                                    <p class="description"><?php echo esc_html__('This is a premium feature', 'sapgs'); ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Gateway Configuration Modal -->
        <div id="sapgs-config-modal" class="sapgs-modal" style="display: none;">
            <div class="sapgs-modal-content">
                <span class="sapgs-modal-close">&times;</span>
                <h2 id="sapgs-config-modal-title"><?php echo esc_html__('Configure Gateway', 'sapgs'); ?></h2>
                <form id="sapgs-config-form">
                    <div id="sapgs-config-fields"></div>
                    <input type="hidden" id="sapgs-config-gateway-id">
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php echo esc_html__('Save Configuration', 'sapgs'); ?></button>
                        <button type="button" class="button sapgs-modal-close"><?php echo esc_html__('Cancel', 'sapgs'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
}

