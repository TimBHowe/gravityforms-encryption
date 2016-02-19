<?php
/*
Plugin Name: Gravity Forms Encryption
Plugin URI:
Description: Adds an option to the text fields to allow the values to be encypted in the database
Version: 1.0
Author: Hall Internet Marketing
Author URI: http://hallme.com
License: GPL2
*/


function encryptData($value){
   if(!$value){return false;}
   $key = GF_ENCRYPT;
   $text = $value;
   $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
   $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
   $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv);
   return trim(base64_encode($crypttext)); //encode for cookie
}

function decryptData($value){
   if(!$value){return false;}
   $key = GF_ENCRYPT;
   $crypttext = base64_decode($value); //decode cookie
   $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
   $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
   $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $crypttext, MCRYPT_MODE_ECB, $iv);
   return trim($decrypttext);
}

add_action("gform_field_advanced_settings", "my_advanced_settings", 10, 2);
function my_advanced_settings($position, $form_id){

    //create settings on position 50 (right after Admin Label)
    if($position == 50){
        ?>
        <li class="encrypt_setting field_setting">
            <label for="field_admin_label">
                <?php _e("Encryption", "gravityforms"); ?>
                <?php gform_tooltip("form_field_encrypt_value") ?>
            </label>
            <input type="checkbox" id="field_encrypt_value" onclick="SetFieldProperty('encryptField', this.checked);" /> Encrypt This Field's Value
        </li>
        <?php
    }
}

//Action to inject supporting script to the form editor page
add_action("gform_editor_js", "editor_script");
function editor_script(){
?>
    <script type='text/javascript'>
        //adding setting to fields of type "text"
        fieldSettings["text"] += ", .encrypt_setting";

        //binding to the load field settings event to initialize the checkbox
        jQuery(document).bind("gform_load_field_settings", function(event, field, form){
            jQuery("#field_encrypt_value").attr("checked", field["encryptField"] == true);
        });
    </script>
 <?php
}

//Filter to add a new tooltip
add_filter('gform_tooltips', 'add_encryption_tooltips');
function add_encryption_tooltips($tooltips){
   $tooltips["form_field_encrypt_value"] = "<h6>Encryption</h6>Check this box to encrypt this field's data";
   return $tooltips;
}

// Encrypt and save the field
add_filter("gform_save_field_value", "save_field_value", 10, 4);
function save_field_value($value, $lead, $field, $form){
	if( $field["encryptField"] ){
		return encryptData( $value );
	}else{
		return $value;
	}
}

// Decrypt and show the field
add_filter("gform_get_input_value", "get_field_value", 10, 4);
function get_field_value( $value, $entry, $field, $input_id ){

	// Check if the field is encrypted
	if( $field["encryptField"] ) {
		return decryptData( $value );
	} else {
		return $value;
	}
}


// Decrypt and show the field
add_filter("gform_merge_tag_filter", "gfe_gform_merge_tag_filter", 10, 5);
function gfe_gform_merge_tag_filter( $field_value, $merge_tag, $options, $field, $raw_field_value ){

	// Check if the field is encrypted
	global $gfe_sending_notification;
	if( $field["encryptField"] && $gfe_sending_notification) {

		return 'Encrypted Field - Please log into the site to view this data';

	} else {
		return $field_value;
	}
}

// Checks when the notification process starts
add_filter( 'gform_notification', 'notification_start', 10, 3 );
function notification_start( $notification, $form, $entry ) {
    global $gfe_sending_notification;

    $gfe_sending_notification = true;

    return $notification;
}

// Checks when the notification process ends
add_filter( 'gform_enable_shortcode_notification_message', 'notification_end', 10, 3 );
function notification_end( $bool, $form, $lead ) {
    global $gfe_sending_notification;

    $gfe_sending_notification = false;

    return $bool;
}