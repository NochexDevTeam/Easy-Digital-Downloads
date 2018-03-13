<?php

/*
Plugin Name: Nochex Payment Gateway for Easy Digital Downloads
Description: Accept Nochex Payments, orders are updated using APC or Callback.
Version: 1
Author: Nochex Ltd
License: GPL2
*/

function nochex_failed_page_install() {
	
	$page = get_page_by_title( 'Transaction Failed' );
		if ( $page->ID != '' ){
			update_option('edd_cancel_page',$page->ID );
		}else{
			$failed_page = wp_insert_post(
				array(
					'post_title' => __('Transaction Failed', 'edd'),
					'post_content' => '[edd_cancel_page]',
					'post_status' => 'publish',
					'post_author' => 1,
					'post_type' => 'page',
					'comment_status' => 'closed'
				)
			);
			update_option('edd_cancel_page',$failed_page );
		}
}
register_activation_hook(__FILE__, 'nochex_failed_page_install');

function nochex_hide_failed_page($excludes) {
    
	$id = get_option( 'edd_cancel_page' );
    $excludes[] = $id;
    
  	sort($excludes);
    
  	return $excludes;
}
add_filter('wp_list_pages_excludes', 'nochex_hide_failed_page');

function nochex_failed_page_shortcode($atts, $content = null) {
	return nochex_failed_page_form();
}
add_shortcode('edd_cancel_page', 'nochex_failed_page_shortcode');

function nochex_failed_page_form() {

	ob_start(); 
	echo "<h2 style='color:red;'>".$_GET['error']."</h2>";
	return ob_get_clean();
	
}

// registers the gateway
function nochex_register_gateway($gateways) {
    global $edd_options;
	
	$gateways['nochex'] = array('admin_label' => 'Nochex', 'checkout_label' => "Credit / Debit Card (Nochex)");
	return $gateways;
}

add_filter('edd_payment_gateways', 'nochex_register_gateway');

function edd_nochex_remove_cc_form() {
ob_start(); ?>




	<fieldset>
		<legend><?php _e('Billing Address', 'nochex'); ?></legend>
		<p>
			<label class="edd-label"><?php _e('Billing Address Line 1', 'nochex'); ?></label>		
			<input type="text" name="edd_address" class="edd-address edd-input required" placeholder="<?php _e('Address', 'nochex'); ?>"/>

		</p>
		<p>
			<label class="edd-label"><?php _e('Billing Address Line 2', 'nochex'); ?></label>
			<input type="text" name="edd_address_2" class="edd-address-2 edd-input required" placeholder="<?php _e('Address 2', 'nochex'); ?>"/>
		</p>
		<p>
			<label class="edd-label"><?php _e('Billing City', 'nochex'); ?></label>		
			<input type="text" name="edd_city" class="edd-city edd-input required" placeholder="<?php _e('City', 'nochex'); ?>"/>
		</p>
		<p>
			<label class="edd-label"><?php _e('Billing Postcode', 'nochex'); ?></label>		
			<input type="text" name="edd_zip" class="edd-zip edd-input required" placeholder="<?php _e('Zip/Postcode', 'nochex'); ?>"/>
		</p>
		<p>
			<label class="edd-label"><?php _e('Phone Number', 'nochex'); ?></label>
			<input type="text" name="edd_phone" class="edd-phone edd-input required" placeholder="<?php _e('Phone', 'nochex'); ?>"/>
		</p>
		
		
	<img src="https://www.nochex.com/logobase-secure-images/logobase-banners/clear-amex-mp.png" style="max-width:300px;" />
	</fieldset>
	
	
	<?php
	echo ob_get_clean();
}

/*add_action( 'edd_nochex_cc_form', '__return_false' );*/
add_action( 'edd_nochex_cc_form', 'edd_nochex_remove_cc_form' );

// processes the payment
function nochex_process_payment($purchase_data) {
    global $edd_options;
    
    // check there is a gateway name
    if ( ! isset( $purchase_data['post_data']['edd-gateway'] ) )
    return;
    
    // collect payment data
    $payment_data = array( 
        'price'         => $purchase_data['price'], 
        'date'          => $purchase_data['date'], 
        'user_email'    => $purchase_data['user_email'], 
        'purchase_key'  => $purchase_data['purchase_key'], 
        /*'currency'      => $edd_options['currency'], */
        'downloads'     => $purchase_data['downloads'], 
        'user_info'     => $purchase_data['user_info'], 
        'cart_details'  => $purchase_data['cart_details'], 
        'status'        => 'pending'
     );
    
	//echo count($purchase_data['cart_details']);
	
		
    if (!$purchase_data['post_data']['edd_first'])
		edd_set_error( 'invalid_edd_first', __('First Name is not entered.', 'nochex') );
		
    if (!$purchase_data['post_data']['edd_last'])
		edd_set_error( 'invalid_edd_last', __('Last Name is not entered.', 'nochex') );
    		
    if (!$purchase_data['post_data']['edd_address'])
		edd_set_error( 'invalid_edd_address', __('First Name is not entered.', 'nochex') );
    		
    if (!$purchase_data['post_data']['edd_city'])
		edd_set_error( 'invalid_edd_city', __('City is not entered.', 'nochex') );
		
    if (!$purchase_data['post_data']['edd_zip'])
		edd_set_error( 'invalid_edd_zip', __('PostCode is not entered.', 'nochex') );
    
    if (!$purchase_data['post_data']['edd_phone'])
		edd_set_error( 'invalid_edd_phone', __('Phone Number is not entered.', 'nochex') );
	
 /*if (!$purchase_data['post_data']['edd_imei'])*
		edd_set_error( 'invalid_edd_imei', __('IMEI Number is not entered.', 'nochex') );*/
	
	$errors = edd_get_errors();
	
	if ( $errors ) {
        // problems? send back
		edd_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['edd-gateway']);
    }else{
	
	    // record the pending payment
    	$payment = edd_insert_payment( $payment_data );
		
	    // check payment
	    if ( !$payment ) {
	        // problems? send back
			edd_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['edd-gateway']);
	    } else {
			
			$returnurl = add_query_arg( 'payment-confirmation', 'nochex', get_permalink( $edd_options['success_page'] ) );
			
			$id = get_option( 'edd_cancel_page' );
			$permalink = get_permalink( $id );
			$message = urlencode('Transaction Error.');
			$cancelurl = $permalink;
			
			/*$edd_options['nochex_xml']*/
			
	if(edd_get_option( 'nochex_xml', false ) == 1){		
	$description = "Order for: " . $payment;
	
	$xmlCollection = "<items>";
	
	for($i=0, $n=sizeof($purchase_data['cart_details']); $i<$n; $i++){
	
	$xmlCollection .= "<item><id></id><name>".$purchase_data['cart_details'][$i]['name']."</name><description>".$purchase_data['cart_details'][$i]['name']."</description><quantity>".$purchase_data['cart_details'][$i]['quantity']."</quantity><price>".number_format($purchase_data['cart_details'][$i]['price'], 2, '.', '' )."</price></item>";
		
	}
	
	$xmlCollection .= "</items>";
	
	}else{
	
	$xmlCollection = "";
	$description = "";
	
	for($i=0, $n=sizeof($purchase_data['cart_details']); $i<$n; $i++){
	
	$description .= "Item Name: " . $purchase_data['cart_details'][$i]['name'] . ", Item Price: ".  number_format($purchase_data['cart_details'][$i]['price'], 2, '.', '' ) . " Quantity Ordered: " .  $purchase_data['cart_details'][$i]['quantity'];
	
	}
	
	}
			
			
			if (edd_get_option( 'nochex_test', false ) == 1){
			
			$nochexTest = "100";
			
			}else{
			
			$nochexTest = "0";
			
			}
			
			if (edd_get_option( 'nochex_hide', false ) == 1){
			
			$nochex_hide = "1";
			
			}else{
			
			$nochex_hide = "0";
			
			}
			
			if (edd_get_option( 'nochex_callback', false ) == 1){
			
			$callback = "1";
			
			}else{
			
			$callback = "";
			
			}
			
			
echo '<form action="https://secure.nochex.com/default.aspx" method="post" id="nochex_payment_form">				
	<input type="hidden" name="merchant_id" value="'.$edd_options['nochex_email'].'" />				
	<input type="hidden" name="amount" value="'.number_format($purchase_data['price'], 2, '.', '' ).'" />	 		
	<input type="hidden" name="xml_item_collection" value="'.$xmlCollection.'" />				
	<input type="hidden" name="description" value="'. sprintf(__('Order #%s' , 'nochex'), $payment) . " Items Ordered " . $description .'" />				
	<input type="hidden" name="order_id" value="'.$payment.'" />													
	<input type="hidden" name="optional_2" value="'.$callback.'" />							
	<input type="hidden" name="billing_fullname" value="'.$purchase_data['post_data']['edd_first'] .' '. $purchase_data['post_data']['edd_last'].'" />				
	<input type="hidden" name="billing_address" value="'.$purchase_data['post_data']['edd_address'].' '.$purchase_data['post_data']['edd_address_2'].'" />				
	<input type="hidden" name="billing_city" value="'.$purchase_data['post_data']['edd_city'].'" />				
	<input type="hidden" name="billing_postcode" value="'.$purchase_data['post_data']['edd_zip'].'" />			
	<input type="hidden" name="email_address" value="'.$purchase_data['post_data']['edd_email'].'" />				
	<input type="hidden" name="customer_phone_number" value="'.$purchase_data['post_data']['edd_phone'].'" />				
	<input type="hidden" name="success_url" value="'.$returnurl.'" />				
	<input type="hidden" name="hide_billing_details" value="'.$nochex_hide.'" />				
	<input type="hidden" name="callback_url" value="'.trailingslashit(home_url()).'?nochex=apc'.'" />				
	<input type="hidden" name="cancel_url" value="'.$cancelurl.'" />				
	<input type="hidden" name="test_success_url" value="'.$returnurl.'" />				
	<input type="hidden" name="test_transaction" value="'.$nochexTest.'" />				
	<input type="submit" class="button-alt" id="submit_nochex_payment_form" value="'.__('Pay via Nochex', 'nochex').'" /> 				
	</form>';		
	

	    }
		
	}
	
}
add_action('edd_gateway_nochex', 'nochex_process_payment');

function nochex_apc() {

	if ( !empty($_GET['nochex']) && $_GET['nochex'] == 'apc' ) {
	
	if ($_POST['optional_2'] == "1"){
	
			$urlencoded = "";
		foreach ($_POST as $Index => $Value)
		$urlencoded .= urlencode($Index ) . "=" . urlencode($Value) . "&";
		$urlencoded = substr($urlencoded,0,-1);
				
		$response = wp_remote_post('https://secure.nochex.com/callback/callback.aspx', array(
								'method' => 'POST',
								'timeout' => 45,
								'redirection' => 5,
								'httpversion' => '1.0',
								'blocking' => true,
								'headers' => array(	'content-type' => 'application/x-www-form-urlencoded',
													'host' => 'secure.nochex.com',
													'content-length' => strlen($urlencoded)),
								'body' => $urlencoded,
								'cookies' => array()
							    ));
			if ($_POST['transaction_status'] == "100"){
			$status = " TEST";
			}else{
			$status = " LIVE";
			}
										
	    if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) { 
			 
			 //$transData = "--" . ;
       	        
		    $payment_meta = get_post_meta($_POST['order_id'], '_edd_payment_meta', true );
					
		    if (strtolower($response['body']) == strtolower('AUTHORISED')) {  
			    
				edd_update_payment_status($_POST['order_id'], 'publish');
				
				$orderNotes = "Payment has been " . strtolower($response['body']) . " for order " . $_POST['order_id'] . ", and this was a " . $status . " transaction, the transaction id for this order is " . $_POST['transaction_id'];
				
				edd_insert_payment_note($_POST['order_id'], __( $orderNotes, 'easy-digital-downloads' ) );     
			}else{
			
				edd_update_payment_status($_POST['order_id'], 'publish');
				
				$orderNotes = "Payment has been " . strtolower($response['body']) . " for order " . $_POST['order_id'] . ", and this was a " . $status . " transaction, the transaction id for this order is " . $_POST['transaction_id'];
				
				edd_insert_payment_note($_POST['order_id'], __( $orderNotes, 'easy-digital-downloads' ) );     
			}
			
			}
	
	}else{
		$urlencoded = "";
		foreach ($_POST as $Index => $Value)
		$urlencoded .= urlencode($Index ) . "=" . urlencode($Value) . "&";
		$urlencoded = substr($urlencoded,0,-1);
				
		$response = wp_remote_post('https://www.nochex.com/apcnet/apc.aspx', array(
								'method' => 'POST',
								'timeout' => 45,
								'redirection' => 5,
								'httpversion' => '1.0',
								'blocking' => true,
								'headers' => array(	'content-type' => 'application/x-www-form-urlencoded',
													'content-length' => strlen($urlencoded)),
								'body' => $urlencoded,
								'cookies' => array()
							    ));
									
	    if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) { 
			 
			 //$transData = "--" . ;
       	        
		    $payment_meta = get_post_meta($_POST['order_id'], '_edd_payment_meta', true );
					
		    if (strtolower($response['body']) == strtolower('AUTHORISED')) {  
			    
				edd_update_payment_status($_POST['order_id'], 'publish');
				
				$orderNotes = "Payment has been " . strtolower($response['body']) . " for order " . $_POST['order_id'] . ", and this was a " . $_POST['status'] . " transaction, the transaction id for this order is " . $_POST['transaction_id'];
				
				edd_insert_payment_note($_POST['order_id'], __( $orderNotes, 'easy-digital-downloads' ) );     
			}else{
			
				edd_update_payment_status($_POST['order_id'], 'publish');
				
				$orderNotes = "Payment has been " . strtolower($response['body']) . " for order " . $_POST['order_id'] . ", and this was a " . $_POST['status'] . " transaction, the transaction id for this order is " . $_POST['transaction_id'];
				
				edd_insert_payment_note($_POST['order_id'], __( $orderNotes, 'easy-digital-downloads' ) );     
			}
			
			}
		}
	}	
}
add_action( 'init', 'nochex_apc' );

/**
 * Add Gateway subsection
 *
 * @since 1.0.1
 * @param array  $sections Gateway subsections
 *
 * @return array
 */
function nochex_settings_section( $sections ) {
	$sections['nochex'] = __( 'Nochex', 'nochex' );

	return $sections;
}
add_filter( 'edd_settings_sections_gateways', 'nochex_settings_section' , 10, 1 );

function nochex_add_settings($settings) {
 
	$nochex_settings = array(
		array(
			'id' => 'nochex_settings',
			'name' => '<strong>' . __('Nochex Payment Settings', 'nochex') . '</strong>',
			'desc' => __('Configure the gateway settings', 'nochex'),
			'type' => 'header'
		),
		array(
			'id' => 'nochex_display_name',
			'name' => __('Payment Display Name', 'nochex'),
			'desc' => __('Please enter your Nochex Display Name; this is needed in order to take payment.', 'nochex'),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'nochex_email',
			'name' => __('Merchant ID or Email Address', 'nochex'),
			'desc' => __('Please enter your Nochex Email Address or Merchant ID, for example: test123 or test123@example.com.', 'nochex'),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'nochex_test',
			'name' => __('Test Transaction', 'nochex'),
			'desc' => __('Testing Mode, Used to test that your shopping cart is working. Leave disabled for live transactions.', 'nochex'),
			'type' => 'checkbox'
		),
		array(
			'id' => 'nochex_hide',
			'name' => __('Hide Billing Details', 'nochex'),
			'desc' => __('Hide Billing Details Option, Used to hide the billing details. ', 'nochex'),
			'type' => 'checkbox'
		),
		array(
			'id' => 'nochex_xml',
			'name' => __('Detailed Product Information', 'nochex'),
			'desc' => __('Display your product details in a structured format on your Nochex Payment Page. ', 'nochex'),
			'type' => 'checkbox'
		),
		array(
			'id' => 'nochex_callback',
			'name' => __('Callback', 'nochex'),
			'desc' => __('To use the callback functionality, please contact Nochex Support to enable this functionality on your merchant account otherwise this function wont work.', 'nochex'),
			'type' => 'checkbox'
		)
	);
	
	if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
		$nochex_settings = array( 'nochex' => $nochex_settings );
	}
 
	return array_merge($settings, $nochex_settings);	
}
add_filter('edd_settings_gateways', 'nochex_add_settings');
