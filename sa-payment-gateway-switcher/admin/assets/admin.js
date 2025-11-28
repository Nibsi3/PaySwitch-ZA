/**
 * SA Payment Gateway Switcher Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Tab switching
        $('.sapgs-tab-link').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            
            $('.sapgs-tab-link').removeClass('active');
            $('.sapgs-tab-content').removeClass('active');
            
            $(this).addClass('active');
            $('#' + tab).addClass('active');
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
                    $card.css('opacity', '0.6');
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to toggle gateway');
                        $card.css('opacity', '1');
                    }
                },
                error: function() {
                    alert('An error occurred');
                    $card.css('opacity', '1');
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
                    $button.prop('disabled', true).text('Setting...');
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to set default gateway');
                        $button.prop('disabled', false).text('Set as Default');
                    }
                },
                error: function() {
                    alert('An error occurred');
                    $button.prop('disabled', false).text('Set as Default');
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
                    $button.prop('disabled', true).text('Testing...');
                    $result.hide();
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Run Test');
                    
                    if (response.success && response.data) {
                        var data = response.data;
                        $card.find('.sapgs-test-status').text(data.success ? 'Success' : 'Failed')
                            .css('color', data.success ? '#46b450' : '#dc3232');
                        $card.find('.sapgs-test-response-time').text(data.response_time + 'ms');
                        $card.find('.sapgs-test-health-score').text(data.health_score + '/100');
                        $card.find('.sapgs-test-message').text(data.message || '');
                        $result.show();
                    } else {
                        alert(response.data?.message || 'Test failed');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Run Test');
                    alert('An error occurred');
                }
            });
        });
        
        // Configure gateway
        $('.sapgs-configure-gateway').on('click', function() {
            var gatewayId = $(this).data('gateway-id');
            openConfigModal(gatewayId);
        });
        
        // Modal close
        $('.sapgs-modal-close').on('click', function() {
            $('#sapgs-config-modal').hide();
        });
        
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('sapgs-modal')) {
                $('#sapgs-config-modal').hide();
            }
        });
        
        // Load logs
        function loadLogs(gatewayId) {
            $.ajax({
                url: sapgsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sapgs_get_logs',
                    gateway_id: gatewayId || '',
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
                loadLogs($('#sapgs-logs-gateway-filter').val());
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
                    }
                }, 100);
            });
        }
        
        // Configuration modal
        function openConfigModal(gatewayId) {
            // This would load gateway config fields via AJAX
            // For now, show a simple form
            $('#sapgs-config-gateway-id').val(gatewayId);
            $('#sapgs-config-modal-title').text('Configure Gateway: ' + gatewayId);
            $('#sapgs-config-fields').html('<p>Configuration fields would be loaded here based on gateway type.</p>');
            $('#sapgs-config-modal').show();
        }
        
        $('#sapgs-config-form').on('submit', function(e) {
            e.preventDefault();
            // Save configuration via AJAX
            alert('Configuration save functionality would be implemented here');
            $('#sapgs-config-modal').hide();
        });
        
    });
    
})(jQuery);

