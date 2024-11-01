<?php
/**
 * Plugin Name: Payment Gateway Zeus for WooCommerce
 * Plugin URI: https://www.wpmarket.jp/product/wc_zeus_gateway/
 * Description: Take Zeus payments on your store using Zeus for WooCommerce.
 * Author: Hiroaki Miyashita
 * Author URI: https://www.wpmarket.jp/
 * Version: 0.3
 * Requires at least: 4.4
 * Tested up to: 5.7.1
 * WC requires at least: 3.0
 * WC tested up to: 5.2.2
 * Text Domain: wc-zeus-gateway
 * Domain Path: /
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wc_zeus_gateway_missing_admin_notices() {
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Zeus requires WooCommerce to be installed and active. You can download %s here.', 'wc-zeus-gateway' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

function wc_zeus_gateway_mode_admin_notices() {
	echo '<div class="error"><p><strong><a href="https://www.wpmarket.jp/product/wc_zeus_gateway/?domain='.$_SERVER['HTTP_HOST'].'" target="_blank">'.__( 'In order to use Zeus, you have to purchase the authentication key at the following site.', 'wc-zeus-gateway' ).'</a></strong></p></div>';
}

add_action( 'plugins_loaded', 'wc_zeus_gateway_plugins_loaded' );
add_filter( 'woocommerce_payment_gateways', 'wc_zeus_gateway_woocommerce_payment_gateways' );

function wc_zeus_gateway_plugins_loaded() {
	load_plugin_textdomain( 'wc-zeus-gateway', false, plugin_basename( dirname( __FILE__ ) ) );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wc_zeus_gateway_missing_admin_notices' );
		return;
	}
	
	$zeus_option = get_option('woocommerce_zeus_credit_settings');
	if ( empty($zeus_option['authentication_key']) ) :
		add_action( 'admin_notices', 'wc_zeus_gateway_mode_admin_notices' );	
	endif;

	if ( ! class_exists( 'WC_Gateway_Zeus_Credit' ) ) :

		class WC_Gateway_Zeus_Credit extends WC_Payment_Gateway {
			
			public function __construct() {
				$this->id = 'zeus_credit';
				$this->method_title = __('Zeus - Credit Card', 'wc-zeus-gateway');
				$this->method_description = __('Enable the credit card payment by Zeus. You can change whole IP Codes and other settings here.', 'wc-zeus-gateway');
				$this->has_fields = false;
				$this->supports = array('refunds');
				
				$this->init_form_fields();
				$this->init_settings();
				
				$this->enabled = $this->get_option( 'enabled' );
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->clientip_credit = $this->get_option( 'clientip_credit' );
				$this->clientip_cvs = $this->get_option( 'clientip_cvs' );
				$this->clientip_ebank = $this->get_option( 'clientip_ebank' );
				$this->clientip_payeasy = $this->get_option( 'clientip_payeasy' );
				$this->clientip_kfgw = $this->get_option( 'clientip_kfgw' );
				$this->clientip_carrier = $this->get_option( 'clientip_carrier' );
				$this->clientip_edy = $this->get_option( 'clientip_edy' );
				$this->clientip_chocom = $this->get_option( 'clientip_chocom' );
				$this->status = $this->get_option( 'status' );
				$this->logging = $this->get_option( 'logging' );
				$this->authentication_key = $this->get_option( 'authentication_key' );
																				
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
				add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
				add_action( 'woocommerce_api_wc_zeus', array( $this, 'check_for_webhook' ) );
			}
			
			function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title'       => __( 'Enable/Disable', 'wc-zeus-gateway' ),
						'label'       => __( 'Enable Zeus - Credit Card', 'wc-zeus-gateway' ),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no',
					),
					'title' => array(
						'title'       => __( 'Title', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Credit Card', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __( 'Description', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the description which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Pay with your credit card', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'clientip_credit'    => array(
						'title' => __('IP Code (Credit)', 'wc-zeus-gateway'),
						'type' => 'text',
						'default' => '' 
					),
					'clientip_cvs'    => array(
						'title' => __('IP Code (CVS)', 'wc-zeus-gateway'),
						'type' => 'text',
						'default' => '' 
					),
					'clientip_ebank'    => array(
						'title' => __('IP Code (Money Omakase Service)', 'wc-zeus-gateway'),
						'type' => 'text',
						'default' => '' 
					),
					'clientip_payeasy'    => array(
						'title' => __('IP Code (Payeasy)', 'wc-zeus-gateway'),
						'type' => 'text',
						'default' => '' 
					),
					'clientip_kfgw'    => array(
						'title' => __('IP Code (Account Transfer)', 'wc-zeus-gateway'),
						'type' => 'text',
						'default' => '' 
					),
					'clientip_carrier'    => array(
						'title' => __('IP Code (Carrier)', 'wc-zeus-gateway'),
						'type' => 'text',
						'default' => '' 
					),
					'clientip_edy'    => array(
						'title' => __('IP Code (Edy)', 'wc-zeus-gateway'),
						'type' => 'text',
						'default' => '' 
					),
					'clientip_chocom'    => array(
						'title' => __('IP Code (Chocom)', 'wc-zeus-gateway'),
						'type' => 'text',
						'default' => '' 
					),
					'status'    => array(
						'title' => __('Status', 'wc-zeus-gateway'),
						'type' => 'select',
						'desc_tip'    => true,
						'default' => '',
						'options'     => array(
							'processing'  => __('Processing', 'wc-zeus-gateway'),
							'completed' => __('Completed', 'wc-zeus-gateway')
						)
					),
					'logging'    => array(
						'title'       => __( 'Logging', 'wc-zeus-gateway' ),
						'label'       => __( 'Log debug messages', 'wc-zeus-gateway' ),
						'type'        => 'checkbox',
						'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'wc-zeus-gateway' ),
						'default'     => 'no',
						'desc_tip'    => true,
					),
					'authentication_key'    => array(
						'title' => __('Authentication Key', 'wc-zeus-gateway'),
						'type' => 'text',
						'default' => '',
						'description' => '<a href="https://www.wpmarket.jp/product/wc_zeus_gateway/?domain='.$_SERVER['HTTP_HOST'].'" target="_blank">'.__( 'In order to use Zeus, you have to purchase the authentication key at the following site.', 'wc-zeus-gateway' ).'</a>',
					),
				);
			}
			
			function process_admin_options( ) {
				$this->init_settings();

				$post_data = $this->get_post_data();
				
				$check_value = $this->wc_zeus_gateway_check_authentication_key( $post_data['woocommerce_zeus_credit_authentication_key'] );
				if ( $check_value == false ) :
					$_POST['woocommerce_zeus_credit_authentication_key'] = '';
				endif;

				return parent::process_admin_options();
			}
			
			function wc_zeus_gateway_check_authentication_key( $auth_key ) {
				$request = wp_remote_get('https://www.wpmarket.jp/auth/?gateway=zeus&domain='.$_SERVER['HTTP_HOST'].'&auth_key='.$auth_key);
				if ( ! is_wp_error( $request ) && $request['response']['code'] == 200 ) :
					if ( $request['body'] == 1 ) :
						return true;
					else :
						return false;
					endif;
				else :
					return false;
				endif;
			}
			
			function process_payment( $order_id ) {
				$order = new WC_Order( $order_id );
				
				if ( empty($this->authentication_key) ) :
					wc_add_notice(  __( 'In order to use Zeus, you have to purchase the authentication key.', 'wc-zeus-gateway' ), 'error' );
					return;
				endif;

				return array(
					'result' => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}
			
			function receipt_page( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );
				
				echo '<p>' . __( 'Redirecting automatically to the payment screen by Zeus. If not, please push the following submit button.', 'wc-zeus-gateway' ) . '</p>';
				$woocommerce->cart->empty_cart();

				wc_enqueue_js( 'jQuery( "#submit-form" ).click();' );
				
				$billing_phone = $order->get_billing_phone();
				$billing_email = $order->get_billing_email();

				echo '<form action="' . 'https://linkpt.cardservice.co.jp/cgi-bin/credit/order.cgi' . '" method="post" accept-charset="Shift_JIS">
<input type="hidden" name="clientip" value="' . esc_attr( $this->clientip_credit ) . '">
<input type="hidden" name="money" value="' . esc_attr( $order->get_total() ) . '">
<input type="hidden" name="telno" value="' . esc_attr( $billing_phone ) . '">
<input type="hidden" name="email" value="' . esc_attr( $billing_email ) . '">
<input type="hidden" name="sendid" value="' . esc_attr( $order_id ) . '">
<input type="hidden" name="success_url" value="' . esc_attr( $this->get_return_url( $order ) ) . '">
<input type="hidden" name="success_str" value="' . esc_attr( __('Go back to the site', 'wc-zeus-gateway') ) .'">
<input type="hidden" name="failure_url" value="' . esc_attr( $order->get_checkout_payment_url(false) ) . '">
<input type="hidden" name="failure_str" value="' . esc_attr( __('Go back to the site', 'wc-zeus-gateway') ) . '">
<div class="btn-submit-payment">
<input type="submit" id="submit-form" value="' . esc_attr( __('To the payment screen', 'wc-zeus-gateway') ) . '">
</div>
</form>';
			}
			
			function check_for_webhook() {
				
				$ips = array( '210.164.6.67', '202.221.139.50' );
				if ( !in_array( $_SERVER['REMOTE_ADDR'], $ips ) ) return;
				
				if ( isset($_GET['clientip']) && !empty($_GET['sendid']) ) :
					$response = $this->mb_convert_encoding_recursive($_GET, 'UTF-8', 'SJIS');
					$this->logging( $response );
					foreach ( $response as $key => $val ) :
						$response[$key] = sanitize_text_field( $val );
					endforeach;
					$order = new WC_Order( $response['sendid'] );
					if ( !empty($order) ) :
						switch ( $response['clientip'] ) :
							case $this->clientip_credit :
								if ( empty($this->status) ) $this->status = 'processing';
								if ( isset($response['result']) && $response['result'] == 'OK' ) :
									$order->update_status( $this->status, sprintf( __( 'Zeus settlement completed (Order No: %s).', 'wc-zeus-gateway' ), $response['ordd'] ) );
								else :
									$order->update_status( 'failed', sprintf( __( 'Zeus settlement failed (Order No: %s).', 'wc-zeus-gateway' ), $response['ordd'] ) );
								endif;
								break;
							case $this->clientip_cvs :
								$zeus_option = get_option('woocommerce_zeus_cvs_settings');
								if ( empty($zeus_option['status']) ) $zeus_option['status'] = 'processing';
								if ( $response['status'] == '01' ) :
									$order->add_order_note( sprintf( __( 'Zeus settlement displayed (Order No: %s, Detail: %s).', 'wc-zeus-gateway' ), $response['order_no'], $response['pay_cvs']." ".$response['pay_no1']." ".$response['pay_no2'] ) );				
								elseif ( $response['status'] == '04' || $response['status'] == '05' ) :
									$order->update_status( $zeus_option['status'], sprintf( __( 'Zeus settlement completed (Order No: %s).', 'wc-zeus-gateway' ), $response['order_no'] ) );
								else :
									$order->update_status( 'failed', sprintf( __( 'Zeus settlement failed (Order No: %s, Status: %s, Error Code: %s).', 'wc-zeus-gateway' ), $response['order_no'], $response['status'], $response['error_code'] ) );
								endif;
								break;
							case $this->clientip_ebank :
								$zeus_option = get_option('woocommerce_zeus_ebank_settings');
								if ( empty($zeus_option['status']) ) $zeus_option['status'] = 'processing';
								if ( $response['status'] == '03' ) :
									$order->update_status( $zeus_option['status'], sprintf( __( 'Zeus settlement completed (Order No: %s, Tracking No: %s).', 'wc-zeus-gateway' ), $response['order_no'], $response['tracking_no'] ) );
								else :
									$order->update_status( 'failed', sprintf( __( 'Zeus settlement failed (Order No: %s, Error Code: %s).', 'wc-zeus-gateway' ), $response['order_no'], $response['error_message'] ) );
								endif;
								break;
							case $this->clientip_payeasy :
								$zeus_option = get_option('woocommerce_zeus_payeasy_settings');
								if ( empty($zeus_option['status']) ) $zeus_option['status'] = 'processing';
								if ( $response['status'] == '05' ) :
									$order->update_status( $zeus_option['status'], sprintf( __( 'Zeus settlement completed (Order No: %s).', 'wc-zeus-gateway' ), $response['order_no'] ) );
								else :
									$order->update_status( 'failed', sprintf( __( 'Zeus settlement failed (Order No: %s).', 'wc-zeus-gateway' ), $response['order_no'] ) );
								endif;
								break;
							case $this->clientip_kfgw :
								$zeus_option = get_option('woocommerce_zeus_kfgw_settings');
								if ( empty($zeus_option['status']) ) $zeus_option['status'] = 'processing';
								if ( $response['status'] == '02' ) :
									$order->update_status( $zeus_option['status'], sprintf( __( 'Zeus settlement completed (Order No: %s, Tracking No: %s, Account Name: %s).', 'wc-zeus-gateway' ), $response['order_no'], $response['tracking_no'], $response['acc_name'] ) );
								else :
									$order->update_status( 'failed', sprintf( __( 'Zeus settlement failed (Order No: %s).', 'wc-zeus-gateway' ), $response['order_no'] ) );
								endif;
								break;
							case $this->clientip_carrier :
								$zeus_option = get_option('woocommerce_zeus_carrier_settings');
								if ( empty($zeus_option['status']) ) $zeus_option['status'] = 'processing';
								if ( $response['status'] == '03' || $response['status'] == '07' ) :
									$order->update_status( $zeus_option['status'], sprintf( __( 'Zeus settlement completed (Order No: %s).', 'wc-zeus-gateway' ), $response['order_no'] ) );
								else :
									$order->update_status( 'failed', sprintf( __( 'Zeus settlement failed (Order No: %s, Error Code: %s).', 'wc-zeus-gateway' ), $response['order_no'], $response['error_code'] ) );
								endif;
								break;
							case $this->clientip_edy :
								$zeus_option = get_option('woocommerce_zeus_edy_settings');
								if ( empty($zeus_option['status']) ) $zeus_option['status'] = 'processing';
								if ( $response['status'] == '04' ) :
									$order->update_status( $zeus_option['status'], sprintf( __( 'Zeus settlement completed (Order No: %s, Tracking No: %s).', 'wc-zeus-gateway' ), $response['order_no'], $response['tracking_no'] ) );
								else :
									$order->update_status( 'failed', sprintf( __( 'Zeus settlement failed (Order No: %s).', 'wc-zeus-gateway' ), $response['order_no'] ) );
								endif;
								break;						
						endswitch;
					endif;
				endif;
			}
			
			function logging( $error ) {
				if ( !empty($this->logging) ) :
					$logger = wc_get_logger();
					$logger->debug( wc_print_r( $error, true ), array( 'source' => 'wc-zeus-gateway' ) );
				endif;
			}
			
			function mb_convert_encoding_recursive($mix, $toEncoding, $fromEncoding) {
    			if (is_string($mix)) {
					return mb_convert_encoding($mix, $toEncoding, $fromEncoding);
				}
				
				if (is_array($mix)) {
					foreach ($mix as $key => $var) {
						$mix[$key] = $this->mb_convert_encoding_recursive($var, $toEncoding, $fromEncoding);
					}
					return $mix;
 				}

				if (is_object($mix)) {
					$properties = get_object_vars($mix);
					foreach ($properties as $propertyName => $var) {
						$mix->$propertyName = $this->mb_convert_encoding_recursive($var, $toEncoding, $fromEncoding);
					}
					return $mix;
				}

				return $mix;
			}
			
		}

	endif;
	
	if ( ! class_exists( 'WC_Gateway_Zeus_Cvs' ) ) :

		class WC_Gateway_Zeus_Cvs extends WC_Payment_Gateway {			
			public function __construct() {
				$this->id = 'zeus_cvs';
				$this->method_title = __('Zeus - CVS', 'wc-zeus-gateway');
				$this->method_description = __('Enable the CVS payment by Zeus.', 'wc-zeus-gateway');
				$this->has_fields = false;
				$this->supports = array('refunds');
				
				$this->init_form_fields();
				$this->init_settings();
				
				$this->enabled = $this->get_option( 'enabled' );
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->status = $this->get_option( 'status' );

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
				add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			}
			
			function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title'       => __( 'Enable/Disable', 'wc-zeus-gateway' ),
						'label'       => __( 'Enable Zeus - CVS', 'wc-zeus-gateway' ),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no',
					),
					'title' => array(
						'title'       => __( 'Title', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'CVS', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __( 'Description', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the description which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Pay with CVS', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'status'    => array(
						'title' => __('Status', 'wc-zeus-gateway'),
						'type' => 'select',
						'desc_tip'    => true,
						'default' => '',
						'options'     => array(
							'processing'  => __('Processing', 'wc-zeus-gateway'),
							'completed' => __('Completed', 'wc-zeus-gateway')
						)
					),
				);
			}
			
			function process_payment( $order_id ) {
				$order = new WC_Order( $order_id );
				
				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				if ( empty($zeus_option['authentication_key']) ) :
					wc_add_notice(  __( 'In order to use Zeus, you have to purchase the authentication key.', 'wc-zeus-gateway' ), 'error' );
					return;
				endif;

				return array(
					'result' => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}
			
			function receipt_page( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );
				
				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				$clientip = !empty($zeus_option['clientip_cvs']) ? $zeus_option['clientip_cvs'] : '';
				
				echo '<p>' . __( 'Redirecting automatically to the payment screen by Zeus. If not, please push the following submit button.', 'wc-zeus-gateway' ) . '</p>';
				$woocommerce->cart->empty_cart();

				wc_enqueue_js( 'jQuery( "#submit-form" ).click();' );
				
				$billing_phone = $order->get_billing_phone();
				$billing_email = $order->get_billing_email();

				echo '<form action="' . 'https://linkpt.cardservice.co.jp/cgi-bin/cvs.cgi' . '" method="post" accept-charset="Shift_JIS">
<input type="hidden" name="clientip" value="' . esc_attr( $clientip ) . '">
<input type="hidden" name="act" value="order">
<input type="hidden" name="money" value="' . esc_attr( $order->get_total() ) . '">
<input type="hidden" name="telno" value="' . esc_attr( $billing_phone ) . '">
<input type="hidden" name="email" value="' . esc_attr( $billing_email ) . '">
<input type="hidden" name="sendid" value="' . esc_attr( $order_id ) . '">
<input type="hidden" name="siteurl" value="' . esc_attr( $this->get_return_url( $order ) ) .'">
<input type="hidden" name="sitestr" value="' . esc_attr( __('Go back to the site', 'wc-zeus-gateway') ) .'">
<div class="btn-submit-payment">
<input type="submit" id="submit-form" value="' . esc_attr( __('To the payment screen', 'wc-zeus-gateway') ) .'">
</div>
</form>';
			}
			
		}
	
	endif;
		
	if ( ! class_exists( 'WC_Gateway_Zeus_Ebank' ) ) :

		class WC_Gateway_Zeus_Ebank extends WC_Payment_Gateway {			
			public function __construct() {
				$this->id = 'zeus_ebank';
				$this->method_title = __('Zeus - Money Omakase Service', 'wc-zeus-gateway');
				$this->method_description = __('Enable the Money Omakase Service payment by Zeus.', 'wc-zeus-gateway');
				$this->has_fields = false;
				$this->supports = array('refunds');
				
				$this->init_form_fields();
				$this->init_settings();
				
				$this->enabled = $this->get_option( 'enabled' );
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->status = $this->get_option( 'status' );

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
				add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			}
			
			function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title'       => __( 'Enable/Disable', 'wc-zeus-gateway' ),
						'label'       => __( 'Enable Zeus - Money Omakase Service', 'wc-zeus-gateway' ),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no',
					),
					'title' => array(
						'title'       => __( 'Title', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Money Omakase Service', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __( 'Description', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the description which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Pay with a bank transfer', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'status'    => array(
						'title' => __('Status', 'wc-zeus-gateway'),
						'type' => 'select',
						'desc_tip'    => true,
						'default' => '',
						'options'     => array(
							'processing'  => __('Processing', 'wc-zeus-gateway'),
							'completed' => __('Completed', 'wc-zeus-gateway')
						)
					),
				);
			}
			
			function process_payment( $order_id ) {
				$order = new WC_Order( $order_id );
				
				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				if ( empty($zeus_option['authentication_key']) ) :
					wc_add_notice(  __( 'In order to use Zeus, you have to purchase the authentication key.', 'wc-zeus-gateway' ), 'error' );
					return;
				endif;

				if ( $order->get_total() < 200 ) :
					wc_add_notice(  sprintf(__( 'It is available if the total amount is over %s Yen.', 'wc-zeus-gateway' ), 200), 'error' );
					return;
				endif;
				
				return array(
					'result' => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}

			function receipt_page( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );
				
				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				$clientip = !empty($zeus_option['clientip_ebank']) ? $zeus_option['clientip_ebank'] : '';
				
				echo '<p>' . __( 'Redirecting automatically to the payment screen by Zeus. If not, please push the following submit button.', 'wc-zeus-gateway' ) . '</p>';
				$woocommerce->cart->empty_cart();

				wc_enqueue_js( 'jQuery( "#submit-form" ).click();' );
				
				$billing_phone = $order->get_billing_phone();
				$billing_email = $order->get_billing_email();

				echo '<form action="' . 'https://linkpt.cardservice.co.jp/cgi-bin/ebank.cgi' . '" method="post" accept-charset="Shift_JIS">
<input type="hidden" name="clientip" value="' . esc_attr( $clientip ) . '">
<input type="hidden" name="act" value="order">
<input type="hidden" name="money" value="' . esc_attr( $order->get_total() ) . '">
<input type="hidden" name="telno" value="' . esc_attr( $billing_phone ) . '">
<input type="hidden" name="email" value="' . esc_attr( $billing_email ) . '">
<input type="hidden" name="sendid" value="' . esc_attr( $order_id ) . '">
<input type="hidden" name="siteurl" value="' . esc_attr( $this->get_return_url( $order ) ) .'">
<input type="hidden" name="sitestr" value="' . esc_attr( __('Go back to the site', 'wc-zeus-gateway') ) .'">
<div class="btn-submit-payment">
<input type="submit" id="submit-form" value="' . esc_attr( __('To the payment screen', 'wc-zeus-gateway') ) .'">
</div>
</form>';
			}

		}
	
	endif;
	
	if ( ! class_exists( 'WC_Gateway_Zeus_Payeasy' ) ) :

		class WC_Gateway_Zeus_Payeasy extends WC_Payment_Gateway {			
			public function __construct() {
				$this->id = 'zeus_payeasy';
				$this->method_title = __('Zeus - Payeasy', 'wc-zeus-gateway');
				$this->method_description = __('Enable the Payeasy payment by Zeus.', 'wc-zeus-gateway');
				$this->has_fields = false;
				$this->supports = array('refunds');
				
				$this->init_form_fields();
				$this->init_settings();
				
				$this->enabled = $this->get_option( 'enabled' );
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->status = $this->get_option( 'status' );

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
				add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			}
			
			function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title'       => __( 'Enable/Disable', 'wc-zeus-gateway' ),
						'label'       => __( 'Enable Zeus - Payeasy', 'wc-zeus-gateway' ),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no',
					),
					'title' => array(
						'title'       => __( 'Title', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Payeasy', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __( 'Description', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the description which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Pay with Payeasy', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'status'    => array(
						'title' => __('Status', 'wc-zeus-gateway'),
						'type' => 'select',
						'desc_tip'    => true,
						'default' => '',
						'options'     => array(
							'processing'  => __('Processing', 'wc-zeus-gateway'),
							'completed' => __('Completed', 'wc-zeus-gateway')
						)
					),
				);
			}
			
			function process_payment( $order_id ) {
				$order = new WC_Order( $order_id );
				
				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				if ( empty($zeus_option['authentication_key']) ) :
					wc_add_notice(  __( 'In order to use Zeus, you have to purchase the authentication key.', 'wc-zeus-gateway' ), 'error' );
					return;
				endif;
				
				if ( $order->get_total() < 200 ) :
					wc_add_notice(  sprintf(__( 'It is available if the total amount is over %s Yen.', 'wc-zeus-gateway' ), 200), 'error' );
					return;
				endif;
				
				return array(
					'result' => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}
			
			function receipt_page( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );
				
				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				$clientip = !empty($zeus_option['clientip_payeasy']) ? $zeus_option['clientip_payeasy'] : '';
				
				echo '<p>' . __( 'Redirecting automatically to the payment screen by Zeus. If not, please push the following submit button.', 'wc-zeus-gateway' ) . '</p>';
				$woocommerce->cart->empty_cart();

				wc_enqueue_js( 'jQuery( "#submit-form" ).click();' );
				
				$billing_phone = $order->get_billing_phone();
				$billing_email = $order->get_billing_email();

				echo '<form action="' . 'https://linkpt.cardservice.co.jp/cgi-bin/cvs.cgi' . '" method="post" accept-charset="Shift_JIS">
<input type="hidden" name="clientip" value="' . esc_attr( $clientip ) . '">
<input type="hidden" name="act" value="order">
<input type="hidden" name="money" value="' . esc_attr( $order->get_total() ) . '">
<input type="hidden" name="telno" value="' . esc_attr( $billing_phone ) . '">
<input type="hidden" name="email" value="' . esc_attr( $billing_email ) . '">
<input type="hidden" name="sendid" value="' . esc_attr( $order_id ) . '">
<input type="hidden" name="siteurl" value="' . esc_attr( $this->get_return_url( $order ) ) .'">
<input type="hidden" name="sitestr" value="' . esc_attr( __('Go back to the site', 'wc-zeus-gateway') ) .'">
<div class="btn-submit-payment">
<input type="submit" id="submit-form" value="' . esc_attr( __('To the payment screen', 'wc-zeus-gateway') ) .'">
</div>
</form>';
			}
			
		}
	
	endif;


	if ( ! class_exists( 'WC_Gateway_Zeus_Kfgw' ) ) :

		class WC_Gateway_Zeus_Kfgw extends WC_Payment_Gateway {			
			public function __construct() {
				$this->id = 'zeus_kfgw';
				$this->method_title = __('Zeus - Account Transfer', 'wc-zeus-gateway');
				$this->method_description = __('Enable the account transfer payment by Zeus.', 'wc-zeus-gateway');
				$this->has_fields = false;
				$this->supports = array('refunds');
				
				$this->init_form_fields();
				$this->init_settings();
				
				$this->enabled = $this->get_option( 'enabled' );
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->status = $this->get_option( 'status' );

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
				add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			}
			
			function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title'       => __( 'Enable/Disable', 'wc-zeus-gateway' ),
						'label'       => __( 'Enable Zeus - Account Transfer', 'wc-zeus-gateway' ),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no',
					),
					'title' => array(
						'title'       => __( 'Title', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Account Transfer', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __( 'Description', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the description which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Pay with an account transfer', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'status'    => array(
						'title' => __('Status', 'wc-zeus-gateway'),
						'type' => 'select',
						'desc_tip'    => true,
						'default' => '',
						'options'     => array(
							'processing'  => __('Processing', 'wc-zeus-gateway'),
							'completed' => __('Completed', 'wc-zeus-gateway')
						)
					),
				);
			}
			
			function process_payment( $order_id ) {
				$order = new WC_Order( $order_id );
				
				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				if ( empty($zeus_option['authentication_key']) ) :
					wc_add_notice(  __( 'In order to use Zeus, you have to purchase the authentication key.', 'wc-zeus-gateway' ), 'error' );
					return;
				endif;
				
				return array(
					'result' => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}
			
			function receipt_page( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );
				
				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				$clientip = !empty($zeus_option['clientip_kfgw']) ? $zeus_option['clientip_kfgw'] : '';
				
				echo '<p>' . __( 'Redirecting automatically to the payment screen by Zeus. If not, please push the following submit button.', 'wc-zeus-gateway' ) . '</p>';
				$woocommerce->cart->empty_cart();

				wc_enqueue_js( 'jQuery( "#submit-form" ).click();' );
				
				$billing_phone = $order->get_billing_phone();
				$billing_email = $order->get_billing_email();

				echo '<form action="' . 'https://linkpt.cardservice.co.jp/cgi-bin/kfgw/order.cgi' . '" method="post" accept-charset="Shift_JIS">
<input type="hidden" name="clientip" value="' . esc_attr( $clientip ) . '">
<input type="hidden" name="act" value="order">
<input type="hidden" name="money" value="' . esc_attr( $order->get_total() ) . '">
<input type="hidden" name="telno" value="' . esc_attr( $billing_phone ) . '">
<input type="hidden" name="email" value="' . esc_attr( $billing_email ) . '">
<input type="hidden" name="sendid" value="' . esc_attr( $order_id ) . '">
<input type="hidden" name="success_url" value="' . esc_attr( $this->get_return_url( $order ) ) . '">
<input type="hidden" name="success_str" value="' . esc_attr( __('Go back to the site', 'wc-zeus-gateway') ) .'">
<input type="hidden" name="failure_url" value="' . esc_attr( $order->get_checkout_payment_url(false) ) . '">
<input type="hidden" name="failure_str" value="' . esc_attr( __('Go back to the site', 'wc-zeus-gateway') ) . '">
<div class="btn-submit-payment">
<input type="submit" id="submit-form" value="' . esc_attr( __('To the payment screen', 'wc-zeus-gateway') ) . '">
</div>
</form>';
			}

		}
	
	endif;

	if ( ! class_exists( 'WC_Gateway_Zeus_Carrier' ) ) :

		class WC_Gateway_Zeus_Carrier extends WC_Payment_Gateway {			
			public function __construct() {
				$this->id = 'zeus_carrier';
				$this->method_title = __('Zeus - Carrier', 'wc-zeus-gateway');
				$this->method_description = __('Enable the Carrier payment by Zeus.', 'wc-zeus-gateway');
				$this->has_fields = false;
				$this->supports = array('refunds');
				
				$this->init_form_fields();
				$this->init_settings();
				
				$this->enabled = $this->get_option( 'enabled' );
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->status = $this->get_option( 'status' );

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
				add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			}
			
			function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title'       => __( 'Enable/Disable', 'wc-zeus-gateway' ),
						'label'       => __( 'Enable Zeus - Carrier', 'wc-zeus-gateway' ),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no',
					),
					'title' => array(
						'title'       => __( 'Title', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Carrier', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __( 'Description', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the description which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Pay with your Carrier', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'status'    => array(
						'title' => __('Status', 'wc-zeus-gateway'),
						'type' => 'select',
						'desc_tip'    => true,
						'default' => '',
						'options'     => array(
							'processing'  => __('Processing', 'wc-zeus-gateway'),
							'completed' => __('Completed', 'wc-zeus-gateway')
						)
					),
				);
			}
			
			function process_payment( $order_id ) {
				$order = new WC_Order( $order_id );
				
				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				if ( empty($zeus_option['authentication_key']) ) :
					wc_add_notice(  __( 'In order to use Zeus, you have to purchase the authentication key.', 'wc-zeus-gateway' ), 'error' );
					return;
				endif;

				return array(
					'result' => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}
			
			function receipt_page( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );

				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				$clientip = !empty($zeus_option['clientip_carrier']) ? $zeus_option['clientip_carrier'] : '';

				echo '<p>' . __( 'Redirecting automatically to the payment screen by Zeus. If not, please push the following submit button.', 'wc-zeus-gateway' ) . '</p>';
				$woocommerce->cart->empty_cart();

				wc_enqueue_js( 'jQuery( "#submit-form" ).click();' );
				
				$billing_phone = $order->get_billing_phone();
				$billing_email = $order->get_billing_email();

				echo '<form action="' . 'https://linkpt.cardservice.co.jp/cgi-bin/carrier/order.cgi' . '" method="post" accept-charset="Shift_JIS">
<input type="hidden" name="clientip" value="' . esc_attr( $clientip ) . '">
<input type="hidden" name="money" value="' . esc_attr( $order->get_total() ) . '">
<input type="hidden" name="telno" value="' . esc_attr( $billing_phone ) . '">
<input type="hidden" name="email" value="' . esc_attr( $billing_email ) . '">
<input type="hidden" name="sendid" value="' . esc_attr( $order_id ) . '">
<input type="hidden" name="success_url" value="' . esc_attr( $this->get_return_url( $order ) ) . '">
<input type="hidden" name="success_str" value="' . esc_attr( __('Go back to the site', 'wc-zeus-gateway') ) .'">
<input type="hidden" name="failure_url" value="' . esc_attr( $order->get_checkout_payment_url(false) ) . '">
<input type="hidden" name="failure_str" value="' . esc_attr( __('Go back to the site', 'wc-zeus-gateway') ) . '">
<div class="btn-submit-payment">
<input type="submit" id="submit-form" value="' . esc_attr( __('To the payment screen', 'wc-zeus-gateway') ) . '">
</div>
</form>';
			}
			
		}
	
	endif;
	
	if ( ! class_exists( 'WC_Gateway_Zeus_Edy' ) ) :

		class WC_Gateway_Zeus_Edy extends WC_Payment_Gateway {			
			public function __construct() {
				$this->id = 'zeus_edy';
				$this->method_title = __('Zeus - Edy', 'wc-zeus-gateway');
				$this->method_description = __('Enable the Edy payment by Zeus.', 'wc-zeus-gateway');
				$this->has_fields = false;
				$this->supports = array('refunds');
				
				$this->init_form_fields();
				$this->init_settings();
				
				$this->enabled = $this->get_option( 'enabled' );
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->status = $this->get_option( 'status' );

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
				add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			}
			
			function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title'       => __( 'Enable/Disable', 'wc-zeus-gateway' ),
						'label'       => __( 'Enable Zeus - Edy', 'wc-zeus-gateway' ),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no',
					),
					'title' => array(
						'title'       => __( 'Title', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Edy', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __( 'Description', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the description which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Pay with your Edy', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'status'    => array(
						'title' => __('Status', 'wc-zeus-gateway'),
						'type' => 'select',
						'desc_tip'    => true,
						'default' => '',
						'options'     => array(
							'processing'  => __('Processing', 'wc-zeus-gateway'),
							'completed' => __('Completed', 'wc-zeus-gateway')
						)
					),
				);
			}
			
			function process_payment( $order_id ) {
				$order = new WC_Order( $order_id );
				
				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				if ( empty($zeus_option['authentication_key']) ) :
					wc_add_notice(  __( 'In order to use Zeus, you have to purchase the authentication key.', 'wc-zeus-gateway' ), 'error' );
					return;
				endif;
				
				if ( $order->get_total() < 100 ) :
					wc_add_notice(  sprintf(__( 'It is available if the total amount is over %s Yen.', 'wc-zeus-gateway' ), 100), 'error' );
					return;
				endif;

				return array(
					'result' => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}
			
			function receipt_page( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );

				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				$clientip = !empty($zeus_option['clientip_edy']) ? $zeus_option['clientip_edy'] : '';

				echo '<p>' . __( 'Redirecting automatically to the payment screen by Zeus. If not, please push the following submit button.', 'wc-zeus-gateway' ) . '</p>';
				$woocommerce->cart->empty_cart();

				wc_enqueue_js( 'jQuery( "#submit-form" ).click();' );
				
				$billing_phone = $order->get_billing_phone();
				$billing_email = $order->get_billing_email();

				echo '<form action="' . 'https://linkpt.cardservice.co.jp/cgi-bin/edy.cgi' . '" method="post" accept-charset="Shift_JIS">
<input type="hidden" name="clientip" value="' . esc_attr( $clientip ) . '">
<input type="hidden" name="act" value="order">
<input type="hidden" name="money" value="' . esc_attr( $order->get_total() ) . '">
<input type="hidden" name="telno" value="' . esc_attr( $billing_phone ) . '">
<input type="hidden" name="email" value="' . esc_attr( $billing_email ) . '">
<input type="hidden" name="sendid" value="' . esc_attr( $order_id ) . '">
<input type="hidden" name="success_url" value="' . esc_attr( $this->get_return_url( $order ) ) . '">
<input type="hidden" name="success_str" value="' . esc_attr( __('Go back to the site', 'wc-zeus-gateway') ) .'">
<input type="hidden" name="failure_url" value="' . esc_attr( $order->get_checkout_payment_url(false) ) . '">
<input type="hidden" name="failure_str" value="' . esc_attr( __('Go back to the site', 'wc-zeus-gateway') ) . '">
<div class="btn-submit-payment">
<input type="submit" id="submit-form" value="' . esc_attr( __('To the payment screen', 'wc-zeus-gateway') ) . '">
</div>
</form>';
			}
			
		}
	
	endif;
	
	if ( ! class_exists( 'WC_Gateway_Zeus_Chocom' ) ) :

		class WC_Gateway_Zeus_Chocom extends WC_Payment_Gateway {			
			public function __construct() {
				$this->id = 'zeus_chocom';
				$this->method_title = __('Zeus - Chocom', 'wc-zeus-gateway');
				$this->method_description = __('Enable the Chocom payment by Zeus.', 'wc-zeus-gateway');
				$this->has_fields = false;
				$this->supports = array('refunds');
				
				$this->init_form_fields();
				$this->init_settings();
				
				$this->enabled = $this->get_option( 'enabled' );
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->status = $this->get_option( 'status' );

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
				add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			}
			
			function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title'       => __( 'Enable/Disable', 'wc-zeus-gateway' ),
						'label'       => __( 'Enable Zeus - Chocom', 'wc-zeus-gateway' ),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no',
					),
					'title' => array(
						'title'       => __( 'Title', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Chocom', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __( 'Description', 'wc-zeus-gateway' ),
						'type'        => 'text',
						'description' => __( 'This controls the description which the user sees during checkout.', 'wc-zeus-gateway' ),
						'default'     => __( 'Pay with your Chocom', 'wc-zeus-gateway' ),
						'desc_tip'    => true,
					),
					'status'    => array(
						'title' => __('Status', 'wc-zeus-gateway'),
						'type' => 'select',
						'desc_tip'    => true,
						'default' => '',
						'options'     => array(
							'processing'  => __('Processing', 'wc-zeus-gateway'),
							'completed' => __('Completed', 'wc-zeus-gateway')
						)
					),
				);
			}
			
			function process_payment( $order_id ) {
				$order = new WC_Order( $order_id );
				
				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				if ( empty($zeus_option['authentication_key']) ) :
					wc_add_notice(  __( 'In order to use Zeus, you have to purchase the authentication key.', 'wc-zeus-gateway' ), 'error' );
					return;
				endif;

				return array(
					'result' => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}
			
			function receipt_page( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );

				$zeus_option = get_option('woocommerce_zeus_credit_settings');
				$clientip = !empty($zeus_option['clientip_chocom']) ? $zeus_option['clientip_chocom'] : '';

				echo '<p>' . __( 'Redirecting automatically to the payment screen by Zeus. If not, please push the following submit button.', 'wc-zeus-gateway' ) . '</p>';
				$woocommerce->cart->empty_cart();

				wc_enqueue_js( 'jQuery( "#submit-form" ).click();' );
				
				$billing_phone = $order->get_billing_phone();
				$billing_email = $order->get_billing_email();

				echo '<form action="' . 'https://linkpt.cardservice.co.jp/cgibin/chocom.cgi?orders' . '" method="post">
<input type="hidden" name="clientip" value="' . esc_attr( $clientip ) . '">
<input type="hidden" name="money" value="' . esc_attr( $order->get_total() ) . '">
<input type="hidden" name="telno" value="' . esc_attr( $billing_phone ) . '">
<input type="hidden" name="email" value="' . esc_attr( $billing_email ) . '">
<input type="hidden" name="sendid" value="' . esc_attr( $order_id ) . '">
<div class="btn-submit-payment">
<input type="submit" id="submit-form" value="' . esc_attr( __('To the payment screen', 'wc-zeus-gateway') ) .'">
</div>
</form>';
			}
			
		}
	
	endif;

}

function wc_zeus_gateway_woocommerce_payment_gateways( $methods ) {
	$methods[] = 'WC_Gateway_Zeus_Credit';
	$methods[] = 'WC_Gateway_Zeus_Cvs';
	$methods[] = 'WC_Gateway_Zeus_Ebank';
	$methods[] = 'WC_Gateway_Zeus_Payeasy';
	$methods[] = 'WC_Gateway_Zeus_Kfgw';
	$methods[] = 'WC_Gateway_Zeus_Carrier';
	$methods[] = 'WC_Gateway_Zeus_Edy';
	$methods[] = 'WC_Gateway_Zeus_Chocom';
	return $methods;
}
?>