<?php
/*
Login using Google credentials
*/

// if you want people to be unable to disconnect their WP and Google accounts, set this to false in wp-config
if (!defined('SGC_ALLOW_DISCONNECT'))
	define('SGC_ALLOW_DISCONNECT',true);

// fix the reauth redirect problem
add_action('login_form_login','sgc_login_reauth_disable');
function sgc_login_reauth_disable() {
	$_REQUEST['reauth'] = false;
}

// add the section on the user profile page
add_action('profile_personal_options','sgc_login_profile_page');
function sgc_login_profile_page($profile) {
	$options = get_option('sgc_options');
?>
	<table class="form-table">
		<tr>
			<th><label><?php _e('Google Connect', 'sgc'); ?></label></th>
<?php
	$goog_id = get_user_meta($profile->ID, 'goog_id', true);
	$goog_access_token = get_user_meta($profile->ID, 'goog_access_token', true);
	$goog_refresh_token = get_user_meta($profile->ID, 'goog_refresh_token', true);
	
	$ui = false;
	if ($goog_access_token && $goog_refresh_token) {
		$ui = sgc_get_userinfo($goog_access_token);
		if ( $ui == false ) {
			$newtoken = sgc_get_token($goog_refresh_token, true);
			if ($newtoken) {
				$ui = sgc_get_userinfo($newtoken['access_token']);
				update_usermeta($profile->ID, 'goog_access_token', $newtoken['access_token']);	
			}
		}
	}
	
	if ($ui == false) {
		?>
			<td><p>
<a class="google-login-link" href="javascript:sgc_poptastic('<?php echo sgc_oauth_request_link('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email', 'popup_profile_connect', true ); ?>');">
<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/goog-login-button.png" />
</a>
			</p></td>
		</tr>
	</table>
	<?php
	} else { ?>
		<td><p>
		<?php 
		_e('Connected as ', 'sgc');
		echo "<a href='https://plus.google.com/".esc_attr($ui['id'])."'>";
		echo " <img width='32' height='32' src='{$ui['picture']}?sz=32' /> ";
		echo esc_html( $ui['name'] );
		echo "</a>";
		?>
<?php if (SGC_ALLOW_DISCONNECT) { ?>
	<a class="button-primary" href='<?php echo wp_nonce_url(get_edit_profile_url($profile->ID).'?sgc_login_disconnect=1', 'sgc_login_disconnect'); ?>';" ><?php _e('Disconnect this account from WordPress', 'sgc'); ?></a>
<?php } ?>
		</p></td>
	<?php } ?>
	</tr>
	</table>
	<?php
}

add_action('sgc_state_popup_profile_connect','sgc_login_connect_user');
function sgc_login_connect_user($oauth) {
	if ( !empty( $oauth['token']['access_token'] ) ) {

		$ui = sgc_get_userinfo($oauth['token']['access_token'] );
		$user = wp_get_current_user();

		if ( !empty($ui['id']) && !empty($user->ID) ) {
			update_usermeta($user->ID, 'goog_id', $ui['id']);
			update_usermeta($user->ID, 'goog_picture', $ui['picture']);
			update_usermeta($user->ID, 'goog_access_token', $oauth['token']['access_token']);
			update_usermeta($user->ID, 'goog_refresh_token', $oauth['token']['refresh_token']);
		}
	}?>
<html><head></head><body>
<script type="text/javascript">
window.opener.location.reload(true);
window.close();
</script>
</body></html>
<?php
exit;
}

add_action('init','sgc_login_check_disconnect');

function sgc_login_check_disconnect() {
	if ( $_GET['sgc_login_disconnect'] == 1 ) {
		check_admin_referer( 'sgc_login_disconnect' );
		sgc_login_disconnect_user();
		wp_safe_redirect(get_edit_profile_url($profile->ID));
		exit;
	}
}

function sgc_login_disconnect_user() {
	$user = wp_get_current_user();
	if (!$user) return;
	delete_usermeta($user->ID, 'goog_id');
	delete_usermeta($user->ID, 'goog_picture');
	delete_usermeta($user->ID, 'goog_access_token');
	delete_usermeta($user->ID, 'goog_refresh_token');
}


add_action('login_footer','sgc_poptastic');

add_action('login_form','sgc_login_add_login_button');
function sgc_login_add_login_button() {
	global $action;
	if ($action == 'login') {
	?>
<p><a class="google-login-link" href="javascript:sgc_poptastic('<?php echo sgc_oauth_request_link('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email', 'popup_login', false ); ?>');">
<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/goog-login-button.png" />
</a></p>
<?php
	}
}

add_action('sgc_state_popup_login','sgc_login_user_popup_handler');
function sgc_login_user_popup_handler($oauth) {
	global $wpdb;
	if ( !empty( $oauth['token']['access_token'] ) ) {
		$ui = sgc_get_userinfo($oauth['token']['access_token'] );
		
		if (!empty($ui['id'])) {
		
			$user_id = $wpdb->get_var( $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'goog_id' AND meta_value = %s", $ui['id']) );

			if (!$user_id) {
				// check by email address
				if (!empty($ui['email'])) {
					$user_id = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_email = %s", $ui['email']) );

					// connect the user so we don't have to do this email search again
					if ($user_id) {
						update_usermeta($user_id, 'goog_id', $ui['id']);
						update_usermeta($user_id, 'goog_picture', $ui['picture']);
						update_usermeta($user_id, 'goog_access_token', $oauth['token']['access_token']);
						update_usermeta($user_id, 'goog_refresh_token', $oauth['token']['refresh_token']);
					}
				}
			}
			if ($user_id) {
				// set the auth cookie, redirect the user to the admin screen
				wp_set_auth_cookie($user_id, true);
				$redirect = admin_url();
			}
		}
	}?>
<html><head></head><body>
<script type="text/javascript">
<?php 
if (!empty($redirect)) echo "window.opener.location.href = ".json_encode($redirect).";";
else echo "window.opener.location.reload(true);";
?>
window.close();
</script>
</body></html>
<?php
exit;
}

// add the Google Profile menu item to the admin bar (3.3+) 
add_action( 'add_admin_bar_menus', 'sgc_add_admin_bar' );
function sgc_add_admin_bar() {
	add_action( 'admin_bar_menu', 'sgc_admin_bar_my_account_menu', 11 );
}
function sgc_admin_bar_my_account_menu( $wp_admin_bar ) {
	$user = wp_get_current_user();
	$gid = get_user_meta($user->ID, 'goog_id', true);

	if ($gid) {
		$wp_admin_bar->add_menu( array(
			'parent' => 'my-account',
			'id'     => 'google-profile',
			'title'  => __( 'Google+ Profile' ),
			'href' => "https://plus.google.com/{$gid}",
			'meta'   => array(
				'class' => 'user-info-item',
			),
		) );
	}
}

add_action('admin_init', 'sgc_login_admin_init');
function sgc_login_admin_init() {
	add_settings_section('sgc_login', __('Login Settings', 'sgc'), 'sgc_login_section_callback', 'sgc');
	add_settings_field('sgc_login_avatars', __('Google Avatars', 'sgc'), 'sgc_login_avatar_callback', 'sgc', 'sgc_login');
}

function sgc_login_section_callback() {
	echo "<p>".__('Settings for the SGC-Login plugin. Users can connect their individual WP Logins to Google accounts on the Users->Your Profile screen.', 'sgc')."</p>";
}

function sgc_login_avatar_callback() {
	$options = get_option('sgc_options');
	if (!isset($options['login_avatars'])) $options['login_avatars'] = false;
	?>
	<p><input type="checkbox" name="sgc_options[login_avatars]" value="1" <?php checked('1', $options['login_avatars']); ?> /><label> <?php _e('Use Google Avatars in preference to Gravatars','sgc'); ?></label></p>
<?php
}

add_filter('sgc_validate_options','sgc_login_validate_options');
function sgc_login_validate_options($input) {
	if (isset($input['login_avatars']) && $input['login_avatars'] != 1) $input['login_avatars'] = 0;
	return $input;
}

// generate google avatar code for users who login with Google
add_filter('get_avatar','sgc_login_avatar', 10, 5);
function sgc_login_avatar($avatar, $id_or_email, $size = '96', $default = '', $alt = false) {
	$options = get_option('sgc_options');
	
	if ( !isset($options['login_avatars']) || $options['login_avatars'] != 1 ) return $avatar;
	
	// handle comments by registered users
	if ( is_object($id_or_email) && isset($id_or_email->user_id) && $id_or_email->user_id != 0) {
		$id_or_email = $id_or_email->user_id;	
	}

	// check to be sure this is for a user id
	if ( !is_numeric($id_or_email) ) return $avatar;

	$image = get_user_meta( $id_or_email, 'goog_picture', true );
	if ($image) {
		// return the avatar code
		return "<img width='{$size}' height='{$size}' style='width:{$size}px;height:($size}px;' class='avatar avatar-{$size} google-avatar' src='{$image}?sz={$size}' />";
	}
	return $avatar;
}

add_action('sgc_help','sgc_login_help');
function sgc_login_help($screen) {
	$screen->add_help_tab( array(
		'id'      => 'sgc-login',
		'title'   => __('Login', 'sgc'),
		'content' => __("<h3>Login using Google Credentials!</h3>
			<p>The Login module will let your users use their Google account credentials to log into their WordPress accounts.</p>
			<p>On activation, the module adds a new button to the Users->Your Profile screen, allowing you to connect your existing WordPress account
			to Google. However, the plugin also matches accounts up by their email address, so this step is not necessary if you have the same email
			address on your WordPress and Google accounts.</p>
			<p>The plugin also adds a 'Sign in with Google' button to the normal wp-login.php screen. This button can be used to log into the account 
			using Google credentials.</p>
			<p>One extra option is provided on the SGC Settings screen, allowing you to choose whether or not to use Google Avatars in preference to 
			Gravatars.</p>"
			,'sgc'),
	));
}