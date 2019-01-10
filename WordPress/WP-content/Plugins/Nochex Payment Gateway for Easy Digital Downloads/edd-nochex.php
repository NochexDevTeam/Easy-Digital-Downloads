<?php
/*
Plugin Name: Nochex Payment Gateway for Easy Digital Downloads
Plugin URI: https://github.com/NochexDevTeam/Easy-Digital-Downloads
Description: Accept Nochex Payments in Easy Digital Downloads.
Version: 1.3
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

function edd_nochex_remove_cc_form() {
	ob_start();
	
	$user_id = get_current_user_id();		
	$address = get_user_meta( $user_id );
	
	 ?>
	<fieldset>
		<legend><?php _e('Billing Address', 'nochex'); ?></legend>
		<p>
			<label class="edd-label"><?php _e('Billing Address Line 1', 'nochex'); ?></label>		
			<input type="text" name="edd_address" class="edd-address edd-input required" autocomplete='address-line1' value="<?php echo $address["billing_address_1"][0]; ?>" placeholder="<?php _e('Address', 'nochex'); ?>"/>
		</p>
		<p>
			<label class="edd-label"><?php _e('Billing City', 'nochex'); ?></label>		
			<input type="text" name="edd_city" class="edd-city edd-input required" autocomplete='address-level2' value="<?php echo $address["billing_city"][0]; ?>" placeholder="<?php _e('City', 'nochex'); ?>"/>
		</p>
		<p>
			<label class="edd-label"><?php _e('Billing Postcode', 'nochex'); ?></label>		
			<input type="text" name="edd_zip" class="edd-zip edd-input required" autocomplete='postal-code' value="<?php echo $address["billing_postcode"][0]; ?>" placeholder="<?php _e('Zip/Postcode', 'nochex'); ?>"/>
		</p>
		<p>
			<label class="edd-label"><?php _e('Phone Number', 'nochex'); ?></label>
			<input type="text" name="edd_phone" class="edd-phone edd-input required" value="<?php echo $address["billing_phone"][0]; ?>" placeholder="<?php _e('Phone', 'nochex'); ?>"/>
		</p>		
		<img src="<?php echo plugins_url('images/clear-amex-mp.png', __FILE__ ); ?>" style="max-width:300px;" />
	</fieldset>		
	<?php
	echo ob_get_clean();
}

add_action( 'edd_nochex_cc_form', 'edd_nochex_remove_cc_form' );
function validateInput($formInput, $type){	
	if($type == "email"){		
	$result = sanitize_email($formInput);		
	$result = is_email($formInput);	
	}else if($type == "text"){		
	$result = sanitize_text_field($formInput);	
	}else if($type == "url"){		
	$result = esc_url($formInput);	
	}else{ 		
	$result = "Input and Type missing!";	
	}	
	return $result;
}

// processes the payment
function nochex_process_payment($purchase_data) {
    global $edd_options;
    
    // check there is a gateway name
    if ( ! isset( $purchase_data['post_data']['edd-gateway'] ) )
    return;
    // collect payment data	 	
	
	 /* User Data */	
	 $email_address = validateInput($purchase_data['post_data']['edd_email'], "email");			
	 $billing_first_name = validateInput($purchase_data['post_data']['edd_first'], "text");	
	 $billing_last_name = validateInput($purchase_data['post_data']['edd_last'], "text");	 	
	 $billing_address_line = validateInput($purchase_data['post_data']['edd_address'], "text");	 	
	 $billing_city = validateInput($purchase_data['post_data']['edd_city'], "text");	 	 	
	 $billing_postcode = validateInput($purchase_data['post_data']['edd_zip'], "text");	
	 $phone_number = validateInput($purchase_data['post_data']['edd_phone'], "text");		
	 /* URLs */	
	 $returnurl = validateInput(add_query_arg( 'payment-confirmation', 'nochex', get_permalink( $edd_options['success_page'] ) ), "url");		
	 $cancel_page_id = get_option( 'edd_cancel_page' );	
	 $cancelurl = validateInput(get_permalink( $cancel_page_id ), "url");
	 $callback_url = validateInput(trailingslashit(home_url()).'?nochex=apc', "url");	

    if (!$billing_first_name)
		edd_set_error( 'invalid_edd_first', __('First Name is not entered.', 'nochex') );
    if (!$billing_last_name)
		edd_set_error( 'invalid_edd_last', __('Last Name is not entered.', 'nochex') );
    if (!$billing_address_line)
		edd_set_error( 'invalid_edd_address', __('Address is not entered.', 'nochex') );
    if (!$billing_city)
		edd_set_error( 'invalid_edd_city', __('City is not entered.', 'nochex') );
    if (!$billing_postcode)
		edd_set_error( 'invalid_edd_zip', __('PostCode is not entered.', 'nochex') );
    if (!$phone_number)
		edd_set_error( 'invalid_edd_phone', __('Phone Number is not entered.', 'nochex') );					$errors = edd_get_errors();
	
	if ( $errors ) {
        // problems? send back
		edd_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['edd-gateway']);
    }else{		
 	
	$purchase_data['user_info'] = array(

										'id' => $purchase_data["user_info"]["id"],
										
										'first_name' => $billing_first_name,
										
										'last_name' => $billing_last_name,
										
										'email' => $email_address,
										
										'address' => array(

										'line1' => $billing_address_line,

										'city' => $billing_city,

										'zip' => $billing_postcode,

										)

										);				
   
 $payment_data = array( 
        'price'         => $purchase_data['price'], 
        'date'          => $purchase_data['date'], 
        'user_email'    => $purchase_data['user_email'], 
        'purchase_key'  => $purchase_data['purchase_key'], 
        'downloads'     => $purchase_data['downloads'], 
        'user_info'     => $purchase_data['user_info'], 
        'cart_details'  => $purchase_data['cart_details'], 
        'status'        => 'pending'
     );		
	    // record the pending payment
    	$payment = edd_insert_payment( $payment_data );		
	    // check payment
	    if ( !$payment ) {
	        // problems? send back
			edd_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['edd-gateway']);
	    } else {
		
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
			
			/* Theme_Header */
			get_header();	
			
			echo "<style> 				
			@keyframes spinner {
			  to {transform: rotate(360deg);}
			}
			.spinner {
			  margin:auto;
			  content: '';
			  box-sizing: border-box;
			  width: 30px;
			  height: 30px;
			  border-radius: 50%;
			  border-right: 4px solid #08c;
			  border-left: 4px solid #08c;
			  animation: spinner 2s linear infinite;
			}
			.btn-primary{
				background-color:#08c!important;
				color:#fff!important;
				border:1px solid #08c;
				border-radius:10%;
				font-size:13px;	
				text-transform:capitalize;
			}
			</style>";					
		_e('<div id="ncxForm" style="z-index: 10;top: 250px;text-align:center;min-height:500px;height: 500px;vertical-align: middle;position: inherit;">
			<div class="spinner"></div>
			<p>If you are not transferred shortly, press the button below;</p>
			<form action="https://secure.nochex.com/default.aspx" method="post" id="nochex_payment_form">			
			<input type="hidden" name="merchant_id" value="'.$edd_options['nochex_email'].'" />				
			<input type="hidden" name="amount" value="'.number_format($purchase_data['price'], 2, '.', '' ).'" />	 		
			<input type="hidden" name="xml_item_collection" value="'.$xmlCollection.'" />				
			<input type="hidden" name="description" value="'. sprintf(__('Order #%s' , 'nochex'), $payment) . " Items Ordered " . $description .'" />				
			<input type="hidden" name="order_id" value="'.$payment.'" />													
			<input type="hidden" name="optional_2" value="'.$callback.'" />							
			<input type="hidden" name="billing_fullname" value="'.$billing_first_name .' '. $billing_last_name.'" />				
			<input type="hidden" name="billing_address" value="'.$billing_address_line.'" />				
			<input type="hidden" name="billing_city" value="'.$billing_city.'" />				
			<input type="hidden" name="billing_postcode" value="'.$billing_postcode.'" />			
			<input type="hidden" name="email_address" value="'.$email_address.'" />				
			<input type="hidden" name="customer_phone_number" value="'.$phone_number.'" />				
			<input type="hidden" name="success_url" value="'.$returnurl.'" />				
			<input type="hidden" name="hide_billing_details" value="'.$nochex_hide.'" />				
			<input type="hidden" name="callback_url" value="'.$callback_url.'" />				
			<input type="hidden" name="cancel_url" value="'.$cancelurl.'" />				
			<input type="hidden" name="test_success_url" value="'.$returnurl.'" />				
			<input type="hidden" name="test_transaction" value="'.$nochexTest.'" />				
			<input type="submit" class="btn btn-primary" id="submit_nochex_payment_form" value="'.__('Pay via Nochex', 'nochex').'" /> 				
			</form><script type="text/javascript">
				window.onload = function(){
						document.getElementById("nochex_payment_form").submit();
				}
			</script></div>', 'nochex');
			
			/* Theme Footer */
			get_footer();
	    }
	}
}

add_action('edd_gateway_nochex', 'nochex_process_payment');
function nochex_apc() {

if(isset($_REQUEST['nochex'])){

	$checkAPC = esc_html($_REQUEST['nochex']);
	$checkAPC = sanitize_text_field($checkAPC);

	if ($checkAPC == 'apc') {
		
	$checkOrderId = esc_html($_POST['order_id']);
	$checkOrderId = sanitize_text_field($checkOrderId);
	
	$checkTranId = esc_html($_POST['transaction_id']);
	$checkTranId = sanitize_text_field($checkTranId);
	 
	$isCallback = esc_html(isset($_POST['optional_2']));
	$isCallback = sanitize_text_field($isCallback);
	
	if ( $isCallback == '1' ) {

		$urlencoded = "";
		foreach ($_POST as $Index => $Value)
		$urlencoded .= urlencode($Index) . "=" . urlencode($Value) . "&";
		$urlencoded = substr($urlencoded,0,-1);
		$response = wp_remote_post('https://secure.nochex.com/callback/callback.aspx', 
							 array(
								 'method' => 'POST',
								 'timeout' => 45,
								 'redirection' => 5,
								 'httpversion' => '1.0',
								 'blocking' => true,
								 'headers' => array(
													'content-type' => 'application/x-www-form-urlencoded',
													'host' => 'secure.nochex.com',
													'content-length' => strlen($urlencoded)),
								 'body' => $urlencoded,
								 'cookies' => array()
							     )
								 );	
								
		$checkStatus = esc_html($_POST['transaction_status']);
		$checkStatus = sanitize_text_field($isCallback);
		if ($checkStatus == "100"){
			$status = " TEST";
		}else{
			$status = " LIVE";
		}
	    if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) { 
		    $payment_meta = get_post_meta($checkOrderId, '_edd_payment_meta', true );				
		    if (strtolower($response['body']) == strtolower('AUTHORISED')) {  			    
				edd_update_payment_status($checkOrderId, 'publish');
				$orderNotes = "Payment has been " . strtolower($response['body']) . " for order " . $checkOrderId . ", and this was a " . $status . " transaction, the transaction id for this order is " . $checkTranId;
				edd_insert_payment_note($checkOrderId, __( $orderNotes, 'easy-digital-downloads' ) );     
			}else{
				edd_update_payment_status($checkOrderId, 'publish');			
				$orderNotes = "Payment has been " . strtolower($response['body']) . " for order " . $checkOrderId . ", and this was a " . $status . " transaction, the transaction id for this order is " . $checkTranId;			
				edd_insert_payment_note($checkOrderId, __( $orderNotes, 'easy-digital-downloads' ) );     
			}		
		}	
		
	}else{
		$urlencoded = "";
		foreach ($_POST as $Index => $Value)
		$urlencoded .= urlencode($Index) . "=" . urlencode($Value) . "&";
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
								
		$checkStatus = esc_html($_POST['status']);
		$checkStatus = sanitize_text_field($isCallback);
	    if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) { 		
		$payment_meta = get_post_meta($checkOrderId, '_edd_payment_meta', true );	
		    if (strtolower($response['body']) == strtolower('AUTHORISED')) {  			    
				edd_update_payment_status($checkOrderId, 'publish');				
				$orderNotes = "Payment has been " . strtolower($response['body']) . " for order " . $checkOrderId . ", and this was a " . $checkStatus . " transaction, the transaction id for this order is " . $checkTranId;		
				edd_insert_payment_note($checkOrderId, __( $orderNotes, 'easy-digital-downloads' ) );     
			}else{			
				edd_update_payment_status($checkOrderId, 'publish');				
				$orderNotes = "Payment has been " . strtolower($response['body']) . " for order " . $checkOrderId . ", and this was a " . $checkStatus . " transaction, the transaction id for this order is " . $checkTranId;
				edd_insert_payment_note($checkOrderId, __( $orderNotes, 'easy-digital-downloads' ) );     
			}
			}
		}
	}	
	}
}
add_action( 'init', 'nochex_apc' );

// registers the gateway and display on Payment Gateways Default Home
function nochex_register_gateway2($gateways) {
    global $edd_options;
	$gateways['nochex'] = array('admin_label' => 'Nochex', 'checkout_label' => "Credit / Debit Card (Nochex)");
	return $gateways;
}
add_filter('edd_payment_gateways', 'nochex_register_gateway2');
// Creates a Tab and assigned all the Nochex Settings / Variables
function nochex_register_gateway($gateway_sections) {
	$gateway_sections['nochex'] = __( 'Nochex', 'easy-digital-downloads' );	 
	return $gateway_sections;
}
add_filter('edd_settings_sections_gateways', 'nochex_register_gateway');

function nochex_add_settings($gateway_settings) {
	
		$nochex_settings = array (
			'$nochex_settings' => array(
				'id'   => '$nochex_settings',
				'name' => '<strong>' . __( 'Nochex Settings', 'easy-digital-downloads' ) . '</strong>',
				'type' => 'header',
			),
			'nochex_email' => array(
				'id'   => 'nochex_email',
				'name' => __('Merchant ID or Email Address', 'nochex'),
				'desc' => __('Please enter your Nochex Email Address or Merchant ID, for example: test123 or test123@example.com.', 'nochex'),
				'type' => 'text',
				'size' => 'regular',
			), 
		);
	$nochex_settings_api = array(		
		array(
			'id' => 'nochex_display_name',
			'name' => __('Payment Display Name', 'nochex'),
			'desc' => __('Please enter your Nochex Display Name; this is needed in order to take payment.', 'nochex'),
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
	$nochex_settings = array_merge($nochex_settings, $nochex_settings_api);	
	$nochex_settings = apply_filters( 'edd_nochex_settings', $nochex_settings );		
	$gateway_settings['nochex'] = $nochex_settings;

	return $gateway_settings;
}

add_filter('edd_settings_gateways', 'nochex_add_settings',1 ,1);
