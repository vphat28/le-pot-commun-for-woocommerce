<?php
/**
* Plugin Name: Le Pot Commun payment
* Plugin URI: https://sundaysea.com/
* Description: Add Le Pot Commun payment to WooCommerce.
* Version: 1.0
* Author: Xavi
* Author URI:
* License GPL2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

define ('LPC_PLUGIN',dirname(__FILE__));

add_action('init', 'lepotStartSession', 1);
add_action('wp_logout', 'lepotEndSession');
add_action('wp_login', 'lepotEndSession');

function lepotStartSession() {
    if(!session_id()) {
        session_start();
    }
    if ( $_REQUEST['lpc_payment'] == '1' ) {
        $_SESSION['lpc_payment'] = 1;
    }
}

function lepotEndSession() {
    session_destroy();
}

 

spl_autoload_register(
                function ($name)
                {
                    @$name = explode ('_', $name);
                    @$name = $name[1];
                    @include_once (LPC_PLUGIN . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'LPC' . DIRECTORY_SEPARATOR . $name . '.php');
                });
function le_pot_commun_order_changed( $order_id, $old_status, $new_status ) {
 
    
    if ( $new_status == 'refunded' )
    {
    
     
            $lpcTnxId = get_post_meta( $order_id, 'lpc_transaction_id', true );
            $order = wc_get_order($order_id);  
            $refund = get_post_meta( $order_id, 'lpc_refund_amount', true );            
            $amount = $order->get_total() - $refund; 
       
           
            if ( $amount > 0  and !empty($lpcTnxId) )
            {   
                $gateway = new WC_Gateway_Le_Pot_Commun(); 
                if ( $gateway->get_option('testmode') == 'yes' ) {
                    $mode = 'testing';
                }
                else
                {
                    $mode = 'production';
                }
             
            
            
                $callback_url = urlencode(str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'wc_gateway_le_pot_commun', home_url( '/' ) ) ) );

                LPC_Configuration::setEnvironment( $mode ,
                    array(
                        'okUrl'          => $order->get_checkout_order_received_url(),
                        'koUrl'          => $order->get_checkout_payment_url(),
                        'notificationUrl'=> $callback_url
                    ));
                  
                $OCReq = array();
                $OCReq ['currency'] = get_woocommerce_currency();
                $OCReq ['merchantId'] = LPC_Configuration::getMerchantId();
                $OCReq ['transactionId'] =  'ref' . $order_id . '_' . time();
                $OCReq ['originalLPCTransactionId'] = $order->get_transaction_id();
                $OCReq ['amount'] = $amount * 100;

           try {
               
                $result = LPC_Order::cancel($OCReq);
                  
                $refund = get_post_meta( $order_id, 'lpc_refund_amount', true );
                 $refund += $amount;
                 update_post_meta($order_id, 'lpc_refund_amount', $refund ); 
              
                $order->add_order_note( __( 'Order was fully refunded. LPC Transaction ID: ', 'le-pot-commun-woocommerce' ) . $result['lpcTransactionId'] );
                
           } catch ( Exception $e ) {
               var_dump( $e );
               die;
           } 
                
                
            }
             
         
        
    }
}

add_action( 'plugins_loaded', 'init_le_pot_commun_gateway_class' );
add_action( 'plugins_loaded', 'le_pot_commun_load_textdomain' ); 
add_action( 'woocommerce_order_status_changed', 'le_pot_commun_order_changed',1, 3 );

/**
 * Load plugin textdomain. 
 woocommerce_order_status_changed
 */
function le_pot_commun_load_textdomain() {
  $lang = load_plugin_textdomain( 'le-pot-commun-woocommerce', false,  basename( dirname( __FILE__ ) ) . '/languages/' );
   
}
function init_le_pot_commun_gateway_class()
{
    class WC_Gateway_Le_Pot_Commun extends WC_Payment_Gateway
    {
        static $testingmode = '';
        
        function __construct()
        {
              
            
            $this->id = 'le_pot_payment';
            $this->has_fields = true;
            $this->method_title = __('Le Pot Payment' , 'le-pot-commun-woocommerce') ;
            $this->method_description = __('Add Le Pot Commun payment to WooCommerce.', 'le-pot-commun-woocommerce');
            $this->init_form_fields();
            $this->init_settings();
            $this->currency = get_woocommerce_currency();
            $this->description = $this->get_option( 'description' );
            $this->title = $this->get_option( 'title' );
            $this->public_mode = $this->get_option( 'public_mode' );
            
            WC_Gateway_Le_Pot_Commun::$testingmode = $this->get_option( 'testmode' );
            LPC_Configuration::setApiKey($this->get_option( 'merchant_key' ));
            LPC_Configuration::setMerchantId($this->get_option( 'merchant_id' ));

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array($this,'process_admin_options' ) );
            add_action( 'woocommerce_api_wc_gateway_le_pot_commun', array($this,'check_ipn_response' ) );
            if ( $this->public_mode != 'yes' ) {
                $flag = false;
       
                
                if ( isset($_SESSION['lpc_payment']) ) {
                    
                         
            
                    if ( $_SESSION['lpc_payment'] == '1' ) $flag = true;
                }
                if ( !$flag )
                    $this->enabled = false;
            }
        }

        public function check_ipn_response( )
        {
            $order_id = intval( $_REQUEST['transactionId'] );
            $order    = wc_get_order($order_id);
            if (!empty($_REQUEST['lpcTransactionId']) and $_REQUEST['status'] == 'OK')
            {
                $this->payment_complete($order, $_REQUEST['lpcTransactionId']);
                update_post_meta($order_id, 'lpc_transaction_id', $_REQUEST['lpcTransactionId']); 
            }
            die;
        }

        public function process_refund( $order_id, $amount = null, $reason = '' )
        { 
        /*
        echo wc_price( $order->get_total() - $order->get_total_refunded(), array( 'currency' => $order->get_order_currency() ) 
        */
            $order = wc_get_order( $order_id );

            if ( ! $this->can_refund_order( $order ) )
            {
                $this->log( 'Refund Failed: No transaction ID' );
                return new WP_Error( 'error', __( 'Refund Failed: No transaction ID', 'woocommerce' ) );
            }
 if ( $this->get_option('testmode') == 'yes' ) {
                $mode = 'testing';
            }
            else
            {
                $mode = 'production';
            }
             
            
            try {
                $callback_url = urlencode(str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'wc_gateway_le_pot_commun', home_url( '/' ) ) ) );

                LPC_Configuration::setEnvironment( $mode ,
                    array(
                        'okUrl'          => $order->get_checkout_order_received_url(),
                        'koUrl'          => $order->get_checkout_payment_url(),
                        'notificationUrl'=> $callback_url
                    ));
                $OCReq = array();
                $OCReq ['currency'] = get_woocommerce_currency();
                $OCReq ['merchantId'] = LPC_Configuration::getMerchantId();
                $OCReq ['transactionId'] =  'ref' . $order_id . '_' . time();
                $OCReq ['originalLPCTransactionId'] = $order->get_transaction_id();
                $OCReq ['amount'] = $amount * 100;

                
                $result = LPC_Order::cancel($OCReq);
                
                if ( $amount < $order->get_total() ) {
                    $order->add_order_note( __( 'Order was partial refunded. LPC Transaction ID: ', 'le-pot-commun-woocommerce' ) . $result['lpcTransactionId'] . __('Reason: ', 'le-pot-commun-woocommerce') . $reason);
                } else {
                    $order->add_order_note( __( 'Order was fully refunded. LPC Transaction ID: ', 'le-pot-commun-woocommerce' ) . $result['lpcTransactionId'] . __('Reason: ', 'le-pot-commun-woocommerce') . $reason);
                }
                
                 $refund = get_post_meta( $order_id, 'lpc_refund_amount', true );
                 $refund += $amount;
                 update_post_meta($order_id, 'lpc_refund_amount', $refund ); 
                 
                 
                return true;
            } catch (Exception $e) {
                 return new WP_Error( 'error', __( 'Refund Failed: No transaction ID', 'woocommerce' ) );
                
            }
        }

        public function can_refund_order( $order )
        {
            return $order && $order->get_transaction_id();
        }

        public function supports( $feature )
        {
            if ( $feature == 'refunds' )
            {
                return true;
            }
            $supports = apply_filters( 'woocommerce_payment_gateway_supports', in_array( $feature, $this->supports ) ? true : false, $feature, $this );
            return $supports;
        }



        protected function payment_complete( $order, $txn_id = '', $note = '' )
        {
            $order->add_order_note( __( 'Order was paid by Le Pot Commun. LPC Transaction ID: ', 'le-pot-commun-woocommerce' ) . $txn_id);
            $order->payment_complete( $txn_id );
        }

        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled'           => array(
                    'title'  => __( 'Enable/Disable', 'woocommerce' ),
                    'type'   => 'checkbox',
                    'label'  => __( 'Enable', 'woocommerce' ),
                    'default'=> 'yes'
                ),
                'testmode'         => array(
                    'title'  => __( 'Test Mode', 'woocommerce' ),
                    'type'   => 'checkbox',
                    'label'  => __( 'Enable', 'woocommerce' ),
                    'default'=> 'yes'
                ),
                'title'               => array(
                    'title'      => __( 'Title', 'woocommerce' ),
                    'type'       => 'text',
                    'description'=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                    'default'    => __( '', 'woocommerce' ),
                    'desc_tip'   => true,
                ),
                'description'   => array(
                    'title'  => __( 'Customer Message', 'woocommerce' ),
                    'type'   => 'textarea',
                    'default'=> ''
                ),
                'merchant_id'   => array(
                    'title'      => __( 'Merchant ID', 'le-pot-commun-woocommerce' ),
                    'type'       => 'text',
                    'description'=> __( '', 'woocommerce' ),
                    'default'    => __( '', 'woocommerce' ),
                    'desc_tip'   => true,
                ),
                'merchant_key' => array(
                    'title'      => __( 'Merchant Key', 'le-pot-commun-woocommerce' ),
                    'type'       => 'text',
                    'description'=> __( '', 'woocommerce' ),
                    'default'    => __( '', 'woocommerce' ),
                    'desc_tip'   => true,
                ),
                'set_domain'     => array(
                    'title'      => __( 'Domain', 'le-pot-commun-woocommerce' ),
                    'type'       => 'text',
                    'description'=> __( '', 'woocommerce' ),
                    'default'    => __( '', 'woocommerce' ),
                    'desc_tip'   => true,
                ),
                'public_mode'   => array(
                    'title'  => __( 'Public or Private', 'le-pot-commun-woocommerce' ),
                    'type'   => 'checkbox',
                    'label'  => __( 'Public', 'woocommerce' ),
                    'default'=> 'yes'
                )

            );
        }

        function process_payment( $order_id )
        {
            global $woocommerce;
            $order = new WC_Order( $order_id );

            if ( $this->get_option('testmode') == 'yes' ) {
                $mode = 'testing';
            }
            else
            {
                $mode = 'production';
            }

            $callback_url = urlencode(str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'wc_gateway_le_pot_commun', home_url( '/' ) ) ) );

            LPC_Configuration::setEnvironment( $mode ,
                array(
                    'okUrl'          => $order->get_checkout_order_received_url(),
                    'koUrl'          => $order->get_checkout_payment_url(),
                    'notificationUrl'=> $callback_url
                ));
            $sentData = array();
            $sentData['transactionId'] = 'tnx'.$order->id.'_'.time();
            $sentData['merchantId'] = LPC_Configuration::getMerchantId();
            $order->amount = $order->get_total();
            $sentData['amount'] = $order->amount * 100;
            $sentData['currency'] = $this->currency ;

            $order = (array)$order;



            $response = LPC_Order::create($sentData);

            if ( !empty($response['paymentPageUrl']) )
            {
                $woocommerce->cart->empty_cart();
                // Remove cart
            }



            // Return thankyou redirect
            return array(
                'result'  => 'success',
                'redirect'=> $response['paymentPageUrl']
            );


            //if fail
            /*
            wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
            return;
            */
        }
    }
}

function add_le_pot_commun_gateway_class( $methods )
{
    $methods[] = 'WC_Gateway_Le_Pot_Commun';
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_le_pot_commun_gateway_class' );

