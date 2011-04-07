<?php
/*
Plugin Name: WP Opt-in
Plugin URI: http://neppe.no/wordpress/wp-opt-in/
Description: Collect e-mail addresses from users, and send them an e-mail automagically. Information can be selectively deleted or exported in an e-mail Bcc friendly format.
Version: 1.4.1
Author: Petter
Author URI: http://neppe.no/
*/

/*  Copyright 2008-2011 Petter (http://neppe.no/)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA


Issues and feature request list:
- Add new admin-page for writing e-mail to all opt'ed in users 
- Replace mail() with wp_mail() for better support of other mail plugins 
- Add option for file attachment in the e-mail 
- Support sending of HTML e-mail 
- Name field in the opt-in form, and possibility to enable/disable it 
- Support for custom defined opt-in form

Implemented stuff:
- Make it possible to place the php file in either a wp-opt-in folder or the plugins folder 
- Option to send notification e-mail to e.g. admin on subscription
- Actually use the Form footer field
- Functionality to seamlessly upgrade the database options through versions
- Removed unnecessary code in form
- Fixed support for empty options

Won't fix with rationale:
- Add export e-mail addresses to file possibility -> copy from bcc field and paste into file should be simple


Option to enable/disable stuff in a form:
<input type="checkbox" name="abc" id="abc" checked="checked"/>
<input type="checkbox" name="abc" id="abc"/>
if ( isset($post['abc'])) {
	$chk = ' checked="checked"';
} else {
	$chk = '';
}

Location for more php mail details:
http://www.sitepoint.com/print/advanced-email-php
*/

$wpoi_db_version = "0.2";

function wpoi_show_form()
{
	echo '<form action="" method="post">' . "\n";
	echo '<p>' . wpoi_get_option('wpoi_form_email');
	echo ' <input type="text" name="wpoi_email" id="wpoi_email" /></p>' . "\n";
	echo '<p><input type="submit" value="' . wpoi_get_option('wpoi_form_send');
	echo '" /></p>' . "\n</form>\n";
}

function wpoi_getip()
{
	if (isset($_SERVER)) {
		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$ip_addr = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
			$ip_addr = $_SERVER["HTTP_CLIENT_IP"];
		} else {
			$ip_addr = $_SERVER["REMOTE_ADDR"];
		}
	} else {
		if ( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
			$ip_addr = getenv( 'HTTP_X_FORWARDED_FOR' );
		} elseif ( getenv( 'HTTP_CLIENT_IP' ) ) {
			$ip_addr = getenv( 'HTTP_CLIENT_IP' );
		} else {
			$ip_addr = getenv( 'REMOTE_ADDR' );
		}
	}
	return $ip_addr;
}

$wpoi_submit_status = '';

function wpoi_init_action()
{
	if(!empty($_POST['wpoi_email'])) {
		// Linux and Windows compatibility
		if (!defined('PHP_EOL')) {
			if ( strtoupper(substr(PHP_OS,0,3) == 'WIN' ) ) {
				$lf = "\r\n";
			} else {
				$lf = "\n";
			}
		} else {
			$lf = PHP_EOL;
		}

		global $wpdb;
		global $wpoi_submit_status;
		$table_users = $wpdb->prefix . "wpoi_users";

		$email = stripslashes(trim($_POST['wpoi_email']));
		$email_from = stripslashes(wpoi_get_option('wpoi_email_from'));
		$subject = stripslashes(wpoi_get_option('wpoi_email_subject'));
		$message = stripslashes(wpoi_get_option('wpoi_email_message'));
		$message = wordwrap($message, 70);
		$email_notify = stripslashes(wpoi_get_option('wpoi_email_notify'));
		$headers = "From: ".$email_from.$lf;
		$headers .= "X-Mailer: WP Opt-in".$lf;
		$headers .= "MIME-Version: 1.0".$lf;
		$headers .= "Content-Type: text/plain; charset=\"" . get_settings('blog_charset') . "\"" . $lf;
//		$headers .= "Content-Type: text/html; charset=\"" . get_settings('blog_charset') . "\"" . $lf;
		$headers .= "Message-ID: <".md5(uniqid(rand(),true))."@".$_SERVER['SERVER_NAME'].">".$lf;

		if (!preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/", $email)) {
			$wpoi_submit_status = stripslashes(wpoi_get_option('wpoi_msg_bad'));
		}
		elseif (mail($email, $subject, $message, $headers)) {
			// Delete user if already present
			$delete = "DELETE FROM " . $table_users .
					" WHERE email = '" . $email . "'";
			$result = $wpdb->query($delete);

			// Write new user to database
			$ip = wpoi_getip();
			$insert = "INSERT INTO " . $table_users .
				" (time, ip, email) " . "VALUES ('" . time() .
				"','" . $ip . "','" . $email . "')";
		 	$result = $wpdb->query($insert);
			$wpoi_submit_status = stripslashes(wpoi_get_option('wpoi_msg_sent'));
			if ($email_notify != '') {
				mail($email_notify, "WP Opt-in notification", "E-mail sent to new address".$lf."E-mail: ".$email.$lf."IP: $ip".$lf, "To: ".$email_notify.$lf.$headers);
			}

			$url_redir = stripslashes(wpoi_get_option('wpoi_url_redir'));
			if ($url_redir != '') {
				wp_redirect( $url_redir );
				exit;
			}
		} else {
			$wpoi_submit_status = stripslashes(wpoi_get_option('wpoi_msg_fail'));
		}
	}
}

function wpoi_opt_in()
{
	global $wpoi_submit_status;

	echo stripslashes(wpoi_get_option('wpoi_form_header'));
	wpoi_show_form();
	if ($wpoi_submit_status != '') {
		echo $wpoi_submit_status;
	}
	echo stripslashes(wpoi_get_option('wpoi_form_footer')) . "\n";
}

function wpoi_install()
{
	global $wpdb;
	global $wpoi_db_version;

	$table_users = $wpdb->prefix . "wpoi_users";

	if(($wpdb->get_var("show tables like '$table_users'") != $table_users) ||
       (wpoi_get_option("wpoi_db_version") != $wpoi_db_version)) {
		// Table did not excist or not compatible; create new
		$sql = "CREATE TABLE " . $table_users . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time bigint(11) DEFAULT '0' NOT NULL,
			ip varchar(50) NOT NULL,
			email varchar(50) NOT NULL,
			UNIQUE KEY id (id)
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		// Insert initial data in table
		$insert = "INSERT INTO $table_users (time, ip, email) " .
			"VALUES ('" . time() . "','" . wpoi_getip() .
			"','" . get_option('admin_email') . "')";
		$result = $wpdb->query($insert);
		wpoi_add_update_option("wpoi_db_version", $wpoi_db_version);

		// Initialise options with default values
		wpoi_add_option('wpoi_widget_title');
		wpoi_add_option('wpoi_email_from');
		wpoi_add_option('wpoi_email_subject');
		wpoi_add_option('wpoi_email_message');
		wpoi_add_option('wpoi_email_notify');

		wpoi_add_option('wpoi_msg_bad');
		wpoi_add_option('wpoi_msg_fail');
		wpoi_add_option('wpoi_msg_sent');

		wpoi_add_option('wpoi_form_header');
		wpoi_add_option('wpoi_form_footer');
		wpoi_add_option('wpoi_form_email');
		wpoi_add_option('wpoi_form_send');

		wpoi_add_option('wpoi_url_redir');
	}
}

function wpoi_add_option($option_name)
{
//	if ( !get_option($option_name) ) {
		add_option($option_name, wpoi_get_option($option_name));
//	}
}

function wpoi_add_update_option($option_name, $option_value)
{
/*	if ( get_option($option_name) ) {
		update_option($option_name, $option_value);
	} else {
		add_option($option_name, $option_value);
	}*/
	add_option($option_name, $option_value);
	update_option($option_name, $option_value);
}

function wpoi_get_option($option_name)
{
	$blogname = get_option('blogname');

	if ($option_name=='wpoi_widget_title') { return get_option($option_name,'WP Opt-in'); }
	if ($option_name=='wpoi_email_from') { return get_option('admin_email'); }
	if ($option_name=='wpoi_email_subject') { return get_option($option_name,"[$blogname] Requested e-mail"); }
	if ($option_name=='wpoi_email_message') { return get_option($option_name,"This is an automatically sent e-mail.\nYou received this because $blogname received a request."); }
	if ($option_name=='wpoi_email_notify') { return get_option($option_name,''); }

	if ($option_name=='wpoi_msg_bad') { return get_option($option_name,"<p><b>Bad e-mail address.</b></p>"); }
	if ($option_name=='wpoi_msg_fail') { return get_option($option_name,"<p><b>Failed sending to e-mail address.</b></p>"); }
	if ($option_name=='wpoi_msg_sent') { return get_option($option_name,"<p><b>Sent requested e-mail.</b></p>"); }

	if ($option_name=='wpoi_form_header') { return get_option($option_name,"<div id=\"wpoi_form\">Receive information automagically here."); }
	if ($option_name=='wpoi_form_footer') { return get_option($option_name,"</div>"); }
	if ($option_name=='wpoi_form_email') { return get_option($option_name,"E-mail:"); }
	if ($option_name=='wpoi_form_send') { return get_option($option_name,"Submit"); }
	if ($option_name=='wpoi_url_redir') { return get_option($option_name,""); }
	if ($option_name=='wpoi_db_version') { return get_option($option_name,"0.2"); }
}

function wpoi_options()
{
	global $wpdb;
	$table_users = $wpdb->prefix . "wpoi_users";

	// Handle options from get method information
	if (isset($_GET['user_id'])) {
		$user_id = $_GET['user_id'];

		// Delete user from database
		$delete = "DELETE FROM " . $table_users .
				" WHERE id = '" . $user_id . "'";
		$result = $wpdb->query($delete);

		// Notify admin of delete
		echo '<div id="message" class="updated fade"><p><strong>';
		_e('User deleted.', 'wpoi_domain');
		echo '</strong></p></div>';
	}

	// Get current options from database
	$email_from = stripslashes(wpoi_get_option('wpoi_email_from'));
	$email_subject = stripslashes(wpoi_get_option('wpoi_email_subject'));
	$email_message = stripslashes(wpoi_get_option('wpoi_email_message'));
	$email_notify = stripslashes(wpoi_get_option('wpoi_email_notify'));

	$msg_bad = stripslashes(wpoi_get_option('wpoi_msg_bad'));
	$msg_fail = stripslashes(wpoi_get_option('wpoi_msg_fail'));
	$msg_sent = stripslashes(wpoi_get_option('wpoi_msg_sent'));

	$form_header = stripslashes(wpoi_get_option('wpoi_form_header'));
	$form_footer = stripslashes(wpoi_get_option('wpoi_form_footer'));
	$form_email = stripslashes(wpoi_get_option('wpoi_form_email'));
	$form_send = stripslashes(wpoi_get_option('wpoi_form_send'));

	$url_redir = stripslashes(wpoi_get_option('wpoi_url_redir'));

	// Update options if user posted new information
	if( $_POST['wpoi_hidden'] == 'SAb13c' ) {
		// Read from form
		$email_from = stripslashes($_POST['wpoi_email_from']);
		$email_subject = stripslashes($_POST['wpoi_email_subject']);
		$email_message = stripslashes($_POST['wpoi_email_message']);
		$email_notify = stripslashes($_POST['wpoi_email_notify']);

		$msg_bad = stripslashes($_POST['wpoi_msg_bad']);
		$msg_fail = stripslashes($_POST['wpoi_msg_fail']);
		$msg_sent = stripslashes($_POST['wpoi_msg_sent']);

		$form_header = stripslashes($_POST['wpoi_form_header']);
		$form_footer = stripslashes($_POST['wpoi_form_footer']);
		$form_email = stripslashes($_POST['wpoi_form_email']);
		$form_send = stripslashes($_POST['wpoi_form_send']);

		$url_redir = stripslashes($_POST['wpoi_url_redir']);

		// Save to database
		wpoi_add_update_option('wpoi_email_from', $email_from );
		wpoi_add_update_option('wpoi_email_subject', $email_subject);
		wpoi_add_update_option('wpoi_email_message', $email_message);
		wpoi_add_update_option('wpoi_email_notify', $email_notify );

		wpoi_add_update_option('wpoi_msg_bad', $msg_bad);
		wpoi_add_update_option('wpoi_msg_fail', $msg_fail);
		wpoi_add_update_option('wpoi_msg_sent', $msg_sent);

		wpoi_add_update_option('wpoi_form_header', $form_header);
		wpoi_add_update_option('wpoi_form_footer', $form_footer);
		wpoi_add_update_option('wpoi_form_email', $form_email);
		wpoi_add_update_option('wpoi_form_send', $form_send);

		wpoi_add_update_option('wpoi_url_redir', $url_redir);

		// Notify admin of change
		echo '<div id="message" class="updated fade"><p><strong>';
		_e('Options saved.', 'wpoi_domain');
		echo '</strong></p></div>';
	}
?>
<div class="wrap">
<h2>WP Opt-in Options</h2>
<form name="wpoi_form" method="post" action="">
<input type="hidden" name="wpoi_hidden" value="SAb13c">
<fieldset class="options">
<legend>E-mail to send users on opt-in</legend>
<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
<tr valign="top">
<th width="33%" scope="row">From:</th><td>
<input type="text" name="wpoi_email_from" id="wpoi_email_from" value="<?php echo $email_from; ?>" size="40"></td><td>Only <i>user@domain.tld</i> format is allowed.</td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Subject:</th><td colspan="2">
<input type="text" name="wpoi_email_subject" id="wpoi_email_subject" value="<?php echo $email_subject; ?>" size="40"></td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Message:</th><td colspan="2">
<textarea name="wpoi_email_message" id="wpoi_email_message" rows="4" cols="40"><?php echo $email_message; ?></textarea></td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Notify:</th><td>
<input type="text" name="wpoi_email_notify" id="wpoi_email_notify" value="<?php echo $email_notify; ?>" size="40"></td><td>Also notify to this email when someone opt-in. Leave blank for no notification.</td>
</tr>
</table>
</fieldset>
<fieldset class="options">
<legend>Front side messages on opt-in</legend>
<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
<tr valign="top">
<th width="33%" scope="row">Bad e-mail:</th><td>
<input type="text" name="wpoi_msg_bad" id="wpoi_msg_bad" value="<?php echo $msg_bad; ?>" size="40"></td>
<td>Error message to show the user on submit of bad e-mail address</td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Failed to send:</th><td>
<input type="text" name="wpoi_msg_fail" id="wpoi_msg_fail" value="<?php echo $msg_fail; ?>" size="40"></td>
<td>Error message to show the user on failure to send opt-in e-mail</td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Success:</th><td>
<input type="text" name="wpoi_msg_sent" id="wpoi_msg_sent" value="<?php echo $msg_sent; ?>" size="40"></td>
<td>Message to show the user on successful opt-in</td>
</tr>
</table>
</fieldset>
<fieldset class="options">
<legend>Front side form appearance</legend>
<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
<tr valign="top">
<th width="33%" scope="row">Form header:</th><td>
<textarea name="wpoi_form_header" id="wpoi_form_header" rows="4" cols="40"><?php echo $form_header; ?></textarea></td>
<td>Optional HTML/CSS code before the Opt-in form</td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Form footer:</th><td>
<textarea name="wpoi_form_footer" id="wpoi_form_footer" rows="2" cols="40"><?php echo $form_footer; ?></textarea></td>
<td>Optional HTML/CSS code after the Opt-in form</td>
</tr>
<tr valign="top">
<th width="33%" scope="row">E-mail field:</th><td>
<input type="text" name="wpoi_form_email" id="wpoi_form_email" value="<?php echo $form_email; ?>" size="40"></td>
<td>Text in front of the e-mail address input field</td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Submit button:</th><td>
<input type="text" name="wpoi_form_send" id="wpoi_form_send" value="<?php echo $form_send; ?>" size="40"></td>
<td>Text on the submit button</td>
</tr>
</table>
</fieldset>
<fieldset class="options">
<legend>Plugin behavior</legend>
<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
<tr valign="top">
<th width="33%" scope="row">URL redirect:</th><td>
<input type="text" name="wpoi_url_redir" id="wpoi_url_redir" value="<?php echo $url_redir; ?>" size="40"></td>
<td>Redirect to this URL on successful submit. Leave blank for no redirect and usage of success message.</td>
</tr>
</table>
</fieldset>
<p class="submit">
<input type="submit" name="Submit" value="Update Options &raquo;" />
</p>
</form>
</div>
<div class="wrap">
<h2>Opted-in users</h2>
<h3>Bcc friendly format</h3>
<p>
<?php
	$users = $wpdb->get_results("SELECT * FROM $table_users ORDER BY id DESC");
	$additional_user=0;
	foreach ($users as $user) {
		if ($additional_user) {
			echo ', ';
		}
		$additional_user=1;
		echo $user->email;
	}
?>
</p>
<h3>All details</h3>
<table class="widefat">
<thead>
<tr>
<th scope="col">ID</th>
<th scope="col">Date</th>
<th scope="col">Time</th>
<th scope="col">IP</th>
<th scope="col">E-mail</th>
<th scope="col">Action</th>
</tr>
</thead>
<tbody>
<?php
	$user_no=0;
	$url = 'options-general.php?page=' . __FILE__ . '&user_id=';
	foreach ($users as $user) {
		if ($user_no&1) {
			echo "<tr class='alternate'>";
		} else {
			echo "<tr>";
		}
		$user_no=$user_no+1;
		echo "<td>$user->id</td>";
		echo "<td>" . date(get_option('date_format'), $user->time) . "</td>";
		echo "<td>" . date(get_option('time_format'), $user->time) . "</td>";
		echo "<td>$user->ip</td>";
		echo "<td>$user->email</td>";
		echo "<td><a href=\"$url$user->id\" onclick='if(confirm(\"Are you sure you want to delete user with ID $user->id?\")) return; else return false;'>Delete</a></td>";
		echo "</tr>";
	}
?>
</tbody>
</table>
</div>
<?php
}

function wpoi_widget_init() {
	global $wp_version;

	if (!function_exists('register_sidebar_widget')) {
		return;
	}

	function wpoi_widget($args) {
		extract($args);
		echo $before_widget . $before_title;
		echo wpoi_get_option('wpoi_widget_title');
		echo $after_title;
		wpoi_opt_in();
		echo $after_widget;
	}

	function wpoi_widget_control() {
		$title = wpoi_get_option('wpoi_widget_title');
		if ($_POST['wpoi_submit']) {
			$title = stripslashes($_POST['wpoi_widget_title']);
			wpoi_add_update_option('wpoi_widget_title', $title );
		}
		echo '<p>Title:<input  style="width: 200px;" type="text" value="';
		echo $title . '" name="wpoi_widget_title" id="wpoi_widget_title" /></p>';
		echo '<input type="hidden" id="wpoi_submit" name="wpoi_submit" value="1" />';
	}

	$width = 300;
	$height = 100;
	if ( ('2.2' == $wp_version) || (!function_exists( 'wp_register_sidebar_widget' ))) {
		register_sidebar_widget('WP Opt-in', 'wpoi_widget');
		register_widget_control('WP Opt-in', 'wpoi_widget_control', $width, $height);
	} else {
		// v2.2.1+
		$size = array('width' => $width, 'height' => $height);
		$class = array( 'classname' => 'wpoi_opt_in' ); // css classname
		wp_register_sidebar_widget('wpoi', 'WP Opt-in', 'wpoi_widget', $class);
		wp_register_widget_control('wpoi', 'WP Opt-in', 'wpoi_widget_control', $size);
	}
}

function wpoi_add_to_menu() {
	add_options_page('WP Opt-in Options', 'WP Opt-in', 7, __FILE__, 'wpoi_options' );
}

register_activation_hook(__FILE__, 'wpoi_install');
add_action('admin_menu', 'wpoi_add_to_menu');
add_action('plugins_loaded', 'wpoi_widget_init');
add_action('init', 'wpoi_init_action');
?>
