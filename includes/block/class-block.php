<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Meak_Paystack_Gateway_Block extends AbstractPaymentMethodType
{

    private $gateway;
    protected $name = 'meak_paystack_gateway';// your payment gateway name

    /**
     * Summary of initialize
     * @return void
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_meak_paystack_gateway_settings', []);
        $this->gateway = new Meak_Paystack_Gateway();
    }

    /**
     * Summary of is_active
     * @return mixed
     */
    public function is_active()
    {
        return $this->gateway->is_available();
    }

    /**
     * Summary of get_payment_method_script_handles
     * @return string[]
     */
    public function get_payment_method_script_handles()
    {

        wp_register_script(
            'meak_paystack_gateway-blocks-integration',
            plugin_dir_url(__FILE__) . '/js/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('meak_paystack_gateway-blocks-integration');

        }
        return ['meak_paystack_gateway-blocks-integration'];
    }

    /**
     * Summary of get_payment_method_data
     * @return array
     */
    public function get_payment_method_data()
    {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'logo_url' => $this->gateway->logo_url,
        ];
    }

}
