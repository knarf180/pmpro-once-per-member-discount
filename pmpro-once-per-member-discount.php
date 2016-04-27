<?php
/*
Plugin Name: Paid Memberships Pro - Once per Member Discount
Plugin URI: https://github.com/knarf180/pmpro-once-per-member-discount
Description: A plugin for Paid Membership Pro which adds the ability to restrict a discount code to be used only once per member account.
Version: .1
Author: Frank Reilly
Author URI: http://www.knarfworks.com

Note: This plugin requires version 1.4.6 of Paid Memberships Pro or higher. 
*/

//show the once per memeber discount setting on the discount code page
function pmproopm_pmpro_discount_code_after_settings() {
	if(!empty($_REQUEST['edit'])) { $edit = $_REQUEST['edit']; }
    //Only show the checkbox if we have an id number.  If not we wont have anything to save to
    //There must be a better way of doing this..
    if ($edit > 0) {
        $pmproopm_settings = get_option("pmproopm_settings");
            
        if(!empty($pmproopm_settings[$edit]['once_per_member']))
            $pmproopm_once_per_member = $pmproopm_settings[$edit]['once_per_member'];
        else
            $pmproopm_once_per_member = 0;    
?>
<table class="form-table">
	<tbody>
		<tr>
			<th scope="row" valign="top">Once per member:</th>
			<td>
            <label><input type="checkbox" name="pmproopm_once_per_member" value="1" <?php if ($pmproopm_once_per_member <> 0) echo ' checked'; ?>> May only be used once per member account</label>
			</td>
		</tr>
							
	</tbody>
</table>

<?php
    }
    
}
add_action("pmpro_discount_code_after_settings", "pmproopm_pmpro_discount_code_after_settings");

//save the discount code setting
function pmproopm_pmpro_save_discount_code($code_id) {	
    //same issue as above.  A code id below 0 is a new item which does not yet have a real id number
	if(!empty($code_id) && $code_id > 0)
	{
		//load settings
		$pmproopm_settings = get_option("pmproopm_settings");
		
		//set it
		$pmproopm_settings[$code_id] = array("once_per_member" => intval($_REQUEST['pmproopm_once_per_member']));
		//save it
		update_option("pmproopm_settings", $pmproopm_settings);
	}
}
add_action("pmpro_save_discount_code", "pmproopm_pmpro_save_discount_code",10,1);


function pmproopm_pmpro_check_discount_code($okay, $dbcode, $level_id, $code) {
    // if we arent logged in don't bother checking anything
    if(!is_user_logged_in())
        return $okay;
    
	//build array of one time use codes
    global $wpdb, $current_user;
    $pmproopm_settings = get_option("pmproopm_settings");
            
    $qcodes = $wpdb->get_results("SELECT id, code FROM $wpdb->pmpro_discount_codes");
    foreach($qcodes as $qcode) 
        $codeList[$qcode->id] = $qcode->code;
    
    foreach($pmproopm_settings as $key => $option) {
        if ($pmproopm_settings[$key]['once_per_member'] == 1) {
            if (!empty($codeList[$key])) {
                $one_time_use_codes[] = strtoupper($codeList[$key]);
            }
        }
    }
	
	//check if the code being used is a one time code
	if(in_array(strtoupper($code), $one_time_use_codes)) {
		//see if user has used this code already
		$used_codes = $current_user->pmpro_used_codes;	//stored in user meta
		if(is_array($used_codes) && in_array(strtoupper($code), $used_codes))
			return "You have already used the discount code provided.";
	}
	
	return $okay;
}
add_filter('pmpro_check_discount_code', 'pmproopm_pmpro_check_discount_code', 10, 4);

//remember which codes have been used after checkout
function pmproopm_pmpro_after_checkout($user_id) {
	global $discount_code;
	
	if(!empty($discount_code)) {
		$used_codes = get_user_meta($user_id, 'pmpro_used_codes', true);
		if(empty($used_codes))
			$used_codes = array();
			
		$used_codes[] = strtoupper($discount_code);
		
		update_user_meta($user_id, "pmpro_used_codes", $used_codes);
	}
}
add_action('pmpro_after_checkout', 'pmproopm_pmpro_after_checkout');