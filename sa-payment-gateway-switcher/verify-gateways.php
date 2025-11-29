<?php
/**
 * Gateway Verification Script
 * 
 * This script verifies that all payment gateways are properly implemented
 * Run this from WordPress admin or via WP-CLI
 */

if (!defined('ABSPATH')) {
    // If running standalone, load WordPress
    require_once('../../../wp-load.php');
}

// Load plugin classes
require_once __DIR__ . '/core/GatewayInterface.php';
require_once __DIR__ . '/core/GatewayManager.php';
require_once __DIR__ . '/gateways/PayfastGateway.php';
require_once __DIR__ . '/gateways/OzowGateway.php';
require_once __DIR__ . '/gateways/YocoGateway.php';
require_once __DIR__ . '/gateways/PeachPaymentsGateway.php';
require_once __DIR__ . '/gateways/PayGateGateway.php';
require_once __DIR__ . '/gateways/PaystackZAGateway.php';
require_once __DIR__ . '/gateways/SnapScanGateway.php';
require_once __DIR__ . '/gateways/ZapperGateway.php';
require_once __DIR__ . '/gateways/StitchGateway.php';

class SAPGS_GatewayVerifier {
    
    private $required_methods = array(
        'get_id',
        'get_name',
        'get_description',
        'connect',
        'charge',
        'refund',
        'test_connection',
        'get_logs',
        'get_config_fields',
        'save_config',
        'get_config',
        'is_configured',
        'get_fees',
        'get_credential_url'
    );
    
    public function verify_all_gateways() {
        $gateway_manager = new SAPGS_GatewayManager();
        $all_gateways = $gateway_manager->get_all_gateways();
        
        $results = array(
            'total' => count($all_gateways),
            'passed' => 0,
            'failed' => 0,
            'gateways' => array()
        );
        
        foreach ($all_gateways as $gateway_id => $gateway) {
            $gateway_result = $this->verify_gateway($gateway_id, $gateway);
            $results['gateways'][$gateway_id] = $gateway_result;
            
            if ($gateway_result['status'] === 'passed') {
                $results['passed']++;
            } else {
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    private function verify_gateway($gateway_id, $gateway) {
        $result = array(
            'gateway_id' => $gateway_id,
            'name' => $gateway->get_name(),
            'status' => 'passed',
            'errors' => array(),
            'warnings' => array(),
            'checks' => array()
        );
        
        // Check 1: Implements interface
        if (!($gateway instanceof SAPGS_GatewayInterface)) {
            $result['status'] = 'failed';
            $result['errors'][] = 'Gateway does not implement SAPGS_GatewayInterface';
            return $result;
        }
        $result['checks']['implements_interface'] = true;
        
        // Check 2: All required methods exist
        foreach ($this->required_methods as $method) {
            if (!method_exists($gateway, $method)) {
                $result['status'] = 'failed';
                $result['errors'][] = "Missing required method: {$method}()";
            } else {
                $result['checks'][$method] = true;
            }
        }
        
        // Check 3: get_id() returns correct value
        $id = $gateway->get_id();
        if ($id !== $gateway_id) {
            $result['warnings'][] = "get_id() returns '{$id}' but gateway key is '{$gateway_id}'";
        }
        $result['checks']['id_matches'] = ($id === $gateway_id);
        
        // Check 4: get_name() returns non-empty string
        $name = $gateway->get_name();
        if (empty($name)) {
            $result['status'] = 'failed';
            $result['errors'][] = 'get_name() returns empty string';
        }
        $result['checks']['name_not_empty'] = !empty($name);
        
        // Check 5: get_description() returns non-empty string
        $description = $gateway->get_description();
        if (empty($description)) {
            $result['warnings'][] = 'get_description() returns empty string';
        }
        $result['checks']['description_not_empty'] = !empty($description);
        
        // Check 6: get_config_fields() returns array
        $config_fields = $gateway->get_config_fields();
        if (!is_array($config_fields)) {
            $result['status'] = 'failed';
            $result['errors'][] = 'get_config_fields() must return an array';
        } else {
            $result['checks']['config_fields_is_array'] = true;
            $result['checks']['config_fields_count'] = count($config_fields);
        }
        
        // Check 7: get_fees() returns correct structure
        $fees = $gateway->get_fees();
        if (!is_array($fees)) {
            $result['status'] = 'failed';
            $result['errors'][] = 'get_fees() must return an array';
        } elseif (!isset($fees['percentage']) || !isset($fees['fixed'])) {
            $result['status'] = 'failed';
            $result['errors'][] = 'get_fees() must return array with "percentage" and "fixed" keys';
        } else {
            $result['checks']['fees_structure'] = true;
            $result['checks']['fees_percentage'] = $fees['percentage'];
            $result['checks']['fees_fixed'] = $fees['fixed'];
        }
        
        // Check 8: get_credential_url() returns string
        $cred_url = $gateway->get_credential_url(false);
        if (!is_string($cred_url) || empty($cred_url)) {
            $result['warnings'][] = 'get_credential_url() should return a valid URL string';
        }
        $result['checks']['credential_url_valid'] = !empty($cred_url);
        
        // Check 9: is_configured() returns boolean
        $is_configured = $gateway->is_configured();
        if (!is_bool($is_configured)) {
            $result['warnings'][] = 'is_configured() should return a boolean';
        }
        $result['checks']['is_configured_returns_bool'] = is_bool($is_configured);
        
        // Check 10: get_config() returns array
        $config = $gateway->get_config();
        if (!is_array($config)) {
            $result['warnings'][] = 'get_config() should return an array';
        }
        $result['checks']['get_config_returns_array'] = is_array($config);
        
        // Check 11: test_connection() returns correct structure
        try {
            $test_result = $gateway->test_connection();
            if (!is_array($test_result)) {
                $result['status'] = 'failed';
                $result['errors'][] = 'test_connection() must return an array';
            } elseif (!isset($test_result['success'])) {
                $result['status'] = 'failed';
                $result['errors'][] = 'test_connection() must return array with "success" key';
            } else {
                $result['checks']['test_connection_structure'] = true;
            }
        } catch (Exception $e) {
            $result['warnings'][] = 'test_connection() threw exception: ' . $e->getMessage();
        }
        
        // Check 12: connect() returns correct structure
        try {
            $connect_result = $gateway->connect();
            if (!is_array($connect_result)) {
                $result['status'] = 'failed';
                $result['errors'][] = 'connect() must return an array';
            } elseif (!isset($connect_result['success'])) {
                $result['status'] = 'failed';
                $result['errors'][] = 'connect() must return array with "success" key';
            } else {
                $result['checks']['connect_structure'] = true;
            }
        } catch (Exception $e) {
            $result['warnings'][] = 'connect() threw exception: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    public function print_results($results) {
        echo "\n=== PAYMENT GATEWAY VERIFICATION RESULTS ===\n\n";
        echo "Total Gateways: {$results['total']}\n";
        echo "Passed: {$results['passed']}\n";
        echo "Failed: {$results['failed']}\n\n";
        
        foreach ($results['gateways'] as $gateway_id => $gateway_result) {
            $status_icon = $gateway_result['status'] === 'passed' ? '✓' : '✗';
            echo "{$status_icon} {$gateway_result['name']} ({$gateway_id})\n";
            
            if (!empty($gateway_result['errors'])) {
                echo "  ERRORS:\n";
                foreach ($gateway_result['errors'] as $error) {
                    echo "    - {$error}\n";
                }
            }
            
            if (!empty($gateway_result['warnings'])) {
                echo "  WARNINGS:\n";
                foreach ($gateway_result['warnings'] as $warning) {
                    echo "    - {$warning}\n";
                }
            }
            
            echo "\n";
        }
        
        if ($results['failed'] === 0) {
            echo "✓ All gateways passed verification!\n";
        } else {
            echo "✗ Some gateways failed verification. Please review errors above.\n";
        }
        echo "\n";
    }
}

// Run verification if executed directly
if (php_sapi_name() === 'cli' || (defined('WP_CLI') && WP_CLI)) {
    $verifier = new SAPGS_GatewayVerifier();
    $results = $verifier->verify_all_gateways();
    $verifier->print_results($results);
}

