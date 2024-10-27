<?php
/*
 * WPGear. Adaptive Login Action
 * uninstall.php
 */	

	if( !defined( 'ABSPATH' ) && !defined( 'WP_UNINSTALL_PLUGIN' ) )
		exit();
	 
	global $wpdb;
	
	$AdaptiveLoginAction_options_table = $wpdb->prefix .'options';
	
	// Delete Plugin Options
	$Query = "DELETE FROM $AdaptiveLoginAction_options_table WHERE option_name LIKE 'adaptive-login-action_%'";		
	$wpdb->query($Query);