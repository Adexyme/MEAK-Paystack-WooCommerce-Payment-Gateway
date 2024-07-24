<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
class Meak_Paystack_Gateway extends WC_Payment_Gateway
{

    // Constructor method
    public function __construct()
    {

        $wc_logger = wc_get_logger();

        $this->id = 'meak_paystack_gateway';
        $this->icon = '';
        $this->method_title = __('MEAK Paystack Paystack Gateway', 'meak-paystack-gateway');
        $this->method_description = __('Accept payments through Paystack numerous Online/Offline Payment Options - Credit Cards, USSD, Bank Transfer, Payment Link, Virtual Account e.t.c. <br>
    <b>Optional: </b>For a better performance, add the following URL to Your Paystack dashboard Webhook Option: <b><i>' . get_site_url() . '/wc-api/paystack_webhook</i></b>', 'meak-paystack-gateway');

        // Other initialization code goes here
        $this->order_id_prepend = 'MEAK';
        $this->order_id_append = 'G75PS';



        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->success_url = $this->get_option('success_url');
        $this->cancel_url = $this->get_option('cancel_url');
        $this->logo_url = $this->get_option('logo_url');
        //$this->ipn_url = plugin_dir_url( __FILE__ ) . 'ipn.php';

        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->private_key = $this->get_option('private_key');
        $this->publishable_key = $this->get_option('publishable_key');
        $this->test_private_key = $this->get_option('test_private_key');
        $this->test_publishable_key = $this->get_option('test_publishable_key');

        $this->public_key = ($this->testmode == 'yes') ? $this->test_publishable_key : $this->publishable_key;
        $this->secret_key = ($this->testmode == 'yes') ? $this->test_private_key : $this->private_key;

        $this->payment_init_url = ($this->testmode == 'yes') ? 'https://sandbox-api-d.paystackco.com/transaction/initiate' : 'https://api-d.paystackco.com/transaction/initiate';

        $this->payment_verification_url_stub = 'https://sandbox-api-d.paystackco.com/transaction/verify/';

        if (empty($this->success_url) || $this->success_url == null) {
            $this->success_url = get_site_url();
        }

        if (empty($this->cancel_url) || $this->cancel_url == null) {
            $this->cancel_url = get_site_url();
        }

        if (empty($this->logo_url) || $this->logo_url == null) {
            $this->logo_url = 'https://www.raastid.com/assets/images/logoIcon/light_logo.png';
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Register a callback url to be called immediately payment is successful, allowing to navigate back to the app for appropriate notification and processing.
        add_action('woocommerce_api_paystack_success_callback', array($this, 'paystack_success_callback_url'));

        // Register a webhook for payment success notification - this can be called by the Pay Proccessor any time and may be done more than once after payment
        add_action('woocommerce_api_paystack_webhook', array($this, 'paystack_webhook'));

    }

    /**
     * Summary of init_form_fields
     * @return void
     */

    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'meak-paystack-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable My Paystack Gateway', 'meak-paystack-gateway'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Title', 'meak-paystack-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'meak-paystack-gateway'),
                'default' => 'Paystack',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'meak-paystack-gateway'),
                'default' => 'Make payment using any of Paystack multiple payment channels',
            ),
            'testmode' => array(
                'title' => 'Test mode',
                'label' => 'Enable Test Mode',
                'type' => 'checkbox',
                'description' => 'Tick this to enable sandbox/test mode. According to Paystack\'s documentaion, test Card details are: ',
                'default' => 'yes',
            ),
            'success_url' => array(
                'title' => __('Success URL'),
                'description' => __('This is the URL where Paystack will redirect after payment was SUCCESSFUL. If you leave this field empty, it will be redirected to your site. Example: https://www.example.com/', 'meak-paystack-gateway'),
                'type' => 'text',
                'default' => get_site_url() . '/wc-api/paystack_success_callback'
            ),
            'cancel_url' => array(
                'title' => __('Cancel URL'),
                'description' => __('This is the URL where Paystack will redirect if payment was CANCELLED. If you leave this field empty, it will be redirected to your site. Example: https://www.example.com/', 'meak-paystack-gateway'),
                'type' => 'text',
                'default' => ''
            ),
            'logo_url' => array(
                'title' => __('Logo Url'),
                'description' => __('Your Site Logo URL. If you leave this field empty, it will use Paystack icon.  Example: https://www.example.com/image.png', 'meak-paystack-gateway'),
                'type' => 'text',
                'default' => 'http://localhost/wp_takora/wp-content/plugins/paystack-payment-gateway/assets/images/logo.png'
            ),
            'publishable_key' => array(
                'title' => __('Public Key'),
                'description' => __('Your Paystack Public Key', 'meak-paystack-gateway'),
                'type' => 'text',
                'default' => ''
            ),
            'private_key' => array(
                'title' => __('Private Key'),
                'description' => __('Your Paystack Private Key', 'meak-paystack-gateway'),
                'type' => 'password',
                'default' => ''
            ),
            'test_publishable_key' => array(
                'title' => __('Public test Key'),
                'description' => __('Your Paystack Public test Key', 'meak-paystack-gateway'),
                'type' => 'text',
                'default' => ''
            ),
            'test_private_key' => array(
                'title' => __('Private Key'),
                'description' => __('Your Paystack Private test Key', 'meak-paystack-gateway'),
                'type' => 'password',
                'default' => ''
            )
            // Add more settings fields as needed
        );
    }

    /**
     * Summary of process_payment
     * @param mixed $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        //global $woocommerce;

        //$wc_logger = wc_get_logger();

        //$wc_logger->debug('inside process_payment', array('source' => 'MAAK Debug'));

        $order = wc_get_order($order_id);
        $amount = $order->get_total() * 100;

        //$wc_logger->debug( 'debug tracker : made request', array( 'source' => 'MAAK Debug' ) );

        //$wc_logger->debug( 'ret currency : '.$order->get_currency().'made request', array( 'source' => 'MAAK Debug' ) );

        //if($order->get_currency() != 'NGN' || $order->get_currency() != 'USD') die('Your currency is not supported by Paystack - maak_payment_gateway/process_payment') ;

        //$wc_logger->debug('debug tracker2 : made request', array('source' => 'MAAK Debug'));
        $paystack_payment_params = array(
            'amount' => absint($amount),
            'email' => $order->get_billing_email(),
            'currency' => $order->get_currency(),
            "initiate_type" => "inline",
            'transaction_ref' => $this->order_id_prepend . '-' . $order_id . '-' . $this->order_id_append,
            'callback_url' => $this->get_option('success_url'),
        );
        //$wc_logger->debug( 'debug tracker3 : made request', array( 'source' => 'MAAK Debug' ) );


        $headers = array(
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Content-Type' => 'application/json'
        );

        $args = array(
            'headers' => $headers,
            'timeout' => 60,
            'body' => json_encode($paystack_payment_params)
        );

        $paystack_payment_init_url = $this->payment_init_url;

        //$wc_logger->debug('inside process_payment x : payload' . print_r($args, true), array('source' => 'MAAK Debug'));

        //$wc_logger->debug('inside process_payment y : url: ' . $paystack_payment_init_url, array('source' => 'MAAK Debug'));

        $request = wp_remote_post($paystack_payment_init_url, $args);

        // $wc_logger->debug( 'debug tracker4 : error code:'.wp_remote_retrieve_response_code( $request ), array( 'source' => 'MAAK Debug' ) );

        //$wc_logger->debug('inside process_payment 2 : request body' . print_r($request, true), array('source' => 'MAAK Debug'));

        if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {

            $paystack_response = json_decode(wp_remote_retrieve_body($request));
            //$wc_logger->debug('inside process_payment 3 : request succeed', array('source' => 'MAAK Debug'));
            return array(
                'result' => 'success',
                'redirect' => $paystack_response->data->checkout_url,
            );

        } else {
            //$wc_logger->debug('inside process_payment 3 : request failed', array('source' => 'MAAK Debug'));

            wp_redirect($this->get_return_url($order));

            //return;

        }

    }

    /**
     * Summary of verify_payment
     * @param mixed $order_id
     * @return array
     */
    protected function verify_payment($order_id)
    {

        $verification_url = $this->payment_verification_url_stub . $this->order_id_prepend . '-' . $order_id . '-' . $this->order_id_append;

        //$wc_logger = wc_get_logger();

        //$wc_logger->debug('Responding To Payment Verification call', array('source' => 'MEAK Verify Payment Debug'));
        //$wc_logger->debug('url: ' . $verification_url, array('source' => 'MEAK Verify Payment Debug'));

        $headers = array(
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Content-Type' => 'application/json'
        );

        $args = array(
            'headers' => $headers,
            'timeout' => 60
        );
        $request = wp_remote_get($verification_url, $args);

        //$wc_logger->debug('Responding To Payment Verification call : ret payload' . print_r($request, true), array('source' => 'MAAK Verify Payment Payload'));

        if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {

            $paystack_response = json_decode(wp_remote_retrieve_body($request));
            //$wc_logger->debug('inside verify_payment 1 : request succeed', array('source' => 'MAAK Verify Payment Debug'));

            if ($paystack_response->status == 200 && strtolower($paystack_response->data->transaction_status) == 'success') {
                $paystack_response = json_decode(wp_remote_retrieve_body($request));

                //$wc_logger->debug('inside verify_payment  : request succeed 2', array('source' => 'MAAK Verify Payment Debug'));

                return array(
                    'status' => true,
                    'amount_paid' => $paystack_response->data->transaction_amount,
                    'currency_symbol' => $paystack_response->data->transaction_currency_id
                );


            } else {

                $paystack_response = json_decode(wp_remote_retrieve_body($request));

                //$wc_logger->debug('inside verify_payment else : request failed 2', array('source' => 'MAAK Verify Payment Debug'));


                return array(
                    'status' => false,
                    'amount_paid' => '',
                    'currency_symbol' => ''
                );

            }

        } else {
            //$wc_logger->debug('inside verify_payment else : request failed 1', array('source' => 'MAAK Verify Payment Debug'));

            return array(
                'status' => false,
                'amount_paid' => '',
                'currency_symbol' => ''
            );

        }


    }




    public function paystack_webhook()
    {
        // Retrieve the request's body
        $json = @file_get_contents('php://input');
        $wc_logger = wc_get_logger();

        $wc_logger->debug('Responding To Payment WebHook', array('source' => 'MEAK WebHook Debug'));
        $wc_logger->debug('Payload: ' . print_r($json, true), array('source' => 'MEAK WebHook Debug'));
        //$orderid = explode('-', $_GET['reference']);

        //$order = wc_get_order($orderid[1]);
        //$order->payment_complete();
        //$order->reduce_order_stock();
        //wp_redirect($this->get_return_url($order));

    }

    public function paystack_success_callback_url()
    {

        $wc_logger = wc_get_logger();

        $wc_logger->debug('Responding To Payment Success URL', array('source' => 'MEAK Success Callback Debug'));
        $wc_logger->debug('reference: ' . $_GET['reference'], array('source' => 'MEAK Success Callback Debug'));
        $orderid = explode('-', $_GET['reference']);

        $arrLen = count($orderid);




        $order = wc_get_order($orderid[1]);

        //if order id is not in the required format, halt and redirect
        if ($arrLen != 3 || !is_int($orderid[1]))
            $this->get_return_url($order);

        //if payment verification failed, halt and redirect
        $verify = $this->verify_payment($orderid[1]);
        if (!$verify['status'])
            $this->get_return_url($order);

        $order_currency = method_exists($order, 'get_currency') ? $order->get_currency() : $order->get_order_currency();
        $currency_symbol = get_woocommerce_currency_symbol($order_currency);

        if ($order->get_total() > (floatval($verify['amount_paid']) / 100) || $verify['currency_symbol'] != $currency_symbol) {

            $order->update_status('on-hold', '');
            $order->reduce_order_stock();
            wp_redirect($this->get_return_url($order));
        }

        $order->payment_complete();
        $order->reduce_order_stock();


        wp_redirect($this->get_return_url($order));

    }

}
