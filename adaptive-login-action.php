<?php
/*
Plugin Name: Adaptive Login Action
Plugin URI: wpgear.xyz/adaptive-login-action
Description: Compromise between Comfort and Paranoia.
Version: 1.4
Author: WPGear
Author URI: http://wpgear.xyz
License: GPLv2
*/

	$AdaptiveLoginAction_plugin_url = plugin_dir_url( __FILE__); // со слэшем на конце	

	/* Admin Console - Styles.
	----------------------------------------------------------------- */	
	function AdaptiveLoginAction_admin_style ($hook) {
		$screen = get_current_screen();
		$screen_base = $screen->base;		

		if ($screen_base == 'adaptive-login-action/options') {
			global $AdaptiveLoginAction_plugin_url;			
		
			wp_enqueue_style ('adaptive-login-action_admin-style', $AdaptiveLoginAction_plugin_url .'admin-style.css');
		}
	}
	add_action ('admin_enqueue_scripts', 'AdaptiveLoginAction_admin_style' );

	/* Login Form - Styles.
	----------------------------------------------------------------- */	
	function AdaptiveLoginAction_style ($hook) {	
		if ($GLOBALS['pagenow'] == 'wp-login.php') {
			global $AdaptiveLoginAction_plugin_url;			
		
			wp_enqueue_style ('adaptive-login-action_style', $AdaptiveLoginAction_plugin_url .'style.css');
		}
	}
	add_action ('login_enqueue_scripts', 'AdaptiveLoginAction_style' );	
	
	/* Create plugin SubMenu
	----------------------------------------------------------------- */		
	function AdaptiveLoginAction_create_menu() {
		add_options_page(
			__( 'Adaptive Login Action', 'textdomain' ),
			__( 'Adaptive Login Action', 'textdomain' ),
			'publish_posts',
			'adaptive-login-action/options.php',
			''
		);
	}
	add_action('admin_menu', 'AdaptiveLoginAction_create_menu');	
	
	/* Login Form
	----------------------------------------------------------------- */
	function AdaptiveLoginAction_Login(){
		$UserIP = AdaptiveLoginAction_GetUserIP();
		
		$AdaptiveLoginAction_NormalMode = false;		
		
		if ($UserIP) {
			$AdaptiveLoginAction_IP = get_option('adaptive-login-action_ip_' .$UserIP, array());
			
			$AdaptiveLoginAction_LastOK = isset($AdaptiveLoginAction_IP['last_ok']) ? $AdaptiveLoginAction_IP['last_ok'] : 0;		
			
			$AdaptiveLoginAction_WhiteListIP = get_option('adaptive-login-action_option_whitelist_ip', null);				
			
			if ($AdaptiveLoginAction_WhiteListIP && $AdaptiveLoginAction_LastOK == 1) {
				$AdaptiveLoginAction_WhiteListIP = explode(",", $AdaptiveLoginAction_WhiteListIP);				
				
				foreach ($AdaptiveLoginAction_WhiteListIP as $Item) {														
					if ($UserIP == $Item) {						
						$AdaptiveLoginAction_NormalMode = true;
					} 
				}
			}
		}

		if ($AdaptiveLoginAction_NormalMode) {
			// Normal Form.
			if ($UserIP) {
				?>
				<div class="adaptive-login-action_normal_field_ip">
					IP: <span><?php echo $UserIP; ?></span>
				</div>
				<?php			
			}			
		} else {
			// Ext. Security.
			?>
			<p class="adaptive-login-action_field_secretkey">		
				<label for="adaptive-login-action_secretkey">Secret Key</label>
				<input id="adaptive-login-action_secretkey" name="adaptive-login-action_secretkey" type="password" class="input password-input"/>
			</p>
			<?php
			if ($UserIP) {
				?>
				<div class="adaptive-login-action_security_field_ip">
					IP: <span><?php echo $UserIP; ?></span> will be saved to DB.
				</div>
				<?php			
			}			
		}

		return true;
	}		
	add_action('login_form', 'AdaptiveLoginAction_Login');
	
	/* Check Secret Key
	----------------------------------------------------------------- */	
	function AdaptiveLoginAction_authenticate($user) {
		$AdaptiveLoginAction_SecretKey_Input = isset($_REQUEST['adaptive-login-action_secretkey']) ? sanitize_text_field ($_REQUEST['adaptive-login-action_secretkey']) : null;	
		
		if (! is_null($AdaptiveLoginAction_SecretKey_Input)) {
			$AdaptiveLoginAction_SecretKey = get_option('adaptive-login-action_option_secretkey', '');
				
			if ($AdaptiveLoginAction_SecretKey_Input != $AdaptiveLoginAction_SecretKey) {				
				$message = 'Authentication Error';
				return new WP_Error ('secret_string_problem', $message);
			}
		}

		if (AdaptiveLoginAction_Check_Plugin_Installed ('new-users-monitor')) {
			// New Users Monitor. Integration.
			if (is_wp_error($user)) {
				global $NUM_Authentication_Msg;
				
				$message = $user->get_error_message();

				if ($message == $NUM_Authentication_Msg) {
					return new WP_Error ('no_confirm_user', $message);	
				}
			}
		}	

		return $user;	
	}
	add_filter('wp_authenticate_user', 'AdaptiveLoginAction_authenticate', 10);	
	
	/* After Login
	----------------------------------------------------------------- */	
	function AdaptiveLoginAction_LoginOK($User_login, $user) {		
		$UserIP = AdaptiveLoginAction_GetUserIP();
		
		$AdaptiveLoginAction_WhiteListUpdate = get_option('adaptive-login-action_option_whitelist_ip_update', 1);
		
		if ($AdaptiveLoginAction_WhiteListUpdate) {
			// Auto Update "White List IP"			
			if ($UserIP) {
				$AdaptiveLoginAction_WhiteListIP = get_option('adaptive-login-action_option_whitelist_ip', null);						
				
				if ($AdaptiveLoginAction_WhiteListIP) {
					if (!preg_match("/$UserIP/", $AdaptiveLoginAction_WhiteListIP)) {
						$AdaptiveLoginAction_WhiteListIP .= "$UserIP,";
						update_option('adaptive-login-action_option_whitelist_ip', $AdaptiveLoginAction_WhiteListIP);
					}
				} else {
					$AdaptiveLoginAction_WhiteListIP = "$UserIP,";
					update_option('adaptive-login-action_option_whitelist_ip', $AdaptiveLoginAction_WhiteListIP);				
				}
			}			
		}
		
		if ($UserIP) {
			// Remember the Success of the Login from this IP
			$Success = true;
			
			AdaptiveLoginAction_Update_LoginIP ($UserIP, $Success);
		}		
	}
	add_action('wp_login', 'AdaptiveLoginAction_LoginOK', 10, 2);
	
	/* Login Failed
	----------------------------------------------------------------- */	
	function AdaptiveLoginAction_LoginFailed ($username){
		$UserIP = AdaptiveLoginAction_GetUserIP();
		
		if (AdaptiveLoginAction_Check_Plugin_Installed ('new-users-monitor')) {
			// New Users Monitor. Integration.
			$is_User_Confirmed = false;

			$user = get_user_by ('login', $username);

			if ($user) {
				$User_ID = $user -> ID;
				
				$is_User_Confirmed = get_user_meta ($User_ID, 'num_confirm', true);	
			}
		} else {
			$is_User_Confirmed = true;
		}
		
		if ($is_User_Confirmed) {
			if ($UserIP) {
				// Remember the Failed of the Login from this IP
				$Success = false;
				
				AdaptiveLoginAction_Update_LoginIP ($UserIP, $Success);
			}
		}
	}
	add_action('wp_login_failed', 'AdaptiveLoginAction_LoginFailed');
	
	/* Login Errors
	----------------------------------------------------------------- */
	function AdaptiveLoginAction_LoginErrors ($message) {
		if (AdaptiveLoginAction_Check_Plugin_Installed ('new-users-monitor')) {
			// New Users Monitor. Integration.
			global $NUM_Authentication_Msg;
			
			$message = sanitize_text_field ($message);

			if ($message != $NUM_Authentication_Msg) {
				$message = 'Authentication Error';
			}
		}
		return $message;
	}	
	add_filter('login_errors', 'AdaptiveLoginAction_LoginErrors');	

	/* Update IP-Stat
	----------------------------------------------------------------- */
	function AdaptiveLoginAction_Update_LoginIP ($UserIP, $Success = true) {
		$AdaptiveLoginAction_IP = get_option('adaptive-login-action_ip_' .$UserIP, array());
		
		$AdaptiveLoginAction_IP_LoginTotal 		= isset($AdaptiveLoginAction_IP['total']) ? $AdaptiveLoginAction_IP['total'] : 0;
		$AdaptiveLoginAction_IP_LoginSuccess 	= isset($AdaptiveLoginAction_IP['success']) ? $AdaptiveLoginAction_IP['success'] : 0;
					
		$AdaptiveLoginAction_IP_LoginTotal += 1;
		
		if ($Success == true) {
			$AdaptiveLoginAction_LastOK = 1;	
			$AdaptiveLoginAction_IP_LoginSuccess += 1;
		} else {
			$AdaptiveLoginAction_LastOK = 0;
		}
		
		$AdaptiveLoginAction_IP = array (
			'last_ok' => $AdaptiveLoginAction_LastOK,
			'total' => $AdaptiveLoginAction_IP_LoginTotal,
			'success' => $AdaptiveLoginAction_IP_LoginSuccess,
		);
			
		update_option('adaptive-login-action_ip_' .$UserIP, $AdaptiveLoginAction_IP);
		update_option('adaptive-login-action_last_ok', $AdaptiveLoginAction_LastOK);
	}

	/* Get User IP
	----------------------------------------------------------------- */	
	function AdaptiveLoginAction_GetUserIP () {
		$IP = null;
		
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] )) {
			$IP = $_SERVER['HTTP_CLIENT_IP'];
		} else if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] )) {
			$IP = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$IP = $_SERVER['REMOTE_ADDR'];
		}
		
		return $IP;
	}
	
	/* Check Plugin Installed
	----------------------------------------------------------------- */		
	function AdaptiveLoginAction_Check_Plugin_Installed ($Plugin_Slug = null) {
		$Result = false;
		
		if ($Plugin_Slug) {
			if (! function_exists ('get_plugins')) {
				require_once ABSPATH .'wp-admin/includes/plugin.php';
			}
			
			$Plugins = get_plugins();
			
			foreach ($Plugins as $Plugin) {
				$Plugin_TextDomain = $Plugin['TextDomain'];
				if ($Plugin_TextDomain == $Plugin_Slug) {
					$Result = true;
				}
			}			
		}	
		
		return $Result;
	}	