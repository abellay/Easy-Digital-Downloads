<?php

function edd_process_purchase_form() {
	if(isset($_POST['edd-action']) && $_POST['edd-action'] == 'purchase' && wp_verify_nonce($_POST['edd-nonce'], 'edd-purchase-nonce')) {
				
		$user_id = isset($_POST['edd-user-id']) ? $_POST['edd-user-id'] : 0;
		$user_email = $_POST['edd-email'];
				
		if(isset($_POST['edd-discount']) && $_POST['edd-discount'] != '' && !edd_is_discount_valid($_POST['edd-discount'])) {
			// check for valid discount
			edd_set_error('invalid_discount', __('The discount you entered is invalid', 'edd'));
		}
		
		if(isset($_POST['edd-purchase-var']) && $_POST['edd-purchase-var'] == 'needs-to-register') {
			
			// check the new user's credentials against existing ones
			
			$user_login		= $_POST["edd_user_login"];	
			$user_email		= $_POST["edd_user_email"];
			$user_pass		= $_POST["edd_user_pass"];
			$user_first 	= $_POST["edd_user_first"];
			$user_last	 	= $_POST["edd_user_last"];
			$pass_confirm 	= $_POST["edd_user_pass_confirm"];
			$need_new_user	= true;
			
			if(username_exists($user_login)) {
				// Username already registered
				edd_set_error('username_unavailable', __('Username already taken', 'edd'));
			}
			if(!validate_username($user_login)) {
				// invalid username
				edd_set_error('username_invalid', __('Invalid username', 'edd'));
			}
			if($user_login == '') {
				// empty username
				edd_set_error('username_empty', __('Enter a username', 'edd'));
			}
			if(!is_email($user_email)) {
				//invalid email
				edd_set_error('email_invalid', __('Invalid email', 'edd'));
			}
			if(email_exists($user_email)) {
				//Email address already registered
				edd_set_error('email_used', __('Email already used', 'edd'));
			}
			if($user_pass == '') {
				// passwords do not match
				edd_set_error('password_empty', __('Enter a password', 'edd'));
			}
			if($user_pass != $pass_confirm) {
				// passwords do not match
				edd_set_error('password_mismatch', __('Passwords don\'t match', 'edd'));
			}	

		} elseif(isset($_POST['edd-purchase-var']) && $_POST['edd-purchase-var'] == 'needs-to-login') {
		
			// log the user in
			$user_data = get_user_by('login', $_POST['edd-username']);
			if($user_data) {
				if(wp_check_password($_POST['edd-password'], $user_data->user_pass, $user_data->ID)) {
					$user_pass = $_POST['edd-password'];
					edd_log_user_in($user_data->ID, $_POST['edd-username'], $user_pass);
					// set the buyer's name
					$_POST['edd-first'] = $user_data->first_name;
					$_POST['edd-last'] = $user_data->last_name;
				} else {
					edd_set_error('password_incorrect', __('The password you entered is incorrect', 'edd'));
				}
			} else {
				edd_set_error('username_incorrect', __('The username you entered does not exist', 'edd'));
			}
			
		} elseif(isset($_POST['edd-purchase-var'])) {
			edd_set_error('login_register_error', __('Something has gone wrong, please try again', 'edd'));
		}
		
		do_action('edd_checkout_error_checks', $_POST);
		
		$errors = edd_get_errors();
		if(!$errors) {
			
			if($need_new_user) {
				// create the new user if needed
				$user_id = wp_insert_user(array(
						'user_login'		=> $user_login,
						'user_pass'	 		=> $user_pass,
						'user_email'		=> $user_email,
						'first_name'		=> $user_first,
						'last_name'			=> $user_last,
						'user_registered'	=> date('Y-m-d H:i:s'),
						'role'				=> 'subscriber'
					)
				);
				edd_log_user_in($user_id, $user_login, $user_pass);
			}	
			
			$user_info = array(
				'id' => $user_id,
				'email' => $user_email,
				'first_name' => strip_tags($_POST['edd-first']),
				'last_name' => strip_tags($_POST['edd-last']),
				'discount' => isset($_POST['edd-discount']) && edd_is_discount_valid($_POST['edd-discount']) ? $_POST['edd-discount'] : 'none'
			);	
				
			// setup purchase information
			$purchase_data = array(
				'downloads' => edd_get_cart_contents(),
				'price' => edd_get_cart_amount(),
				'purchase_key' => strtolower(md5(uniqid())), // random key
				'user_email' => $user_email,
				'date' => date('Y-m-d H:i:s'),
				'user_info' => $user_info,
				'post_data' => $_POST,
				'cart_details' => edd_get_cart_content_details()
			);
						
			// allow the purchase data to be modified before it is sent to the gateway
			$purchase_data = apply_filters('edd_purchase_data_before_gateway', $purchase_data);
			
			// send info to gateway for payment processing
			edd_send_to_gateway($_POST['edd-gateway'], $purchase_data);		
		}
		// errors are present
		edd_send_back_to_checkout('?payment-mode=' . $_POST['edd-gateway']);
	}
}
add_action('init', 'edd_process_purchase_form');

function edd_send_to_success_page($query_string = null) {
	global $edd_options;
	$redirect = get_permalink($edd_options['success_page']);
	if($query_string)
		$redirect .= $query_string;
	wp_redirect($redirect); exit;
}

// used to redirect a user back to the purchase page if there are errors present
function edd_send_back_to_checkout($query_string = null) {
	global $edd_options;
	$redirect = get_permalink($edd_options['purchase_page']);
	if($query_string)
		$redirect .= $query_string;
	wp_redirect($redirect); exit;
}

function edd_get_success_page_url($query_string = null) {
	global $edd_options;
	$success_page = get_permalink($edd_options['success_page']);
	if($query_string)
		$success_page .= $query_string;
	return $success_page;
}