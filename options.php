<?php
/*
 * WPGear. Adaptive Login Action
 * options.php
 */
	
	$AdaptiveLoginAction_Action 			= isset($_REQUEST['action']) ? sanitize_text_field ($_REQUEST['action']) : null;
	$AdaptiveLoginAction_Setup_AdminOnly 	= isset($_REQUEST['adaptive-login-action_option_adminonly']) ? 1 : 0;	
	$AdaptiveLoginAction_WhiteListIP 		= isset($_REQUEST['adaptive-login-action_option_whitelist_ip']) ? sanitize_textarea_field ($_REQUEST['adaptive-login-action_option_whitelist_ip']) : null;			
	$AdaptiveLoginAction_WhiteListUpdate 	= isset($_REQUEST['adaptive-login-action_option_whitelist_ip_update']) ? 1 : 0;	
	$AdaptiveLoginAction_SecretKey			= isset($_REQUEST['adaptive-login-action_option_secretkey']) ? sanitize_text_field ($_REQUEST['adaptive-login-action_option_secretkey']) : null;
	
	if ($AdaptiveLoginAction_Action == 'Update') {
        $AdaptiveLoginAction_WhiteListIP_txt 	= '';
        $AdaptiveLoginAction_WhiteListIP_Wrong 	= '';

		if ($AdaptiveLoginAction_WhiteListIP) {
			$AdaptiveLoginAction_WhiteListIP = explode(PHP_EOL, $AdaptiveLoginAction_WhiteListIP);

			foreach ($AdaptiveLoginAction_WhiteListIP as $Item) {
				if (filter_var($Item, FILTER_VALIDATE_IP)) {
					$AdaptiveLoginAction_WhiteListIP_txt .= "$Item,";
				} else {
					if ($Item != '') {
						$AdaptiveLoginAction_WhiteListIP_Wrong .= "$Item\r\n";
					}
				}
			}
		}		
		
		update_option('adaptive-login-action_option_adminonly', $AdaptiveLoginAction_Setup_AdminOnly);
		update_option('adaptive-login-action_option_whitelist_ip', $AdaptiveLoginAction_WhiteListIP_txt);
		update_option('adaptive-login-action_option_whitelist_ip_update', $AdaptiveLoginAction_WhiteListUpdate);
		update_option('adaptive-login-action_option_secretkey', $AdaptiveLoginAction_SecretKey);		
		
		if ($AdaptiveLoginAction_WhiteListIP_Wrong) {
			?>
			<div class="adaptive-login-action_warning" style="margin: 40px;">
				Not a valid IP address:
				<div>
					<?php echo $AdaptiveLoginAction_WhiteListIP_Wrong; ?>
				</div>
			</div>
			<?php			
		}
	}
	
	$AdaptiveLoginAction_Setup_AdminOnly 	= get_option('adaptive-login-action_option_adminonly', 1);
	$AdaptiveLoginAction_WhiteListIP 		= get_option('adaptive-login-action_option_whitelist_ip', null);
	$AdaptiveLoginAction_WhiteListUpdate 	= get_option('adaptive-login-action_option_whitelist_ip_update', 1);
	$AdaptiveLoginAction_SecretKey 			= get_option('adaptive-login-action_option_secretkey', null);
	
	if ($AdaptiveLoginAction_WhiteListIP) {
		$AdaptiveLoginAction_WhiteListIP = str_replace(",", "\r\n", $AdaptiveLoginAction_WhiteListIP);
	}
	
	if ($AdaptiveLoginAction_Setup_AdminOnly) {
		if (!current_user_can('edit_dashboard')) {
			?>
			<div class="adaptive-login-action_warning" style="margin: 40px;">
				Sorry, you are not allowed to view this page.
			</div>
			<?php
			
			return;
		}		
	}	
	
	?>
	<div class="wrap">
		<h2>Adaptive Login Action.</h2>
		<hr>
		
		<div class="adaptive-login-action_options_box">
			<form name="form_AdaptiveLoginAction_Options" method="post" style="margin-top: 20px;">
				<div style="margin-top: 10px;">
					<label for="adaptive-login-action_option_adminonly" title="On/Off">
					Enable this Page for Admin only
					</label>
					<input id="adaptive-login-action_option_adminonly" name="adaptive-login-action_option_adminonly" type="checkbox" <?php if($AdaptiveLoginAction_Setup_AdminOnly) {echo 'checked';} ?>>
				</div>	

				<div style="margin-top: 10px; margin-left: 10px;">
					<label for="adaptive-login-action_option_whitelist_ip" title="one IP per line" style="vertical-align: top;">"White List IP" (one IP per line)</label>
					<textarea id="adaptive-login-action_option_whitelist_ip" name="adaptive-login-action_option_whitelist_ip" rows="4" class="adaptive-login-action_option_whitelist_ip"><?php echo $AdaptiveLoginAction_WhiteListIP; ?></textarea>
				</div>
				
				<div style="margin-top: 10px; margin-left: 28px;">
					<label for="adaptive-login-action_option_whitelist_ip_update" title="On/Off">
					Auto Update "White List IP"
					</label>
					<input id="adaptive-login-action_option_whitelist_ip_update" name="adaptive-login-action_option_whitelist_ip_update" type="checkbox" <?php if($AdaptiveLoginAction_WhiteListUpdate) {echo 'checked';} ?>>
				</div>				
				
				<div style="margin-top: 10px; margin-left: 124px;">
					<label for="adaptive-login-action_option_secretkey" title="On/Off">
					Secret Key
					</label>
					<input id="adaptive-login-action_option_secretkey" name="adaptive-login-action_option_secretkey" type="text" value="<?php echo $AdaptiveLoginAction_SecretKey; ?>">
				</div>				

				<hr>
				<div style="margin-top: 10px; margin-bottom: 5px; text-align: right;">
					<input id="adaptive-login-action_btn_options_save" type="submit" class="button button-primary" style="margin-right: 5px;" value="Save">
				</div>
				<input id="action" name="action" type="hidden" value="Update">
			</form>
		</div>			
	</div>
