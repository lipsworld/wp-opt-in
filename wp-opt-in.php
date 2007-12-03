<?php
/*
Plugin Name: WP Opt-in
Plugin URI: http://neppe.no/wordpress/wp-opt-in/
Description: Collect e-mail addresses from users, and send them an e-mail automagically. Information can be selectively deleted or exported in an e-mail Bcc friendly format.
Version: 0.6
Author: Petter
Author URI: http://neppe.no/
*/

/*  Copyright 2007 Petter (http://neppe.no/)

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
*/

$wpoi_db_version = "0.1";
$wpoi_db_users = $wpdb->prefix . "wpoi_users";

function wpoi_show_form()
{
//	echo '<form action="' . get_permalink() . '" method="post">' . "\n";
	echo '<form action="" method="post">' . "\n";
	echo '<p>' . get_option('wpoi_form_email');
	echo ' <input type="text" name="wpoi_email" id="wpoi_email" /></p>' . "\n";
	echo '<p><input type="submit" value="' . get_option('wpoi_form_send');
	echo '" /></p>' . "\n</form>\n<!-- Made by WP Opt-in -->\n";
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

function wpoi_opt_in()
{
	global $wpdb;
	global $wpoi_db_users;

	echo stripslashes(get_option('wpoi_form_header'));

	$_POST['wpoi_email'] = trim($_POST['wpoi_email']);
	if(empty($_POST['wpoi_email'])) {
		wpoi_show_form();
	} else {
		$email = stripslashes($_POST['wpoi_email']);
		$email_from = stripslashes(get_option('wpoi_email_from'));
		$subject = stripslashes(get_option('wpoi_email_subject'));
		$message = stripslashes(get_option('wpoi_email_message'));
		$headers = "MIME-Version: 1.0\n";
		$headers .= "From: $email_from\n";
		$headers .= "Content-Type: text/plain; charset=\"" . get_settings('blog_charset') . "\"\n";

		if (!preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/", $email)) {
			echo stripslashes(get_option('wpoi_msg_bad'));
			wpoi_show_form();
		}
		elseif (mail($email,$subject,$message,$headers)) {
			// Write new user to database
			$insert = "INSERT INTO " . $wpoi_db_users .
				" (time, ip, email) " . "VALUES ('" . time() .
				"','" . wpoi_getip() . "','" . $email . "')";
		 	$result = $wpdb->query($insert);
			echo stripslashes(get_option('wpoi_msg_sent'));
		} else {
			echo stripslashes(get_option('wpoi_msg_fail'));
		}
	}

	echo '</div>' . "\n";
}

function wpoi_install()
{
	global $wpdb;
	global $wpoi_db_version;
	global $wpoi_db_users;

	if($wpdb->get_var("SHOW TABLES LIKE '$wpoi_db_users'") != $wpoi_db_users) {
		// Table did not excist; create new
		$sql = "CREATE TABLE " . $wpoi_db_users . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time bigint(11) DEFAULT '0' NOT NULL,
			ip varchar(50) NOT NULL,
			email varchar(50) NOT NULL,
			UNIQUE KEY id (id)
		);";
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);

		// Insert initial data in table
		$insert = "INSERT INTO $wpoi_db_users (time, ip, email) " .
			"VALUES ('" . time() . "','" . wpoi_getip() .
			"','" . get_option('admin_email') . "')";
		$result = $wpdb->query($insert);
		add_option("wpoi_db_version", $wpoi_db_version);

		// Initialise options with default values
		$blogname = get_option('blogname');
		add_option('wpoi_widget_title', 'WP Opt-in');
		add_option('wpoi_email_from', get_option('admin_email') );
		add_option('wpoi_email_subject', "[$blogname] Requested e-mail");
		add_option('wpoi_email_message', "This is an automatically sent e-mail.\nYou received this because $blogname received a request.");

		add_option('wpoi_msg_bad', "<p><b>Bad e-mail address.</b></p>");
		add_option('wpoi_msg_fail', "<p><b>Failed sending to e-mail address.</b></p>");
		add_option('wpoi_msg_sent', "<p><b>Sent requested e-mail.</b></p>");

		add_option('wpoi_form_header', "<div class=\"widget module\">Receive information automagically here.");
		add_option('wpoi_form_footer', "</div>");
		add_option('wpoi_form_email', "E-mail:");
		add_option('wpoi_form_send', "Submit");
	}
/*
	if (get_option("wpoi_db_version") != $wpoi_db_version) {
		// Create new table structure
		$sql = "CREATE TABLE " . $table . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time bigint(11) DEFAULT '0' NOT NULL,
			ip varchar(50) NOT NULL,
			email varchar(50) NOT NULL,
			UNIQUE KEY id (id)
		);";
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);

		update_option("wpoi_db_version", $wpoi_db_version);
	}
*/
}

function wpoi_options()
{
	global $wpdb;
	global $wpoi_db_users;

	// Handle options from get method information
	if (isset($_GET['user_id'])) {
		$user_id = $_GET['user_id'];

		// Delete user from database
		$delete = "DELETE FROM " . $wpoi_db_users .
				" WHERE id = '" . $user_id . "'";
		$result = $wpdb->query($delete);

		// Notify admin of delete
		echo '<div id="message" class="updated fade"><p><strong>';
		_e('User deleted.', 'wpoi_domain');
		echo '</strong></p></div>';
	}

	// Get current options from database
	$email_from = stripslashes(get_option('wpoi_email_from'));
	$email_subject = stripslashes(get_option('wpoi_email_subject'));
	$email_message = stripslashes(get_option('wpoi_email_message'));

	$msg_bad = stripslashes(get_option('wpoi_msg_bad'));
	$msg_fail = stripslashes(get_option('wpoi_msg_fail'));
	$msg_sent = stripslashes(get_option('wpoi_msg_sent'));

	$form_header = stripslashes(get_option('wpoi_form_header'));
	$form_footer = stripslashes(get_option('wpoi_form_footer'));
	$form_email = stripslashes(get_option('wpoi_form_email'));
	$form_send = stripslashes(get_option('wpoi_form_send'));

	// Update options if user posted new information
	if( $_POST['wpoi_hidden'] == 'SAb13c' ) {
		// Read from form
		$email_from = stripslashes($_POST['wpoi_email_from']);
		$email_subject = stripslashes($_POST['wpoi_email_subject']);
		$email_message = stripslashes($_POST['wpoi_email_message']);

		$msg_bad = stripslashes($_POST['wpoi_msg_bad']);
		$msg_fail = stripslashes($_POST['wpoi_msg_fail']);
		$msg_sent = stripslashes($_POST['wpoi_msg_sent']);

		$form_header = stripslashes($_POST['wpoi_form_header']);
		$form_footer = stripslashes($_POST['wpoi_form_footer']);
		$form_email = stripslashes($_POST['wpoi_form_email']);
		$form_send = stripslashes($_POST['wpoi_form_send']);

		// Save to database
		update_option('wpoi_email_from', $email_from );
		update_option('wpoi_email_subject', $email_subject);
		update_option('wpoi_email_message', $email_message);

		update_option('wpoi_msg_bad', $msg_bad);
		update_option('wpoi_msg_fail', $msg_fail);
		update_option('wpoi_msg_sent', $msg_sent);

		update_option('wpoi_form_header', $form_header);
		update_option('wpoi_form_footer', $form_footer);
		update_option('wpoi_form_email', $form_email);
		update_option('wpoi_form_send', $form_send);

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
<input type="text" name="wpoi_email_from" id="wpoi_email_from" value="<?php echo $email_from; ?>" size="40"></td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Subject:</th><td>
<input type="text" name="wpoi_email_subject" id="wpoi_email_subject" value="<?php echo $email_subject; ?>" size="40"></td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Message:</th><td>
<textarea name="wpoi_email_message" id="wpoi_email_message" rows="4" cols="40"><?php echo $email_message; ?></textarea></td>
</tr>
</table>
</fieldset>
<fieldset class="options">
<legend>Front side messages on opt-in</legend>
<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
<tr valign="top">
<th width="33%" scope="row">Bad e-mail:</th><td>
<input type="text" name="wpoi_msg_bad" id="wpoi_msg_bad" value="<?php echo $msg_bad; ?>" size="40"></td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Failed to send:</th><td>
<input type="text" name="wpoi_msg_fail" id="wpoi_msg_fail" value="<?php echo $msg_fail; ?>" size="40"></td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Success:</th><td>
<input type="text" name="wpoi_msg_sent" id="wpoi_msg_sent" value="<?php echo $msg_sent; ?>" size="40"></td>
</tr>
</table>
</fieldset>
<fieldset class="options">
<legend>Front side form appearance</legend>
<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
<tr valign="top">
<th width="33%" scope="row">Form header:</th><td>
<textarea name="wpoi_form_header" id="wpoi_form_header" rows="4" cols="40"><?php echo $form_header; ?></textarea></td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Form footer:</th><td>
<textarea name="wpoi_form_footer" id="wpoi_form_footer" rows="2" cols="40"><?php echo $form_footer; ?></textarea></td>
</tr>
<tr valign="top">
<th width="33%" scope="row">E-mail field:</th><td>
<input type="text" name="wpoi_form_email" id="wpoi_form_email" value="<?php echo $form_email; ?>" size="40"></td>
</tr>
<tr valign="top">
<th width="33%" scope="row">Submit button:</th><td>
<input type="text" name="wpoi_form_send" id="wpoi_form_send" value="<?php echo $form_send; ?>" size="40"></td>
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
	$users = $wpdb->get_results("SELECT * FROM `$wpoi_db_users` ORDER BY id DESC");
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
	$users = $wpdb->get_results("SELECT * FROM `$wpoi_db_users` ORDER BY id DESC");
	$user_no=0;
	$url = get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=' .
		basename(__FILE__) . '&user_id=';
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
		echo get_option('wpoi_widget_title');
		echo $after_title;
		wpoi_opt_in();
		echo $after_widget;
	}

	function wpoi_widget_control() {
		$title = get_option('wpoi_widget_title');
		if ($_POST['wpoi_submit']) {
			$title = stripslashes($_POST['wpoi_widget_title']);
			update_option('wpoi_widget_title', $title );
		}
		echo '<p>Title:<input  style="width: 200px;" type="text" value="';
		echo $title . '" name="wpoi_widget_title" id="wpoi_widget_title" /></p>';
		echo '<input type="hidden" id="wpoi_submit" name="wpoi_submit" value="1" />';
	}

	$width = 300;
	$height = 100;
	if ( '2.2' == $wp_version || (!function_exists( 'wp_register_sidebar_widget' ))) {
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

register_activation_hook(basename(__FILE__), 'wpoi_install');
add_action('admin_menu', 'wpoi_add_to_menu');
add_action('plugins_loaded', 'wpoi_widget_init');
?>
