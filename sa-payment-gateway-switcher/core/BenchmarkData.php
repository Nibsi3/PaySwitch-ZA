<?php
/**
 * Benchmark Data
 * 
 * Provides static sample averages for free-tier users
 * These are nationwide data snapshots, not store-specific
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAPGS_BenchmarkData {
    
    /**
     * Get benchmark success rates (nationwide averages)
     */
    public static function get_success_rates() {
        return array(
            'payfast' => array(
                'rate' => 94.5,
                'range' => '92-97%',
                'description' => 'Highly reliable with established infrastructure'
            ),
            'ozow' => array(
                'rate' => 96.2,
                'range' => '94-98%',
                'description' => 'Excellent for EFT payments'
            ),
            'yoco' => array(
                'rate' => 95.8,
                'range' => '93-97%',
                'description' => 'Strong performance for card payments'
            ),
            'peach_payments' => array(
                'rate' => 95.0,
                'range' => '93-97%',
                'description' => 'Consistent multi-channel performance'
            ),
            'paygate' => array(
                'rate' => 94.0,
                'range' => '92-96%',
                'description' => 'Reliable with good uptime'
            ),
            'paystack_za' => array(
                'rate' => 96.5,
                'range' => '94-98%',
                'description' => 'Modern infrastructure with high success rates'
            ),
            'snapscan' => array(
                'rate' => 93.5,
                'range' => '91-96%',
                'description' => 'Good for mobile payments'
            ),
            'zapper' => array(
                'rate' => 94.8,
                'range' => '92-97%',
                'description' => 'Solid mobile payment solution'
            ),
            'stitch' => array(
                'rate' => 96.0,
                'range' => '94-98%',
                'description' => 'High-performance payment infrastructure'
            ),
            'instant_eft' => array(
                'rate' => 97.5,
                'range' => '95-99%',
                'description' => 'Excellent for instant EFT with real-time verification'
            ),
            'payu' => array(
                'rate' => 95.5,
                'range' => '93-97%',
                'description' => 'Reliable international gateway with SA support'
            ),
            'ikhokha' => array(
                'rate' => 94.2,
                'range' => '92-96%',
                'description' => 'Growing SA provider with good performance'
            )
        );
    }
    
    /**
     * Get benchmark response times (milliseconds)
     */
    public static function get_response_times() {
        return array(
            'payfast' => array(
                'avg' => 450,
                'range' => '300-600ms',
                'description' => 'Standard response time'
            ),
            'ozow' => array(
                'avg' => 380,
                'range' => '250-500ms',
                'description' => 'Fast EFT processing'
            ),
            'yoco' => array(
                'avg' => 420,
                'range' => '280-580ms',
                'description' => 'Quick card authorization'
            ),
            'peach_payments' => array(
                'avg' => 480,
                'range' => '320-650ms',
                'description' => 'Multi-channel processing'
            ),
            'paygate' => array(
                'avg' => 500,
                'range' => '350-700ms',
                'description' => 'Standard processing time'
            ),
            'paystack_za' => array(
                'avg' => 350,
                'range' => '220-480ms',
                'description' => 'Fast modern API'
            ),
            'snapscan' => array(
                'avg' => 550,
                'range' => '400-750ms',
                'description' => 'QR code processing'
            ),
            'zapper' => array(
                'avg' => 520,
                'range' => '380-700ms',
                'description' => 'Mobile payment processing'
            ),
            'stitch' => array(
                'avg' => 360,
                'range' => '240-500ms',
                'description' => 'Optimized API performance'
            ),
            'instant_eft' => array(
                'avg' => 320,
                'range' => '200-450ms',
                'description' => 'Fast instant EFT processing'
            ),
            'payu' => array(
                'avg' => 400,
                'range' => '280-550ms',
                'description' => 'Standard international gateway response'
            ),
            'ikhokha' => array(
                'avg' => 440,
                'range' => '300-600ms',
                'description' => 'Standard processing time'
            )
        );
    }
    
    /**
     * Get benchmark fee ranges
     */
    public static function get_fee_ranges() {
        return array(
            'payfast' => array(
                'percentage' => '2.5-3.0%',
                'fixed' => 'R1.50-R2.50',
                'description' => 'Standard market rates'
            ),
            'ozow' => array(
                'percentage' => '2.5-3.0%',
                'fixed' => 'R1.00-R2.00',
                'description' => 'Competitive EFT rates'
            ),
            'yoco' => array(
                'percentage' => '2.7-3.2%',
                'fixed' => 'R0.00-R0.00',
                'description' => 'Percentage-only pricing'
            ),
            'peach_payments' => array(
                'percentage' => '2.5-3.0%',
                'fixed' => 'R0.00-R1.00',
                'description' => 'Flexible pricing structure'
            ),
            'paygate' => array(
                'percentage' => '2.8-3.5%',
                'fixed' => 'R1.50-R2.50',
                'description' => 'Standard pricing'
            ),
            'paystack_za' => array(
                'percentage' => '2.5-3.0%',
                'fixed' => 'R1.50-R2.50',
                'description' => 'Competitive rates'
            ),
            'snapscan' => array(
                'percentage' => '2.5-3.0%',
                'fixed' => 'R0.00-R1.00',
                'description' => 'Mobile-friendly pricing'
            ),
            'zapper' => array(
                'percentage' => '2.3-2.8%',
                'fixed' => 'R0.00-R1.00',
                'description' => 'Lower percentage rates'
            ),
            'stitch' => array(
                'percentage' => '2.3-2.8%',
                'fixed' => 'R0.00-R1.00',
                'description' => 'Competitive pricing'
            ),
            'instant_eft' => array(
                'percentage' => '1.5-2.0%',
                'fixed' => 'R0.00-R0.00',
                'description' => 'Lower fees - major advantage over cards'
            ),
            'payu' => array(
                'percentage' => '2.7-3.2%',
                'fixed' => 'R1.50-R2.50',
                'description' => 'Standard international rates'
            ),
            'ikhokha' => array(
                'percentage' => '2.5-3.0%',
                'fixed' => 'R1.00-R2.00',
                'description' => 'Competitive SA market rates'
            )
        );
    }
    
    /**
     * Get all benchmark data for a gateway
     */
    public static function get_gateway_benchmark($gateway_id) {
        return array(
            'success_rate' => self::get_success_rates()[$gateway_id] ?? null,
            'response_time' => self::get_response_times()[$gateway_id] ?? null,
            'fees' => self::get_fee_ranges()[$gateway_id] ?? null
        );
    }
    
    /**
     * Get all benchmarks for display
     */
    public static function get_all_benchmarks() {
        return array(
            'success_rates' => self::get_success_rates(),
            'response_times' => self::get_response_times(),
            'fee_ranges' => self::get_fee_ranges()
        );
    }
}

