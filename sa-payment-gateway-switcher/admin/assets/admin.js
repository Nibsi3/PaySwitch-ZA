/**
 * PaySwitch ZA Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Ensure jQuery is available - use window.jQuery as fallback
    if (typeof $ === 'undefined') {
        if (typeof jQuery !== 'undefined') {
            $ = jQuery;
        } else if (typeof window.jQuery !== 'undefined') {
            $ = window.jQuery;
        } else {
            // jQuery not available, but don't break - just log and continue
            console.warn('jQuery may not be fully loaded');
            // Try to use window.$ as last resort
            if (typeof window.$ !== 'undefined') {
                $ = window.$;
            }
        }
    }
    
    $(document).ready(function() {
        try {
        
        // Tab switching with smooth transitions
        $('.sapgs-tab-link').on('click', function(e) {
            e.preventDefault();
            var $clickedLink = $(this);
            var tab = $clickedLink.data('tab');
            var $targetTab = $('#' + tab);
            var $activeTab = $('.sapgs-tab-content.active');
            
            // Don't do anything if clicking the same tab
            if ($clickedLink.hasClass('active')) {
                return;
            }
            
            // Remove active class from all tabs and links immediately
            $('.sapgs-tab-link').removeClass('active');
            $('.sapgs-tab-content').removeClass('active');
            
            // Add active class to clicked link immediately
            $clickedLink.addClass('active');
            
            // Hide current tab and show new one
            if ($activeTab.length) {
                $activeTab.fadeOut(150, function() {
                    $targetTab.addClass('active').fadeIn(200);
                });
            } else {
                $targetTab.addClass('active').fadeIn(200);
            }
        });
        
        // Toggle gateway
        $('.sapgs-enable-gateway').on('change', function() {
            var gatewayId = $(this).data('gateway-id');
            var enabled = $(this).is(':checked');
            var $card = $(this).closest('.sapgs-gateway-card');
            
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_toggle_gateway',
                    gateway_id: gatewayId,
                    enabled: enabled,
                    nonce: sapgsData.nonce
                },
                beforeSend: function() {
                    $card.css({
                        'opacity': '0.6',
                        'pointer-events': 'none'
                    }).addClass('sapgs-loading');
                },
                success: function(response) {
                    if (response.success) {
                        // Update card data attribute
                        $card.data('enabled', enabled);
                        
                        // Update status indicator
                        if (enabled) {
                            $card.find('.sapgs-status-indicator')
                                .removeClass('status-offline status-not_configured status-intermittent')
                                .addClass('status-connected');
                        } else {
                            // If disabled, revert to original status
                            var originalStatus = $card.data('original-status') || 'not_configured';
                            $card.find('.sapgs-status-indicator')
                                .removeClass('status-connected status-offline status-not_configured status-intermittent')
                                .addClass('status-' + originalStatus);
                        }
                        
                        // Update toggle label
                        var $toggleLabel = $card.find('.sapgs-toggle-label');
                        $toggleLabel.text(enabled ? 'Enabled' : 'Disabled');
                        
                        // Show/hide "Set as Default" button
                        var $setDefaultBtn = $card.find('.sapgs-set-default');
                        var isDefault = $card.find('.sapgs-badge-primary').length > 0;
                        
                        if (enabled && !isDefault) {
                            // Show "Set as Default" button if not already default
                            if ($setDefaultBtn.length === 0) {
                                var $actions = $card.find('.sapgs-gateway-actions');
                                $actions.append('<button class="button sapgs-set-default" data-gateway-id="' + gatewayId + '">Set as Default</button>');
                            } else {
                                $setDefaultBtn.show();
                            }
                        } else {
                            $setDefaultBtn.hide();
                        }
                        
                        // Update premium badge visibility
                        var enabledCount = response.data?.enabled_count || 0;
                        var isPremium = response.data?.is_premium || false;
                        var maxFree = response.data?.max_free || 1;
                        
                        var $premiumBadge = $card.find('.sapgs-premium-badge');
                        if (!isPremium && !enabled && enabledCount >= maxFree) {
                            if ($premiumBadge.length === 0) {
                                var $actions = $card.find('.sapgs-gateway-actions');
                                $actions.append('<span class="sapgs-premium-badge" style="font-size: 11px; color: var(--sapgs-primary); font-weight: 600;">Premium</span>');
                            } else {
                                $premiumBadge.show();
                            }
                        } else {
                            $premiumBadge.hide();
                        }
                        
                        // Restore card appearance
                        $card.css({
                            'opacity': '1',
                            'pointer-events': 'auto',
                            'transform': 'scale(1)'
                        }).removeClass('sapgs-loading');
                        
                        // Show success notification
                        showNotification(enabled ? 'Gateway enabled successfully!' : 'Gateway disabled successfully!', 'success');
                    } else {
                        $card.css({
                            'opacity': '1',
                            'pointer-events': 'auto'
                        }).removeClass('sapgs-loading');
                        
                        var errorMsg = response.data?.message || 'Failed to toggle gateway';
                        
                        // If limit reached, show upgrade prompt
                        if (response.data?.limit_reached) {
                            errorMsg += '<br><br><a href="#premium" class="sapgs-tab-link" data-tab="premium" style="color: white; text-decoration: underline;">Upgrade to Premium</a> to enable all gateways.';
                        }
                        
                        showNotification(errorMsg, 'error');
                    }
                },
                error: function() {
                    $card.css({
                        'opacity': '1',
                        'pointer-events': 'auto'
                    }).removeClass('sapgs-loading');
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        });
        
        // Set default gateway
        $('.sapgs-set-default').on('click', function() {
            var gatewayId = $(this).data('gateway-id');
            var $button = $(this);
            
            if (!confirm('Set this gateway as the default payment gateway?')) {
                return;
            }
            
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_set_default',
                    gateway_id: gatewayId,
                    nonce: sapgsData.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true)
                        .html('<span class="sapgs-loading-spinner"></span> Setting...')
                        .addClass('sapgs-loading');
                },
                success: function(response) {
                    if (response.success) {
                        // Remove default badge from all cards
                        $('.sapgs-gateway-card').each(function() {
                            var $card = $(this);
                            $card.find('.sapgs-badge-primary').remove();
                            
                            // Show "Set as Default" button if gateway is enabled
                            var isEnabled = $card.data('enabled') === true || $card.data('enabled') === 'true';
                            if (isEnabled && $card.data('gateway-id') !== gatewayId) {
                                var $setDefaultBtn = $card.find('.sapgs-set-default');
                                if ($setDefaultBtn.length === 0) {
                                    var $actions = $card.find('.sapgs-gateway-actions');
                                    $actions.append('<button class="button sapgs-set-default" data-gateway-id="' + $card.data('gateway-id') + '">Set as Default</button>');
                                } else {
                                    $setDefaultBtn.show();
                                }
                            }
                        });
                        
                        // Add default badge to selected gateway
                        var $selectedCard = $('.sapgs-gateway-card[data-gateway-id="' + gatewayId + '"]');
                        var $actions = $selectedCard.find('.sapgs-gateway-actions');
                        
                        // Remove "Set as Default" button
                        $selectedCard.find('.sapgs-set-default').remove();
                        
                        // Add default badge
                        if ($selectedCard.find('.sapgs-badge-primary').length === 0) {
                            $actions.prepend('<span class="sapgs-badge sapgs-badge-primary">Default</span>');
                        }
                        
                        // Restore button state
                        $button.prop('disabled', false)
                            .text('Set as Default')
                            .removeClass('sapgs-loading');
                        
                        showNotification('Default gateway updated successfully!', 'success');
                    } else {
                        showNotification(response.data.message || 'Failed to set default gateway', 'error');
                        $button.prop('disabled', false).text('Set as Default').removeClass('sapgs-loading');
                    }
                },
                error: function() {
                    showNotification('An error occurred. Please try again.', 'error');
                    $button.prop('disabled', false).text('Set as Default').removeClass('sapgs-loading');
                }
            });
        });
        
        // Test gateway
        $('.sapgs-test-gateway').on('click', function() {
            var gatewayId = $(this).data('gateway-id');
            var $card = $(this).closest('.sapgs-test-card');
            var $result = $card.find('.sapgs-test-result');
            var $button = $(this);
            
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_test_gateway',
                    gateway_id: gatewayId,
                    nonce: sapgsData.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true)
                        .html('<span class="sapgs-loading-spinner"></span> Testing...')
                        .addClass('sapgs-loading');
                    $result.hide();
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Run Test').removeClass('sapgs-loading');
                    
                    if (response.success && response.data) {
                        var data = response.data;
                        var statusColor = data.success ? '#10B981' : '#EF4444';
                        var statusText = data.success ? '✓ Success' : '✗ Failed';
                        
                        $card.find('.sapgs-test-status')
                            .text(statusText)
                            .css('color', statusColor);
                        $card.find('.sapgs-test-response-time').text(data.response_time + 'ms');
                        $card.find('.sapgs-test-health-score').text(data.health_score + '/100');
                        $card.find('.sapgs-test-message').text(data.message || '');
                        
                        // Performance Badge
                        var $badgeContainer = $card.find('.sapgs-performance-badge');
                        var $badgeText = $card.find('.sapgs-badge-text');
                        if ($badgeContainer.length && $badgeText.length) {
                            var badgeType = 'pass';
                            var badgeText = 'Pass';
                            var badgeColor = '#10B981';
                            
                            if (!data.success) {
                                badgeType = 'fail';
                                badgeText = 'Fail';
                                badgeColor = '#EF4444';
                            } else if (data.health_score < 70 || data.response_time > 1000) {
                                badgeType = 'warning';
                                badgeText = 'Warning';
                                badgeColor = '#F59E0B';
                            }
                            
                            $badgeText.text(badgeText)
                                .css({
                                    'background-color': badgeColor,
                                    'color': 'white'
                                });
                            $badgeContainer.show();
                        }
                        
                        // Test Receipt for Free Users
                        var $receipt = $card.find('.sapgs-test-receipt');
                        var $receiptContent = $card.find('.sapgs-receipt-content');
                        if ($receipt.length && $receiptContent.length && !sapgsData.isPremium) {
                            var receiptHtml = '<div style="font-size: 13px; line-height: 1.8;">';
                            receiptHtml += '<div><strong>Gateway:</strong> ' + gatewayId + '</div>';
                            receiptHtml += '<div><strong>Test Date:</strong> ' + new Date().toLocaleString() + '</div>';
                            receiptHtml += '<div><strong>Status:</strong> <span style="color: ' + statusColor + ';">' + statusText + '</span></div>';
                            receiptHtml += '<div><strong>Response Time:</strong> ' + data.response_time + 'ms</div>';
                            receiptHtml += '<div><strong>Health Score:</strong> ' + data.health_score + '/100</div>';
                            if (data.message) {
                                receiptHtml += '<div><strong>Message:</strong> ' + data.message + '</div>';
                            }
                            receiptHtml += '</div>';
                            $receiptContent.html(receiptHtml);
                            $receipt.show();
                        }
                        
                        // Animate result appearance
                        $result.slideDown(300);
                        
                        // Add success/fail class to card
                        $card.removeClass('sapgs-test-success sapgs-test-failed');
                        $card.addClass(data.success ? 'sapgs-test-success' : 'sapgs-test-failed');
                    } else {
                        showNotification(response.data?.message || 'Test failed', 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Run Test').removeClass('sapgs-loading');
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        });
        
        // Configure gateway - use event delegation for dynamically loaded content
        $(document).on('click', '.sapgs-configure-gateway', function() {
            var gatewayId = $(this).data('gateway-id');
            if (gatewayId) {
            openConfigModal(gatewayId);
            }
        });
        
        // Modal close - click outside to close
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('sapgs-modal')) {
                closeConfigModal();
            }
        });
        
        // ESC key to close modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#sapgs-config-modal').is(':visible')) {
                closeConfigModal();
            }
        });
        
        // Function to close modal and restore original config if not saved
        function closeConfigModal() {
            var configSaved = $('#sapgs-config-modal').data('config-saved');
            var gatewayId = $('#sapgs-config-gateway-id').val();
            var originalConfig = $('#sapgs-config-modal').data('original-config');
            
            // If config was not successfully saved, restore original config
            if (!configSaved && gatewayId && originalConfig) {
                $.ajax({
                    url: sapgsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sapgs_save_gateway_config',
                        gateway_id: gatewayId,
                        config: JSON.stringify(originalConfig),
                        nonce: sapgsData.nonce,
                        skip_validation: 'true' // Skip validation when restoring
                    },
                    async: false // Synchronous to ensure restore happens before modal closes
                });
            }
            
            // Clear modal data
            $('#sapgs-config-modal').removeData('config-saved');
            $('#sapgs-config-modal').removeData('current-gateway-id');
            $('#sapgs-config-modal').removeData('original-config');
            $('#sapgs-config-modal').removeData('original-fields');
            
            $('#sapgs-config-modal').hide();
        }
        
        // Store close function reference
        $('#sapgs-config-modal').data('close-function', closeConfigModal);
        
        // Load logs
        // Load logs function
        function loadLogs(gatewayId, limit) {
            limit = limit || (sapgsData.isPremium ? 50 : 20);
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_get_logs',
                    gateway_id: gatewayId || '',
                    limit: limit,
                    nonce: sapgsData.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        renderLogs(response.data);
                    }
                }
            });
        }
        
        function renderLogs(logs) {
            var $tbody = $('#sapgs-logs-tbody');
            $tbody.empty();
            
            if (logs.length === 0) {
                $tbody.html('<tr><td colspan="7" class="sapgs-loading">No logs found</td></tr>');
                return;
            }
            
            logs.forEach(function(log) {
                var statusClass = log.status === 'success' ? 'success' : 'failed';
                var statusText = log.status === 'success' ? 'Success' : 'Failed';
                var row = '<tr>' +
                    '<td>' + log.created_at + '</td>' +
                    '<td>' + log.gateway_id + '</td>' +
                    '<td>' + (log.transaction_id || '-') + '</td>' +
                    '<td>' + (log.order_id || '-') + '</td>' +
                    '<td>R ' + parseFloat(log.amount || 0).toFixed(2) + '</td>' +
                    '<td><span class="status-' + statusClass + '">' + statusText + '</span></td>' +
                    '<td>' + (log.response_time ? log.response_time + 'ms' : '-') + '</td>' +
                    '</tr>';
                $tbody.append(row);
            });
        }
        
        // Logs filter
        $('#sapgs-logs-gateway-filter').on('change', function() {
            loadLogs($(this).val());
        });
        
        // Load logs on tab show
        $('.sapgs-tab-link[data-tab="logs"]').on('click', function() {
            setTimeout(function() {
                var limit = sapgsData.isPremium ? 50 : 20; // Free users limited to 20
                loadLogs($('#sapgs-logs-gateway-filter').val(), limit);
            }, 100);
        });
        
        // License activation
        $('#sapgs-activate-license-form').on('submit', function(e) {
            e.preventDefault();
            var licenseKey = $('#sapgs-license-key').val();
            var $message = $('#sapgs-license-message');
            var $form = $(this);
            
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_activate_license',
                    license_key: licenseKey,
                    nonce: sapgsData.nonce
                },
                beforeSend: function() {
                    $form.find('button').prop('disabled', true).text('Activating...');
                    $message.removeClass('success error').text('');
                },
                success: function(response) {
                    $form.find('button').prop('disabled', false).text('Activate');
                    
                    if (response.success) {
                        $message.addClass('success').text('License activated successfully!');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $message.addClass('error').text(response.data?.message || 'Failed to activate license');
                    }
                },
                error: function() {
                    $form.find('button').prop('disabled', false).text('Activate');
                    $message.addClass('error').text('An error occurred');
                }
            });
        });
        
        // License deactivation - use event delegation for dynamically loaded content
        $(document).on('click', '.sapgs-deactivate-license', function(e) {
            e.preventDefault();
            var $button = $(this);
            
            if (!confirm('Are you sure you want to deactivate your license? This will disable all premium features.')) {
                return;
            }
            
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_deactivate_license',
                    nonce: sapgsData.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text('Deactivating...');
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('License deactivated successfully!', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(response.data?.message || 'Failed to deactivate license', 'error');
                        $button.prop('disabled', false).text('Deactivate License');
                    }
                },
                error: function() {
                    showNotification('An error occurred', 'error');
                    $button.prop('disabled', false).text('Deactivate License');
                }
            });
        });
        
        // Analytics
        if (typeof Chart !== 'undefined') {
            var analyticsCharts = {};
            
            function loadAnalytics(days) {
                $.ajax({
                    url: sapgsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sapgs_get_analytics',
                        days: days || 7,
                        nonce: sapgsData.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            renderAnalytics(response.data);
                        }
                    }
                });
            }
            
            function renderAnalytics(data) {
                // Fees comparison
                if (data.comparison && data.comparison.fees) {
                    var feesCtx = document.getElementById('sapgs-chart-fees');
                    if (feesCtx) {
                        if (analyticsCharts.fees) {
                            analyticsCharts.fees.destroy();
                        }
                        var feesData = {
                            labels: Object.keys(data.comparison.fees).map(function(id) {
                                return data.gateways[id]?.name || id;
                            }),
                            datasets: [{
                                label: 'Percentage Fee',
                                data: Object.values(data.comparison.fees).map(function(f) { return f.percentage; }),
                                backgroundColor: 'rgba(34, 113, 177, 0.6)'
                            }, {
                                label: 'Fixed Fee (ZAR)',
                                data: Object.values(data.comparison.fees).map(function(f) { return f.fixed; }),
                                backgroundColor: 'rgba(70, 180, 80, 0.6)'
                            }]
                        };
                        analyticsCharts.fees = new Chart(feesCtx, {
                            type: 'bar',
                            data: feesData,
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Gateway Fees Comparison'
                                    }
                                }
                            }
                        });
                    }
                }
                
                // Success rates
                if (data.comparison && data.comparison.success_rates) {
                    var successCtx = document.getElementById('sapgs-chart-success-rates');
                    if (successCtx) {
                        if (analyticsCharts.success) {
                            analyticsCharts.success.destroy();
                        }
                        analyticsCharts.success = new Chart(successCtx, {
                            type: 'doughnut',
                            data: {
                                labels: Object.keys(data.comparison.success_rates).map(function(id) {
                                    return data.gateways[id]?.name || id;
                                }),
                                datasets: [{
                                    data: Object.values(data.comparison.success_rates),
                                    backgroundColor: [
                                        'rgba(70, 180, 80, 0.6)',
                                        'rgba(34, 113, 177, 0.6)',
                                        'rgba(255, 185, 0, 0.6)',
                                        'rgba(220, 50, 50, 0.6)'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Success Rates (%)'
                                    }
                                }
                            }
                        });
                    }
                }
                
                // Response times
                if (data.comparison && data.comparison.avg_response_times) {
                    var responseCtx = document.getElementById('sapgs-chart-response-times');
                    if (responseCtx) {
                        if (analyticsCharts.response) {
                            analyticsCharts.response.destroy();
                        }
                        analyticsCharts.response = new Chart(responseCtx, {
                            type: 'line',
                            data: {
                                labels: Object.keys(data.comparison.avg_response_times).map(function(id) {
                                    return data.gateways[id]?.name || id;
                                }),
                                datasets: [{
                                    label: 'Avg Response Time (ms)',
                                    data: Object.values(data.comparison.avg_response_times),
                                    borderColor: 'rgba(34, 113, 177, 1)',
                                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Average Response Times'
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }
                }
                
                // Approval Rates Chart
                if (data.comparison && data.comparison.approval_rates) {
                    var approvalCtx = document.getElementById('sapgs-chart-approval-rates');
                    if (approvalCtx) {
                        if (analyticsCharts.approval) {
                            analyticsCharts.approval.destroy();
                        }
                        analyticsCharts.approval = new Chart(approvalCtx, {
                            type: 'bar',
                            data: {
                                labels: Object.keys(data.comparison.approval_rates).map(function(id) {
                                    return data.gateways[id]?.name || id;
                                }),
                                datasets: [{
                                    label: 'Approval Rate (%)',
                                    data: Object.values(data.comparison.approval_rates),
                                    backgroundColor: 'rgba(16, 185, 129, 0.6)'
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Approval Rates (%)'
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 100
                                    }
                                }
                            }
                        });
                    }
                }
                
                // Downtime Chart
                if (data.comparison && data.comparison.downtime_percentages) {
                    var downtimeCtx = document.getElementById('sapgs-chart-downtime');
                    if (downtimeCtx) {
                        if (analyticsCharts.downtime) {
                            analyticsCharts.downtime.destroy();
                        }
                        analyticsCharts.downtime = new Chart(downtimeCtx, {
                            type: 'bar',
                            data: {
                                labels: Object.keys(data.comparison.downtime_percentages).map(function(id) {
                                    return data.gateways[id]?.name || id;
                                }),
                                datasets: [{
                                    label: 'Downtime (%)',
                                    data: Object.values(data.comparison.downtime_percentages),
                                    backgroundColor: 'rgba(239, 68, 68, 0.6)'
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Downtime Percentage'
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }
                }
                
                // 7-Day Performance Chart
                if (data.seven_day_performance) {
                    var sevenDayCtx = document.getElementById('sapgs-chart-seven-day');
                    if (sevenDayCtx) {
                        if (analyticsCharts.sevenDay) {
                            analyticsCharts.sevenDay.destroy();
                        }
                        
                        var datasets = [];
                        var labels = [];
                        var colors = ['rgba(79, 70, 229, 0.6)', 'rgba(16, 185, 129, 0.6)', 'rgba(245, 158, 11, 0.6)', 'rgba(239, 68, 68, 0.6)', 'rgba(59, 130, 246, 0.6)'];
                        var colorIndex = 0;
                        
                        $.each(data.seven_day_performance, function(gatewayId, performance) {
                            if (performance.length > 0) {
                                if (labels.length === 0) {
                                    labels = performance.map(function(p) { return p.date; });
                                }
                                datasets.push({
                                    label: data.gateways[gatewayId]?.name || gatewayId,
                                    data: performance.map(function(p) { return p.success_rate; }),
                                    borderColor: colors[colorIndex % colors.length],
                                    backgroundColor: colors[colorIndex % colors.length].replace('0.6', '0.1'),
                                    fill: false
                                });
                                colorIndex++;
                            }
                        });
                        
                        if (datasets.length > 0) {
                            analyticsCharts.sevenDay = new Chart(sevenDayCtx, {
                                type: 'line',
                                data: {
                                    labels: labels,
                                    datasets: datasets
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        title: {
                                            display: true,
                                            text: '7-Day Performance Trend'
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            max: 100
                                        }
                                    }
                                }
                            });
                        }
                    }
                }
            }
            
            $('#sapgs-refresh-analytics').on('click', function() {
                var days = $('#sapgs-analytics-days').val();
                loadAnalytics(days);
            });
            
            $('.sapgs-tab-link[data-tab="analytics"]').on('click', function() {
                setTimeout(function() {
                    if (sapgsData.isPremium) {
                        var days = $('#sapgs-analytics-days').val() || 7;
                        loadAnalytics(days);
                        loadFeeComparison();
                    }
                }, 100);
            });
            
            // Fee comparison functionality
            function loadFeeComparison() {
                var amount = parseFloat($('#sapgs-fee-test-amount').val()) || 100;
                
                $.ajax({
                    url: sapgsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sapgs_get_fee_comparison',
                        nonce: sapgsData.nonce,
                        amount: amount
                    },
                    success: function(response) {
                        if (response.success) {
                            renderFeeComparison(response.data);
                        } else {
                            $('#sapgs-fee-comparison-results').html(
                                '<div style="text-align: center; padding: 40px; color: var(--sapgs-text-secondary);">' +
                                '<p>' + (response.data.message || 'Failed to load fee comparison') + '</p>' +
                                '</div>'
                            );
                        }
                    },
                    error: function() {
                        $('#sapgs-fee-comparison-results').html(
                            '<div style="text-align: center; padding: 40px; color: #dc3545;">' +
                            '<p>Error loading fee comparison. Please try again.</p>' +
                            '</div>'
                        );
                    }
                });
            }
            
            function renderFeeComparison(data) {
                var html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">';
                
                if (data.comparison && Object.keys(data.comparison).length > 0) {
                    $.each(data.comparison, function(gatewayId, info) {
                        var isBest = info.is_best;
                        var borderColor = isBest ? '#10b981' : 'var(--sapgs-border)';
                        var bgColor = isBest ? 'rgba(16, 185, 129, 0.05)' : 'transparent';
                        
                        html += '<div style="background: ' + bgColor + '; border: 2px solid ' + borderColor + '; border-radius: 12px; padding: 20px; position: relative;">';
                        
                        if (isBest) {
                            html += '<div style="position: absolute; top: -10px; right: 16px; background: #10b981; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase;">Best Value</div>';
                        }
                        
                        html += '<h4 style="margin: 0 0 12px 0; color: var(--sapgs-text); font-size: 18px;">' + info.name + '</h4>';
                        html += '<div style="margin-bottom: 12px;">';
                        html += '<div style="font-size: 14px; color: var(--sapgs-text-secondary); margin-bottom: 4px;">Fee Structure:</div>';
                        html += '<div style="font-size: 16px; font-weight: 600; color: var(--sapgs-text);">' + info.percentage + '% + R' + info.fixed.toFixed(2) + '</div>';
                        html += '</div>';
                        html += '<div style="margin-bottom: 12px;">';
                        html += '<div style="font-size: 14px; color: var(--sapgs-text-secondary); margin-bottom: 4px;">Cost for R' + data.test_amount.toFixed(2) + ':</div>';
                        html += '<div style="font-size: 24px; font-weight: 700; color: ' + (isBest ? '#10b981' : 'var(--sapgs-text)') + ';">R' + info.cost.toFixed(2) + '</div>';
                        html += '</div>';
                        
                        if (info.checked_at) {
                            var checkedDate = new Date(info.checked_at);
                            html += '<div style="font-size: 12px; color: var(--sapgs-text-secondary); margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--sapgs-border);">';
                            html += 'Last checked: ' + checkedDate.toLocaleDateString();
                            html += '</div>';
                        }
                        
                        html += '</div>';
                    });
                } else {
                    html += '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--sapgs-text-secondary);">';
                    html += '<p>No fee data available. Click "Check Fees Now" to fetch current fees.</p>';
                    html += '</div>';
                }
                
                html += '</div>';
                
                if (data.best_gateway) {
                    html += '<div style="margin-top: 24px; padding: 16px; background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; border-radius: 8px;">';
                    html += '<strong style="color: #10b981;">Best Option:</strong> ';
                    html += '<span style="color: var(--sapgs-text);">' + data.best_gateway.gateway_id + ' with a cost of R' + data.best_gateway.cost.toFixed(2) + ' for a R' + data.test_amount.toFixed(2) + ' transaction</span>';
                    html += '</div>';
                }
                
                $('#sapgs-fee-comparison-results').html(html);
            }
            
            // Load fee comparison on page load if on analytics tab
            if ($('#analytics').hasClass('active')) {
                loadFeeComparison();
            }
            
            // Check fees now button
            $('#sapgs-check-fees-now').on('click', function() {
                var $btn = $(this);
                var originalText = $btn.text();
                $btn.prop('disabled', true).text('Checking...');
                
                $.ajax({
                    url: sapgsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sapgs_check_fees_now',
                        nonce: sapgsData.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Fees checked successfully!', 'success');
                            loadFeeComparison();
                        } else {
                            showNotification(response.data.message || 'Failed to check fees', 'error');
                        }
                    },
                    error: function() {
                        showNotification('Error checking fees. Please try again.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            // Update fee comparison when amount changes
            $('#sapgs-fee-test-amount').on('change', function() {
                loadFeeComparison();
            });
        }
        
        // Configuration modal
        function openConfigModal(gatewayId) {
            if (!gatewayId) {
                showNotification('Gateway ID is required', 'error');
                return;
            }
            
            // Clear any previous error messages and form data
            $('#sapgs-config-error').remove();
            $('#sapgs-config-form')[0].reset();
            $('#sapgs-config-fields').empty();
            $('#sapgs-test-mode').prop('checked', false);
            
            // Clear any cached data
            $('#sapgs-config-modal').removeData('original-fields');
            $('#sapgs-config-modal').removeData('original-config');
            
            $('#sapgs-config-gateway-id').val(gatewayId);
            var gatewayName = gatewayId.charAt(0).toUpperCase() + gatewayId.slice(1).replace(/_/g, ' ');
            $('#sapgs-config-modal-title').text('Configure Gateway: ' + gatewayName);
            $('#sapgs-config-fields').html('<p style="text-align: center; padding: 20px;"><span class="sapgs-loading-spinner"></span> Loading configuration...</p>');
            $('#sapgs-config-modal').fadeIn(200);
            
            // Load gateway configuration
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_get_gateway_config',
                    gateway_id: gatewayId,
                    nonce: sapgsData.nonce
                },
                timeout: 10000,
                success: function(response) {
                    if (response && response.success && response.data) {
                        if (response.data.fields && Object.keys(response.data.fields).length > 0) {
                            renderConfigForm(gatewayId, response.data.fields, response.data.config || {});
                        } else {
                            $('#sapgs-config-fields').html('<p style="color: var(--sapgs-text-light); padding: 20px;">No configuration fields available for this gateway.</p>');
                        }
                    } else {
                        var errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Failed to load configuration';
                        $('#sapgs-config-fields').html('<p style="color: var(--sapgs-error); padding: 20px;">' + errorMsg + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Config load error:', status, error);
                    $('#sapgs-config-fields').html('<p style="color: var(--sapgs-error); padding: 20px;">An error occurred while loading configuration. Please try again.</p>');
                }
            });
        }
        
        function renderConfigForm(gatewayId, fields, config) {
            if (!fields || Object.keys(fields).length === 0) {
                $('#sapgs-config-fields').html('<p style="color: var(--sapgs-text-light); padding: 20px;">No configuration fields available for this gateway.</p>');
                return;
            }
            
            // Clear any previous error messages
            $('#sapgs-config-error').remove();
            
            // Get test mode state from config for THIS specific gateway
            var testMode = (config && config.test_mode) ? true : false;
            $('#sapgs-test-mode').prop('checked', testMode);
            
            // Store original fields and config for THIS gateway only
            // This is the LAST SAVED config - we'll restore this if user closes without successful validation
            $('#sapgs-config-modal').data('current-gateway-id', gatewayId);
            $('#sapgs-config-modal').data('original-fields', fields);
            $('#sapgs-config-modal').data('original-config', JSON.parse(JSON.stringify(config))); // Deep copy
            $('#sapgs-config-modal').data('config-saved', false); // Track if config was successfully saved
            
            // Render fields based on test mode
            renderFieldsForMode(gatewayId, fields, config, testMode);
            
            // Handle test mode toggle
            $('#sapgs-test-mode').off('change').on('change', function() {
                var currentGatewayId = $('#sapgs-config-modal').data('current-gateway-id');
                if (currentGatewayId !== gatewayId) {
                    return; // Don't process if gateway changed
                }
                var isTestMode = $(this).is(':checked');
                var originalFields = $('#sapgs-config-modal').data('original-fields');
                var originalConfig = $('#sapgs-config-modal').data('original-config');
                renderFieldsForMode(gatewayId, originalFields, originalConfig, isTestMode);
            });
        }
        
        function renderFieldsForMode(gatewayId, fields, config, testMode) {
            var html = '';
            
            // Key mappings for test/live mode
            var keyMappings = {
                'merchant_id': { test: 'test_merchant_id', live: 'merchant_id' },
                'merchant_key': { test: 'test_merchant_key', live: 'merchant_key' },
                'secret_key': { test: 'test_secret_key', live: 'secret_key' },
                'public_key': { test: 'test_public_key', live: 'public_key' },
                'api_key': { test: 'test_api_key', live: 'api_key' },
                'api_secret': { test: 'test_api_secret', live: 'api_secret' },
                'client_id': { test: 'test_client_id', live: 'client_id' },
                'client_secret': { test: 'test_client_secret', live: 'client_secret' }
            };
            
            $.each(fields, function(key, field) {
                if (!field || !field.label) return;
                
                // Skip sandbox field as we're using test mode toggle instead
                if (key === 'sandbox') return;
                
                // Determine which key to use based on test mode
                var actualKey = key;
                var displayKey = key;
                if (keyMappings[key]) {
                    actualKey = testMode ? keyMappings[key].test : keyMappings[key].live;
                    displayKey = testMode ? keyMappings[key].test : key;
                }
                
                // Get value - check test key first if in test mode, then live key
                var value = '';
                if (testMode && keyMappings[key] && config && config[keyMappings[key].test] !== undefined) {
                    value = config[keyMappings[key].test];
                } else if (config && config[actualKey] !== undefined) {
                    value = config[actualKey];
                } else if (config && config[key] !== undefined) {
                    value = config[key];
                } else {
                    value = field.default || '';
                }
                
                var required = field.required ? ' <span style="color: red;">*</span>' : '';
                var tooltip = field.tooltip ? ' <span class="sapgs-help-tooltip" data-tooltip="' + escapeHtml(field.tooltip) + '">?</span>' : '';
                
                // Update label to show test/live
                var labelText = escapeHtml(field.label);
                if (keyMappings[key] && testMode) {
                    labelText = 'Test ' + labelText;
                } else if (keyMappings[key] && !testMode) {
                    labelText = 'Live ' + labelText;
                }
                
                html += '<div class="sapgs-config-field" data-field-key="' + key + '">';
                html += '<label for="config_' + actualKey + '">' + labelText + required + tooltip + '</label>';
                
                if (field.type === 'text' || field.type === 'password') {
                    html += '<input type="' + field.type + '" id="config_' + actualKey + '" name="' + actualKey + '" data-original-key="' + key + '" value="' + escapeHtml(String(value)) + '" class="regular-text"' + (field.required ? ' required' : '') + '>';
                } else if (field.type === 'checkbox') {
                    var checked = (value === true || value === '1' || value === 1 || value === 'on') ? ' checked' : '';
                    html += '<label style="display: flex; align-items: center; gap: 8px;"><input type="checkbox" id="config_' + actualKey + '" name="' + actualKey + '" data-original-key="' + key + '" value="1"' + checked + '> <span>' + labelText + '</span></label>';
                } else if (field.type === 'select') {
                    html += '<select id="config_' + actualKey + '" name="' + actualKey + '" data-original-key="' + key + '" class="regular-text">';
                    if (field.options && typeof field.options === 'object') {
                        $.each(field.options, function(optValue, optLabel) {
                            var selected = (String(value) === String(optValue)) ? ' selected' : '';
                            html += '<option value="' + escapeHtml(String(optValue)) + '"' + selected + '>' + escapeHtml(String(optLabel)) + '</option>';
                        });
                    }
                    html += '</select>';
                } else if (field.type === 'textarea') {
                    html += '<textarea id="config_' + actualKey + '" name="' + actualKey + '" data-original-key="' + key + '" rows="4" class="large-text"' + (field.required ? ' required' : '') + '>' + escapeHtml(String(value)) + '</textarea>';
                }
                
                if (field.description) {
                    html += '<p class="description">' + escapeHtml(field.description) + '</p>';
                }
                
                html += '</div>';
            });
            
            if (html === '') {
                $('#sapgs-config-fields').html('<p style="color: var(--sapgs-text-light); padding: 20px;">No valid configuration fields found.</p>');
            } else {
                $('#sapgs-config-fields').html(html);
            }
        }
        
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        // Clear error message when user starts editing
        $(document).on('input change', '#sapgs-config-form input, #sapgs-config-form select, #sapgs-config-form textarea', function() {
            $('#sapgs-config-error').remove();
        });
        
        $('#sapgs-config-form').on('submit', function(e) {
            e.preventDefault();
            
            // Clear any previous errors
            $('#sapgs-config-error').remove();
            
            var gatewayId = $('#sapgs-config-gateway-id').val();
            
            // Verify we're still configuring the same gateway
            var currentGatewayId = $('#sapgs-config-modal').data('current-gateway-id');
            if (currentGatewayId && currentGatewayId !== gatewayId) {
                showNotification('Gateway configuration mismatch. Please close and reopen the configuration modal.', 'error');
                return;
            }
            var config = {};
            var testMode = $('#sapgs-test-mode').is(':checked');
            
            // Save test mode state
            config.test_mode = testMode ? '1' : '0';
            
            // Collect form data - save both test and live keys
            $('#sapgs-config-form input, #sapgs-config-form select, #sapgs-config-form textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var originalKey = $field.data('original-key');
                
                if (name && name !== 'test_mode') {
                    var value = '';
                    if ($field.attr('type') === 'checkbox') {
                        value = $field.is(':checked') ? '1' : '0';
                    } else {
                        value = $field.val();
                    }
                    
                    // Save the value to the actual field name
                    config[name] = value;
                    
                    // Also map back to original key if it's a test/live key
                    if (originalKey && name.startsWith('test_')) {
                        // This is a test key, also save it
                        config[name] = value;
                    } else if (originalKey && !name.startsWith('test_')) {
                        // This is a live key, save it
                        config[name] = value;
                    }
                }
            });
            
            // Save configuration
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_save_gateway_config',
                    gateway_id: gatewayId,
                    config: JSON.stringify(config),
                    nonce: sapgsData.nonce
                },
                beforeSend: function() {
                    $('#sapgs-config-form button[type="submit"]').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        var message = response.data?.message || 'Configuration saved and validated successfully!';
                        if (response.data?.gateway_enabled) {
                            message += ' The gateway has been automatically enabled.';
                            
                            // Update the gateway card to reflect enabled state
                            var $card = $('.sapgs-gateway-card[data-gateway-id="' + gatewayId + '"]');
                            if ($card.length) {
                                // Update enabled state
                                $card.data('enabled', true);
                                
                                // Enable the toggle
                                $card.find('.sapgs-enable-gateway').prop('checked', true);
                                $card.find('.sapgs-toggle-label').text('Enabled');
                                
                                // Update status indicator
                                var config = response.data?.config || {};
                                var isTestMode = config.test_mode === '1' || config.test_mode === true || config.test_mode === 1 || config.test_mode === 'on';
                                var status = isTestMode ? 'test_mode' : 'connected';
                                
                                $card.find('.sapgs-status-indicator')
                                    .removeClass('status-offline status-not_configured status-intermittent status-connected status-test_mode')
                                    .addClass('status-' + status);
                                
                                // Update test/live badge
                                var $testLiveBadge = $card.find('.sapgs-badge-warning, .sapgs-badge-success');
                                if ($testLiveBadge.length === 0) {
                                    var $actions = $card.find('.sapgs-gateway-actions');
                                    $actions.append('<span class="sapgs-badge ' + (isTestMode ? 'sapgs-badge-warning' : 'sapgs-badge-success') + '">' + (isTestMode ? 'Test' : 'Live') + '</span>');
                                } else {
                                    $testLiveBadge.removeClass('sapgs-badge-warning sapgs-badge-success')
                                        .addClass(isTestMode ? 'sapgs-badge-warning' : 'sapgs-badge-success')
                                        .text(isTestMode ? 'Test' : 'Live');
                                }
                                
                                // Remove "not configured" notice
                                $card.find('.sapgs-notice-warning').remove();
                                
                                // Show "Set as Default" button if not default
                                var isDefault = $card.find('.sapgs-badge-primary').length > 0;
                                if (!isDefault) {
                                    var $setDefaultBtn = $card.find('.sapgs-set-default');
                                    if ($setDefaultBtn.length === 0) {
                                        var $actions = $card.find('.sapgs-gateway-actions');
                                        $actions.append('<button class="button sapgs-set-default" data-gateway-id="' + gatewayId + '">Set as Default</button>');
                                    } else {
                                        $setDefaultBtn.show();
                                    }
                                }
                            }
                        }
                        
                        showNotification(message, 'success');
                        $('#sapgs-config-modal').hide();
                        $('#sapgs-config-modal').data('config-saved', true);
                    } else {
                        // Show detailed error message
                        var errorMsg = response.data?.message || 'Failed to save configuration';
                        if (response.data?.error_type === 'validation_failed') {
                            // Show error in modal with detailed information
                            var $errorDiv = $('#sapgs-config-error');
                            if ($errorDiv.length === 0) {
                                $('#sapgs-config-form').prepend('<div id="sapgs-config-error" style="padding: 16px; margin-bottom: 20px; background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 8px; color: #991B1B;"></div>');
                                $errorDiv = $('#sapgs-config-error');
                            }
                            $errorDiv.html('<strong style="display: block; margin-bottom: 8px;">⚠️ Configuration Error:</strong>' + errorMsg).show();
                            
                            // Scroll to error
                            $('html, body').animate({
                                scrollTop: $errorDiv.offset().top - 100
                            }, 300);
                        } else {
                            showNotification(errorMsg, 'error');
                        }
                        $('#sapgs-config-form button[type="submit"]').prop('disabled', false).text('Save Configuration');
                    }
                },
                error: function() {
                    showNotification('An error occurred', 'error');
                    $('#sapgs-config-form button[type="submit"]').prop('disabled', false).text('Save Configuration');
                }
            });
        });
        
        // Notification system
        function showNotification(message, type) {
            type = type || 'info';
            var $notification = $('<div class="sapgs-notification sapgs-notification-' + type + '">' + message + '</div>');
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.addClass('sapgs-notification-show');
            }, 10);
            
            setTimeout(function() {
                $notification.removeClass('sapgs-notification-show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        }
        
        // Smooth scroll to top on tab change
        $('.sapgs-tab-link').on('click', function() {
            $('html, body').animate({
                scrollTop: $('.sapgs-dashboard').offset().top - 20
            }, 300);
        });
        
        // Add hover effects to cards
        $('.sapgs-gateway-card').on('mouseenter', function() {
            $(this).find('.sapgs-status-indicator').css('transform', 'scale(1.2)');
        }).on('mouseleave', function() {
            $(this).find('.sapgs-status-indicator').css('transform', 'scale(1)');
        });
        
        // Bulk Testing
        $('#sapgs-bulk-test-btn').on('click', function() {
            var $btn = $(this);
            var $results = $('#sapgs-bulk-test-results');
            
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_bulk_test',
                    nonce: sapgsData.nonce
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).html('<span class="sapgs-loading-spinner"></span> Testing...');
                    $results.hide().empty();
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Test All Enabled Gateways');
                    
                    if (response.success && response.data) {
                        var html = '<h4>Test Results:</h4><div class="sapgs-bulk-results-grid">';
                        $.each(response.data, function(gatewayId, result) {
                            var statusClass = result.success ? 'success' : 'failed';
                            var statusIcon = result.success ? '✓' : '✗';
                            html += '<div class="sapgs-bulk-result-item ' + statusClass + '">';
                            html += '<strong>' + gatewayId + '</strong>: ';
                            html += '<span class="status-icon">' + statusIcon + '</span> ';
                            html += 'Health: ' + (result.health_score || 0) + '/100, ';
                            html += 'Response: ' + (result.response_time || 0) + 'ms';
                            html += '</div>';
                        });
                        html += '</div>';
                        $results.html(html).show();
                    } else {
                        showNotification(response.data?.message || 'Bulk test failed', 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Test All Enabled Gateways');
                    showNotification('An error occurred during bulk testing', 'error');
                }
            });
        });
        
        // Load Optimization Suggestions
        $('#sapgs-load-suggestions').on('click', function() {
            var $btn = $(this);
            var $container = $('#sapgs-suggestions-container');
            
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_get_optimization_suggestions',
                    nonce: sapgsData.nonce
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).text('Loading...');
                    $container.html('<p>Loading suggestions...</p>');
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Load Suggestions');
                    
                    if (response.success && response.data) {
                        var suggestions = response.data;
                        var html = '';
                        
                        if (Object.keys(suggestions).length === 0) {
                            html = '<p style="color: var(--sapgs-success);">✓ No optimization needed. Your setup is optimal!</p>';
                        } else {
                            $.each(suggestions, function(type, suggestion) {
                                html += '<div class="sapgs-suggestion-item" style="margin: 15px 0; padding: 15px; background: var(--sapgs-bg); border-left: 4px solid var(--sapgs-primary); border-radius: 4px;">';
                                html += '<strong>' + type.replace(/_/g, ' ').toUpperCase() + '</strong><br>';
                                html += '<p>' + suggestion.reason + '</p>';
                                if (suggestion.action) {
                                    html += '<button class="button button-small sapgs-apply-suggestion" data-suggestion=\'' + JSON.stringify(suggestion).replace(/'/g, "&#39;") + '\'>Apply Suggestion</button>';
                                }
                                html += '</div>';
                            });
                        }
                        
                        $container.html(html);
                    } else {
                        $container.html('<p style="color: var(--sapgs-error);">Failed to load suggestions</p>');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Load Suggestions');
                    $container.html('<p style="color: var(--sapgs-error);">An error occurred</p>');
                }
            });
        });
        
        // Apply Optimization Suggestion
        $(document).on('click', '.sapgs-apply-suggestion', function() {
            var suggestion = JSON.parse($(this).data('suggestion').replace(/&#39;/g, "'"));
            var $btn = $(this);
            
            if (!confirm('Apply this optimization suggestion?')) {
                return;
            }
            
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_apply_optimization',
                    suggestion: JSON.stringify(suggestion),
                    nonce: sapgsData.nonce
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).text('Applying...');
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Optimization applied successfully!', 'success');
                        // Reload suggestions to show updated state
                        $('#sapgs-load-suggestions').trigger('click');
                    } else {
                        showNotification(response.data?.message || 'Failed to apply optimization', 'error');
                        $btn.prop('disabled', false).text('Apply Suggestion');
                    }
                },
                error: function() {
                    showNotification('An error occurred', 'error');
                    $btn.prop('disabled', false).text('Apply Suggestion');
                }
            });
        });
        
        // Disable sorting for free users
        if (!sapgsData.isPremium) {
            $('#sapgs-sort-by').on('change', function() {
                showNotification('Gateway sorting is a Premium feature. Upgrade to unlock this feature.', 'error');
                $(this).val('manual'); // Reset to manual
            });
        }
        
        // Gateway Sorting (Premium Only) - No drag and drop
        if (sapgsData.isPremium) {
            var sortingData = null;
            
            // Load sorting data
            function loadSortingData() {
                $.ajax({
                    url: sapgsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sapgs_get_sorting_data',
                        nonce: sapgsData.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            sortingData = response.data;
                        }
                    }
                });
            }
            
            // Sort gateways
            function sortGateways(sortType) {
                if (!sortingData) {
                    loadSortingData();
                    setTimeout(function() {
                        sortGateways(sortType);
                    }, 500);
                    return;
                }
                
                var $cards = $('.sapgs-gateway-card');
                var cards = $cards.toArray();
                
                // Separate enabled and disabled
                var enabled = [];
                var disabled = [];
                
                cards.forEach(function(card) {
                    var $card = $(card);
                    if ($card.data('enabled') === true || $card.data('enabled') === 'true') {
                        enabled.push(card);
                    } else {
                        disabled.push(card);
                    }
                });
                
                // Sort enabled gateways
                enabled.sort(function(a, b) {
                    var aId = $(a).data('gateway-id');
                    var bId = $(b).data('gateway-id');
                    var aData = sortingData[aId] || {};
                    var bData = sortingData[bId] || {};
                    
                    return compareGateways(aData, bData, sortType);
                });
                
                // Sort disabled gateways
                disabled.sort(function(a, b) {
                    var aId = $(a).data('gateway-id');
                    var bId = $(b).data('gateway-id');
                    var aData = sortingData[aId] || {};
                    var bData = sortingData[bId] || {};
                    
                    return compareGateways(aData, bData, sortType);
                });
                
                // Combine: enabled first, then disabled
                var sorted = enabled.concat(disabled);
                
                // Reorder in DOM
                var $grid = $('.sapgs-gateways-grid');
                sorted.forEach(function(card) {
                    $grid.append(card);
                });
                
                // Save order
                var order = sorted.map(function(card) {
                    return $(card).data('gateway-id');
                });
                saveGatewayOrder(order, sortType);
            }
            
            // Compare gateways based on sort type
            function compareGateways(aData, bData, sortType) {
                switch(sortType) {
                    case 'approval_rate':
                        return (bData.approval_rate || 0) - (aData.approval_rate || 0);
                    case 'success_rate':
                        return (bData.success_rate || 0) - (aData.success_rate || 0);
                    case 'lowest_fees':
                        return (aData.fee_adjusted_cost || 999999) - (bData.fee_adjusted_cost || 999999);
                    case 'fastest_response':
                        return (aData.response_time || 9999) - (bData.response_time || 9999);
                    case 'highest_uptime':
                        return (bData.uptime || 0) - (aData.uptime || 0);
                    case 'smart_weighted':
                    default:
                        return (bData.smart_score || 0) - (aData.smart_score || 0);
                }
            }
            
            // Save gateway order
            function saveGatewayOrder(order, sortType) {
                $.ajax({
                    url: sapgsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sapgs_save_gateway_order',
                        order: JSON.stringify(order),
                        sort_type: sortType,
                        nonce: sapgsData.nonce
                    },
                    success: function(response) {
                        if (!response.success) {
                            console.error('Failed to save gateway order');
                        }
                    }
                });
            }
            
            // Handle sort dropdown change
            $('#sapgs-sort-by').on('change', function() {
                var sortType = $(this).val();
                sortGateways(sortType);
            });
            
            // Initialize on page load
            loadSortingData();
            var currentSortType = $('#sapgs-sort-by').val();
            if (currentSortType && currentSortType !== 'manual') {
                // Auto-sort on load
                setTimeout(function() {
                    sortGateways(currentSortType);
                }, 300);
            }
        }
        
        // Fee Calculator (Free Users)
        $('#sapgs-calculate-fee').on('click', function() {
            var amount = parseFloat($('#sapgs-calc-amount').val()) || 0;
            var gatewayId = $('#sapgs-calc-gateway').val();
            
            if (amount <= 0) {
                showNotification('Please enter a valid amount', 'error');
                return;
            }
            
            // Get gateway fees (this would normally come from API, but for free users we use static data)
            var gatewayFees = {
                'payfast': { percentage: 2.9, fixed: 2.00 },
                'ozow': { percentage: 2.75, fixed: 1.50 },
                'yoco': { percentage: 2.95, fixed: 0.00 },
                'peach_payments': { percentage: 2.75, fixed: 0.00 },
                'paygate': { percentage: 3.0, fixed: 2.00 },
                'paystack_za': { percentage: 2.9, fixed: 2.00 },
                'snapscan': { percentage: 2.75, fixed: 0.00 },
                'zapper': { percentage: 2.5, fixed: 0.00 },
                'stitch': { percentage: 2.5, fixed: 0.00 }
            };
            
            var fees = gatewayFees[gatewayId] || { percentage: 0, fixed: 0 };
            var percentageFee = (amount * fees.percentage) / 100;
            var totalFee = percentageFee + fees.fixed;
            var netAmount = amount - totalFee;
            
            var html = '<div style="font-size: 14px; line-height: 2;">';
            html += '<div><strong>Transaction Amount:</strong> R' + amount.toFixed(2) + '</div>';
            html += '<div><strong>Percentage Fee (' + fees.percentage + '%):</strong> R' + percentageFee.toFixed(2) + '</div>';
            html += '<div><strong>Fixed Fee:</strong> R' + fees.fixed.toFixed(2) + '</div>';
            html += '<div style="border-top: 2px solid var(--sapgs-border); padding-top: 8px; margin-top: 8px;"><strong>Total Fee:</strong> R' + totalFee.toFixed(2) + '</div>';
            html += '<div style="color: var(--sapgs-primary); font-weight: 600; margin-top: 8px;"><strong>Net Amount (after fees):</strong> R' + netAmount.toFixed(2) + '</div>';
            html += '</div>';
            
            $('#sapgs-fee-breakdown').html(html);
            $('#sapgs-fee-result').slideDown(300);
        });
        
        // Simulated Failover Demo (Free Users)
        $('#sapgs-run-failover-demo').on('click', function() {
            var $button = $(this);
            var $result = $('#sapgs-failover-demo-result');
            var $steps = $('#sapgs-failover-demo-steps');
            
            $button.prop('disabled', true).text('Running Demo...');
            $result.hide();
            $steps.html('');
            
            // Simulate failover scenario
            var steps = [];
            steps.push('<div style="padding: 8px; background: #FEF3C7; border-left: 4px solid #F59E0B; margin-bottom: 8px; border-radius: 4px;">');
            steps.push('<strong>Step 1:</strong> Payment request received (R100.00)');
            steps.push('</div>');
            
            setTimeout(function() {
                steps.push('<div style="padding: 8px; background: #FEF3C7; border-left: 4px solid #F59E0B; margin-bottom: 8px; border-radius: 4px;">');
                steps.push('<strong>Step 2:</strong> Attempting payment with Primary Gateway (Payfast)...');
                steps.push('</div>');
                $steps.html(steps.join(''));
                
                setTimeout(function() {
                    steps.push('<div style="padding: 8px; background: #FEE2E2; border-left: 4px solid #EF4444; margin-bottom: 8px; border-radius: 4px;">');
                    steps.push('<strong>Step 3:</strong> ❌ Primary Gateway failed (Timeout: 5000ms)');
                    steps.push('</div>');
                    $steps.html(steps.join(''));
                    
                    setTimeout(function() {
                        steps.push('<div style="padding: 8px; background: #D1FAE5; border-left: 4px solid #10B981; margin-bottom: 8px; border-radius: 4px;">');
                        steps.push('<strong>Step 4:</strong> ✅ Automatic failover to Backup Gateway (Ozow)');
                        steps.push('</div>');
                        $steps.html(steps.join(''));
                        
                        setTimeout(function() {
                            steps.push('<div style="padding: 8px; background: #D1FAE5; border-left: 4px solid #10B981; margin-bottom: 8px; border-radius: 4px;">');
                            steps.push('<strong>Step 5:</strong> ✅ Payment successful! Transaction ID: DEMO-' + Date.now());
                            steps.push('</div>');
                            steps.push('<div style="margin-top: 12px; padding: 12px; background: #EFF6FF; border-radius: 8px; border: 1px solid #3B82F6;">');
                            steps.push('<strong>Result:</strong> Payment completed successfully via failover. Without automatic failover, this payment would have failed.');
                            steps.push('</div>');
                            $steps.html(steps.join(''));
                            $result.slideDown(300);
                            $button.prop('disabled', false).text('Run Failover Demo');
                        }, 1000);
                    }, 1000);
                }, 1500);
            }, 500);
        });
        
        // Refresh Uptime Status (Free Users)
        $('#sapgs-refresh-uptime').on('click', function() {
            var $button = $(this);
            var $list = $('#sapgs-uptime-status-list');
            
            $button.prop('disabled', true).text('Refreshing...');
            
            // Test all enabled gateways
            var enabledGateways = [];
            $('.sapgs-gateway-card[data-enabled="true"]').each(function() {
                enabledGateways.push($(this).data('gateway-id'));
            });
            
            if (enabledGateways.length === 0) {
                $list.html('<p style="color: var(--sapgs-text-light);">No gateways enabled. Enable a gateway to see status.</p>');
                $button.prop('disabled', false).text('Refresh Status');
                return;
            }
            
            // Test first gateway (free users only have one)
            var gatewayId = enabledGateways[0];
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_test_gateway',
                    gateway_id: gatewayId,
                    nonce: sapgsData.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var data = response.data;
                        var isUp = data.success;
                        var gatewayName = $('.sapgs-gateway-card[data-gateway-id="' + gatewayId + '"]').find('h3').text();
                        
                        var html = '<div style="display: flex; align-items: center; padding: 12px; border-bottom: 1px solid var(--sapgs-border);">';
                        html += '<span class="sapgs-status-indicator status-' + (isUp ? 'connected' : 'offline') + '" style="margin-right: 12px;"></span>';
                        html += '<div style="flex: 1;">';
                        html += '<strong>' + gatewayName + '</strong>';
                        html += '<p style="margin: 4px 0 0 0; font-size: 13px; color: var(--sapgs-text-light);">';
                        html += isUp ? 'Online - Gateway is responding (' + data.response_time + 'ms)' : 'Offline - Gateway is not responding';
                        html += '</p>';
                        html += '</div>';
                        html += '</div>';
                        
                        $list.html(html);
                        showNotification('Status refreshed successfully', 'success');
                    }
                },
                complete: function() {
                    $button.prop('disabled', false).text('Refresh Status');
                }
            });
        });
        
        // Gateway Rankings
        function loadRankings(useLiveData) {
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_get_gateway_rankings',
                    use_live_data: useLiveData ? 'true' : 'false',
                    nonce: sapgsData.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        renderRankings(response.data.rankings, response.data.use_live_data);
                    }
                }
            });
        }
        
        function renderRankings(rankings, useLiveData) {
            var html = '<table style="width: 100%; border-collapse: collapse;">';
            html += '<thead><tr style="background: var(--sapgs-bg); border-bottom: 2px solid var(--sapgs-border);">';
            html += '<th style="padding: 12px; text-align: left;">Rank</th>';
            html += '<th style="padding: 12px; text-align: left;">Gateway</th>';
            html += '<th style="padding: 12px; text-align: right;">Success Rate</th>';
            html += '<th style="padding: 12px; text-align: right;">Response Time</th>';
            html += '<th style="padding: 12px; text-align: right;">Uptime</th>';
            html += '<th style="padding: 12px; text-align: right;">Fees</th>';
            html += '<th style="padding: 12px; text-align: right;">Score</th>';
            html += '</tr></thead><tbody>';
            
            $.each(rankings, function(gatewayId, data) {
                var rankClass = data.rank === 1 ? 'background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 215, 0, 0.05) 100%);' : '';
                html += '<tr style="border-bottom: 1px solid var(--sapgs-border); ' + rankClass + '">';
                html += '<td style="padding: 12px; font-weight: 600; font-size: 18px;">#' + data.rank + '</td>';
                html += '<td style="padding: 12px; font-weight: 600;">' + data.name + '</td>';
                html += '<td style="padding: 12px; text-align: right;">' + data.success_rate.toFixed(1) + '%</td>';
                html += '<td style="padding: 12px; text-align: right;">' + data.response_time + 'ms</td>';
                html += '<td style="padding: 12px; text-align: right;">' + data.uptime.toFixed(1) + '%</td>';
                html += '<td style="padding: 12px; text-align: right;">' + data.fee_percentage.toFixed(2) + '%</td>';
                html += '<td style="padding: 12px; text-align: right; font-weight: 700; color: var(--sapgs-primary);">' + data.weighted_score.toFixed(1) + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            html += '<p style="margin-top: 15px; color: var(--sapgs-text-light); font-size: 13px;">';
            html += useLiveData ? 'Using live store data' : 'Using benchmark data (upgrade to Premium for live data)';
            html += '</p>';
            
            $('#sapgs-rankings-container').html(html);
        }
        
        $('#sapgs-refresh-rankings').on('click', function() {
            var useLiveData = $('#sapgs-use-live-data').is(':checked');
            loadRankings(useLiveData);
        });
        
        $('#sapgs-use-live-data').on('change', function() {
            if ($(this).is(':checked') && !sapgsData.isPremium) {
                showNotification('Live data rankings require Premium', 'error');
                $(this).prop('checked', false);
                return;
            }
            loadRankings($(this).is(':checked'));
        });
        
        // Load rankings on tab show
        $('.sapgs-tab-link[data-tab="rankings"]').on('click', function() {
            setTimeout(function() {
                var useLiveData = $('#sapgs-use-live-data').is(':checked') && sapgsData.isPremium;
                loadRankings(useLiveData);
            }, 300);
        });
        
        // Failover Report (Premium)
        if (sapgsData.isPremium) {
            $('#sapgs-load-failover-report').on('click', function() {
                var days = $('#sapgs-failover-days').val();
                var $container = $('#sapgs-failover-report-container');
                
                $container.html('<div class="sapgs-loading">Loading report...</div>');
                
                $.ajax({
                    url: sapgsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sapgs_get_failover_report',
                        days: days,
                        nonce: sapgsData.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            renderFailoverReport(response.data);
                        } else {
                            $container.html('<p style="color: var(--sapgs-text-light);">' + (response.data?.message || 'Failed to load report') + '</p>');
                        }
                    }
                });
            });
            
            function renderFailoverReport(data) {
                var html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">';
                html += '<div style="background: var(--sapgs-bg); padding: 20px; border-radius: 8px; border: 1px solid var(--sapgs-border);">';
                html += '<div style="font-size: 32px; font-weight: 700; color: var(--sapgs-primary);">' + data.total + '</div>';
                html += '<div style="color: var(--sapgs-text-light); margin-top: 8px;">Total Failovers</div>';
                html += '</div>';
                html += '<div style="background: var(--sapgs-bg); padding: 20px; border-radius: 8px; border: 1px solid var(--sapgs-border);">';
                html += '<div style="font-size: 32px; font-weight: 700; color: var(--sapgs-primary);">' + (data.avg_recovery_time || 0) + 'ms</div>';
                html += '<div style="color: var(--sapgs-text-light); margin-top: 8px;">Avg Recovery Time</div>';
                html += '</div>';
                html += '</div>';
                
                if (data.by_primary_gateway && data.by_primary_gateway.length > 0) {
                    html += '<h3>Failovers by Primary Gateway</h3>';
                    html += '<table style="width: 100%; margin-top: 15px; border-collapse: collapse;">';
                    html += '<thead><tr style="background: var(--sapgs-bg); border-bottom: 2px solid var(--sapgs-border);">';
                    html += '<th style="padding: 12px; text-align: left;">Gateway</th>';
                    html += '<th style="padding: 12px; text-align: right;">Failover Count</th>';
                    html += '<th style="padding: 12px; text-align: right;">Avg Recovery</th>';
                    html += '</tr></thead><tbody>';
                    
                    $.each(data.by_primary_gateway, function(i, item) {
                        html += '<tr style="border-bottom: 1px solid var(--sapgs-border);">';
                        html += '<td style="padding: 12px;">' + item.primary_gateway_id + '</td>';
                        html += '<td style="padding: 12px; text-align: right;">' + item.count + '</td>';
                        html += '<td style="padding: 12px; text-align: right;">' + (item.avg_recovery ? Math.round(item.avg_recovery) + 'ms' : 'N/A') + '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                }
                
                $('#sapgs-failover-report-container').html(html);
            }
        }
        
        // Webhook Health Checks
        $('#sapgs-check-all-webhooks').on('click', function() {
            var $button = $(this);
            var $results = $('#sapgs-webhook-health-results');
            
            $button.prop('disabled', true).text('Checking...');
            $results.html('<div class="sapgs-loading">Checking webhook health...</div>');
            
            // Check each enabled gateway
            var enabledGateways = [];
            $('.sapgs-gateway-card[data-enabled="true"]').each(function() {
                enabledGateways.push($(this).data('gateway-id'));
            });
            
            if (enabledGateways.length === 0) {
                $results.html('<p style="color: var(--sapgs-text-light);">No gateways enabled</p>');
                $button.prop('disabled', false).text('Check All Gateways');
                return;
            }
            
            var checks = [];
            $.each(enabledGateways, function(i, gatewayId) {
                $.ajax({
                    url: sapgsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sapgs_check_webhook_health',
                        gateway_id: gatewayId,
                        nonce: sapgsData.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            checks.push({
                                gateway_id: gatewayId,
                                data: response.data
                            });
                            
                            if (checks.length === enabledGateways.length) {
                                renderWebhookHealth(checks);
                                $button.prop('disabled', false).text('Check All Gateways');
                            }
                        }
                    }
                });
            });
        });
        
        function renderWebhookHealth(checks) {
            var html = '';
            $.each(checks, function(i, check) {
                var status = check.data.is_reachable ? '✓ Reachable' : '✗ Not Reachable';
                var statusColor = check.data.is_reachable ? '#10B981' : '#EF4444';
                html += '<div style="padding: 12px; border-bottom: 1px solid var(--sapgs-border);">';
                html += '<strong>' + check.gateway_id + '</strong><br>';
                html += '<span style="color: ' + statusColor + ';">' + status + '</span>';
                if (check.data.response_time) {
                    html += ' (' + check.data.response_time + 'ms)';
                }
                html += '</div>';
            });
            $('#sapgs-webhook-health-results').html(html || '<p style="color: var(--sapgs-text-light);">No results</p>');
        }
        
        // Webhook Events Listener
        $('#sapgs-load-webhook-events').on('click', function() {
            var $list = $('#sapgs-webhook-events-list');
            $list.html('<div class="sapgs-loading">Loading events...</div>');
            
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_get_webhook_events',
                    limit: 20,
                    nonce: sapgsData.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        renderWebhookEvents(response.data);
                    } else {
                        $list.html('<p style="color: var(--sapgs-text-light);">No webhook events found</p>');
                    }
                }
            });
        });
        
        function renderWebhookEvents(events) {
            if (events.length === 0) {
                $('#sapgs-webhook-events-list').html('<p style="color: var(--sapgs-text-light);">No webhook events received yet</p>');
                return;
            }
            
            var html = '';
            $.each(events, function(i, event) {
                html += '<div style="padding: 12px; border-bottom: 1px solid var(--sapgs-border); background: var(--sapgs-bg); margin-bottom: 8px; border-radius: 8px;">';
                html += '<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">';
                html += '<strong>' + event.gateway_id + '</strong>';
                html += '<span style="color: var(--sapgs-text-light); font-size: 12px;">' + event.received_at + '</span>';
                html += '</div>';
                html += '<div style="font-size: 12px; color: var(--sapgs-text-light);">Type: ' + (event.event_type || 'N/A') + '</div>';
                if (event.signature_valid !== null) {
                    html += '<div style="font-size: 12px; color: ' + (event.signature_valid ? '#10B981' : '#EF4444') + ';">';
                    html += 'Signature: ' + (event.signature_valid ? 'Valid' : 'Invalid');
                    html += '</div>';
                }
                html += '</div>';
            });
            
            $('#sapgs-webhook-events-list').html(html);
        }
        
        // Payment Simulation (Premium)
        if (sapgsData.isPremium) {
            $('#sapgs-run-simulation').on('click', function() {
                var gatewayId = $('#sapgs-simulate-gateway').val();
                var amount = parseFloat($('#sapgs-simulate-amount').val()) || 100.00;
                var $button = $(this);
                var $results = $('#sapgs-simulation-results');
                
                $button.prop('disabled', true).text('Running...');
                $results.html('<div class="sapgs-loading">Running simulation...</div>');
                
                $.ajax({
                    url: sapgsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sapgs_simulate_checkout',
                        gateway_id: gatewayId,
                        amount: amount,
                        nonce: sapgsData.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            renderSimulationResults(response.data);
                        } else {
                            $results.html('<p style="color: #EF4444;">' + (response.data?.message || 'Simulation failed') + '</p>');
                        }
                        $button.prop('disabled', false).text('Run Simulation');
                    }
                });
            });
            
            function renderSimulationResults(data) {
                var html = '<div style="background: var(--sapgs-bg); padding: 20px; border-radius: 8px; border: 1px solid var(--sapgs-border);">';
                html += '<h4 style="margin-top: 0;">Simulation Results for ' + data.gateway_name + '</h4>';
                
                if (data.steps) {
                    $.each(data.steps, function(stepName, step) {
                        var statusColor = step.success ? '#10B981' : '#EF4444';
                        html += '<div style="padding: 12px; margin-bottom: 12px; border-left: 4px solid ' + statusColor + '; background: white; border-radius: 4px;">';
                        html += '<strong>' + step.name + '</strong><br>';
                        html += '<span style="color: ' + statusColor + ';">' + (step.success ? '✓' : '✗') + ' ' + step.message + '</span><br>';
                        html += '<span style="color: var(--sapgs-text-light); font-size: 12px;">Response Time: ' + step.response_time + 'ms</span>';
                        html += '</div>';
                    });
                }
                
                html += '<div style="margin-top: 20px; padding: 16px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); border-radius: 8px;">';
                html += '<strong>Total Time to Confirmation: ' + (data.time_to_confirmation || data.total_time || 0) + 'ms</strong>';
                html += '</div>';
                html += '</div>';
                
                $('#sapgs-simulation-results').html(html);
            }
        }
        
        // Before Going Live Checklist
        $('#sapgs-run-live-checklist').on('click', function() {
            var $button = $(this);
            var $results = $('#sapgs-live-checklist-results');
            
            $button.prop('disabled', true).text('Running...');
            $results.html('<div class="sapgs-loading">Running checklist...</div>');
            
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_get_live_checklist',
                    nonce: sapgsData.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        renderLiveChecklist(response.data);
                    }
                    $button.prop('disabled', false).text('Run Checklist');
                }
            });
        });
        
        function renderLiveChecklist(data) {
            var html = '<div style="margin-top: 20px;">';
            html += '<div style="padding: 16px; background: ' + (data.all_passed ? 'linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);' : 'linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);') + ' border-radius: 8px; border: 2px solid ' + (data.all_passed ? '#10B981' : '#EF4444') + '; margin-bottom: 20px;">';
            html += '<strong style="font-size: 18px;">' + data.passed_count + ' / ' + data.total_count + ' checks passed</strong>';
            html += '</div>';
            
            $.each(data.checklist, function(key, item) {
                var icon = item.status ? 'dashicons-yes-alt' : 'dashicons-dismiss';
                var color = item.status ? '#10B981' : '#EF4444';
                html += '<div style="display: flex; align-items: center; padding: 12px; border-bottom: 1px solid var(--sapgs-border);">';
                html += '<span class="dashicons ' + icon + '" style="color: ' + color + '; margin-right: 12px; font-size: 20px;"></span>';
                html += '<div style="flex: 1;">';
                html += '<strong>' + item.label + '</strong><br>';
                html += '<span style="color: var(--sapgs-text-light); font-size: 13px;">' + item.message + '</span>';
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            $('#sapgs-live-checklist-results').html(html);
        }
        
        // Settings form submission
        $('#sapgs-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $('#sapgs-save-settings');
            var $message = $('#sapgs-settings-message');
            
            var settings = {
                failover_enabled: $('#sapgs_failover_enabled').is(':checked') ? '1' : '0',
                routing_mode: $('#sapgs_routing_mode').val() || 'default'
            };
            
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_save_settings',
                    settings: JSON.stringify(settings),
                    nonce: sapgsData.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text('Saving...');
                    $message.removeClass('success error').text('');
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Save Settings');
                    
                    if (response.success) {
                        $message.addClass('success').text('Settings saved successfully!');
                        setTimeout(function() {
                            $message.removeClass('success error').text('');
                        }, 3000);
                    } else {
                        $message.addClass('error').text(response.data?.message || 'Failed to save settings');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Save Settings');
                    $message.addClass('error').text('An error occurred. Please try again.');
                }
            });
        });
        
        // Prevent horizontal scrolling
        $('body').css('overflow-x', 'hidden');
        $(window).on('resize', function() {
            $('body').css('overflow-x', 'hidden');
        });
        
        // Hide LSCWP error messages - disabled temporarily to debug blank page issue
        // Will re-enable once page loads correctly
        /*
        function hideLSCWPMessages() {
            try {
                // Only target elements that are definitely WordPress admin notices
                // and are direct children of #wpbody-content
                $('#wpbody-content > .notice.error, #wpbody-content > .error.notice').each(function() {
                    try {
                        var $el = $(this);
                        if (!$el || $el.length === 0) return;
                        
                        var text = $el.text() || '';
                        // Only hide if it contains LSCWP-related text
                        if (text.indexOf('LSCWP') !== -1 || 
                            text.indexOf('object cache initialization') !== -1 ||
                            text.indexOf('Can NOT find LSCWP') !== -1) {
                            // Use CSS to hide instead of jQuery hide() for safety
                            $el.css('display', 'none');
                        }
                    } catch(e) {
                        // Silently fail for individual elements
                    }
                });
            } catch(e) {
                // Silently fail - don't break the page
            }
        }
        
        // Only run after DOM is ready with delay to avoid breaking the page
        setTimeout(function() {
            try {
                if (typeof $ !== 'undefined' && $('#wpbody-content').length > 0) {
                    hideLSCWPMessages();
                }
            } catch(e) {
                // Silently fail
            }
        }, 1000);
        */
        
        // Dynamic tooltip positioning for gateway info icons
        var tooltipTimeout;
        var currentTooltip = null;
        var currentIcon = null;
        var tooltipUpdateHandler = null;
        
        function updateTooltipPosition($icon, $tooltip) {
            if (!$icon || !$tooltip || !$icon.length || !$tooltip.length) {
                return;
            }
            
            // Get icon position relative to viewport (getBoundingClientRect is viewport-relative)
            var iconRect = $icon[0].getBoundingClientRect();
            var iconCenterX = iconRect.left + (iconRect.width / 2);
            var iconTop = iconRect.top;
            var iconBottom = iconRect.bottom;
            
            // Get tooltip dimensions
            var tooltipWidth = $tooltip.outerWidth();
            var tooltipHeight = $tooltip.outerHeight();
            
            // Get viewport dimensions
            var viewportWidth = window.innerWidth || $(window).width();
            var viewportHeight = window.innerHeight || $(window).height();
            
            // Calculate positions (using viewport coordinates for fixed positioning)
            var top, left;
            var spacing = 12;
            
            // Try to position above icon first
            top = iconTop - tooltipHeight - spacing;
            left = iconCenterX - (tooltipWidth / 2);
            
            // Check if tooltip fits above (in viewport)
            var fitsAbove = top >= 10;
            
            // If doesn't fit above, try below
            if (!fitsAbove) {
                top = iconBottom + spacing;
                // Check if it fits below
                var fitsBelow = (top + tooltipHeight) <= (viewportHeight - 10);
                if (!fitsBelow) {
                    // Doesn't fit below either, position in viewport center vertically
                    top = Math.max(10, (viewportHeight / 2) - (tooltipHeight / 2));
                }
            }
            
            // Adjust horizontal position to stay within viewport
            if (left < 10) {
                left = 10;
            } else if (left + tooltipWidth > viewportWidth - 10) {
                left = viewportWidth - tooltipWidth - 10;
            }
            
            // Ensure tooltip doesn't go off top of viewport
            if (top < 10) {
                top = 10;
            }
            
            // Apply position (using fixed positioning, so no scroll offset needed)
            $tooltip.css({
                'top': top + 'px',
                'left': left + 'px',
                'visibility': 'visible',
                'opacity': '1'
            });
        }
        
        function removeTooltip() {
            if (currentTooltip) {
                currentTooltip.stop().fadeOut(150, function() {
                    $(this).remove();
                });
                currentTooltip = null;
            }
            if (currentIcon) {
                currentIcon = null;
            }
            if (tooltipUpdateHandler) {
                $(window).off('scroll resize', tooltipUpdateHandler);
                tooltipUpdateHandler = null;
            }
        }
        
        $(document).on('mouseenter', '.sapgs-gateway-info-icon', function(e) {
            var $icon = $(this);
            
            // Clear any existing timeout
            clearTimeout(tooltipTimeout);
            
            // Remove any existing tooltip
            removeTooltip();
            
            var tooltipText = $icon.attr('data-tooltip');
            if (!tooltipText || tooltipText.trim() === '') {
                return; // No tooltip text, don't show anything
            }
            
            // Store reference to current icon
            currentIcon = $icon;
            
            // Create tooltip element
            var $tooltip = $('<div class="sapgs-tooltip-content"></div>')
                .html(tooltipText.replace(/\n/g, '<br>'))
                .css({
                    'position': 'fixed',
                    'background': '#111827',
                    'color': 'white',
                    'padding': '12px 16px',
                    'border-radius': '8px',
                    'font-size': '12px',
                    'line-height': '1.6',
                    'z-index': '100005',
                    'max-width': '320px',
                    'min-width': '240px',
                    'box-shadow': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    'pointer-events': 'none',
                    'opacity': '0',
                    'transition': 'opacity 0.2s ease',
                    'text-align': 'left',
                    'font-weight': 'normal',
                    'font-style': 'normal',
                    'display': 'block',
                    'visibility': 'hidden',
                    'top': '-9999px',
                    'left': '-9999px'
                });
            $('body').append($tooltip);
            currentTooltip = $tooltip;
            
            // Use double requestAnimationFrame to ensure DOM is fully rendered
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    updateTooltipPosition($icon, $tooltip);
                    
                    // Set up scroll/resize handler to update position
                    tooltipUpdateHandler = function() {
                        if (currentTooltip && currentIcon) {
                            updateTooltipPosition(currentIcon, currentTooltip);
                        }
                    };
                    
                    $(window).on('scroll resize', tooltipUpdateHandler);
                });
            });
        });
        
        $(document).on('mouseleave', '.sapgs-gateway-info-icon', function() {
            clearTimeout(tooltipTimeout);
            removeTooltip();
        });
        
        } catch(e) {
            // Catch any errors to prevent white screen
            console.error('Error in PaySwitch ZA admin script:', e);
        }
    });
    
})(jQuery);

