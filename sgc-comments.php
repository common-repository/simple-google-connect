<?php
/*
Module to allow google auth for comments
*/


// this exists so that other plugins (SFC and STC) can hook into the same place to add their login buttons
if (!function_exists('alt_login_method_div')) {
	add_action('alt_comment_login','alt_login_method_div',5,0);
	add_action('comment_form_before_fields', 'alt_login_method_div',5,0); // WP 3.0 support
	function alt_login_method_div() { echo '<div id="alt-login-methods">'; }
	add_action('alt_comment_login','alt_login_method_div_close',20,0);
	add_action('comment_form_before_fields', 'alt_login_method_div_close',20,0); // WP 3.0 support
	function alt_login_method_div_close() { echo '</div>'; }
}

// WP 3.0 support
if (!function_exists('comment_user_details_begin')) {
	add_action('comment_form_before_fields', 'comment_user_details_begin',1,0);
	function comment_user_details_begin() { echo '<div id="comment-user-details">'; }
	add_action('comment_form_after_fields', 'comment_user_details_end',20,0);
	function comment_user_details_end() { echo '</div>'; }
}

add_action('alt_comment_login','sgc_comm_add_button');
add_action('comment_form_before_fields', 'sgc_comm_add_button',10,0); // WP 3.0 support
function sgc_comm_add_button() {
?>
<a class="google-comments-link" href="javascript:sgc_poptastic('<?php echo sgc_oauth_request_link('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email', 'popup_comments' ); ?>');">
<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/goog-login-button.png" />
</a>
<?php
}

add_action('sgc_state_popup_comments','sgc_comm_return_creds');
function sgc_comm_return_creds($oauth) {
?>
<html>
<head>
<?php
	wp_enqueue_script('jquery');
	wp_print_scripts();
?>
</head>
<body>
<script type="text/javascript">
<?php if ( !empty( $oauth['token']['access_token'] ) ) {
	$ui = sgc_get_userinfo($oauth['token']['access_token'] );

	if (!empty($ui['picture'])) $image = "<span class='avatar google-avatar'><img src='{$ui['picture']}?sz=50' width='50' height='50' /></span>";
	else $image = "<span class='avatar google-avatar'></span>";

	if (!empty($ui['name'])) $name = $ui['name'];

	$logininfo = __("<p class='google-auth-comment'>{$image} Hi {$name}! You are now connected with your Google Account. Your Google user information will be used for the comment's information.</p>",'sgc');
?>
jQuery('#commentform', window.opener.document).append('<input type="hidden" name="google_authtoken" value="<?php echo $oauth['token']['access_token']; ?>" />');
jQuery('#comment-user-details', window.opener.document).hide().after("<?php echo $logininfo; ?>");
<?php } ?>
window.close();
</script>
</body>
</html>
<?php
exit;
}


// Add user fields for Google based commenters
add_filter('pre_comment_on_post','sgc_comm_fill_in_fields');
function sgc_comm_fill_in_fields($comment_post_ID) {
	if (is_user_logged_in()) return; // do nothing to WP users

	if ( empty( $_POST['google_authtoken'] ) ) return;
	
	$token = $_POST['google_authtoken'];
	
	global $goog_userinfo;
	if (empty($goog_userinfo)) $goog_userinfo = sgc_get_userinfo($token);

	if (!$goog_userinfo) return;

	$goog_userinfo = apply_filters('sgc_comm_user_data', $goog_userinfo);
	$_POST['author'] = $goog_userinfo['name'];
	$_POST['url'] = $goog_userinfo['link'];
	$_POST['email'] = $goog_userinfo['email'];
}



// store the user ID and picture as comment meta data
add_action('comment_post','sgc_comm_add_meta', 10, 1);
function sgc_comm_add_meta($comment_id) {

	global $goog_userinfo;
	if (empty($goog_userinfo)) return;

	update_comment_meta($comment_id, 'googleid', $goog_userinfo['id']);
	update_comment_meta($comment_id, 'googlepicture', $goog_userinfo['picture']);
}


// generate avatar code for Google user comments
add_filter('get_avatar','sgc_comm_avatar', 10, 5);
function sgc_comm_avatar($avatar, $id_or_email, $size = '96', $default = '', $alt = false) {
	// check to be sure this is for a comment
	if ( !is_object($id_or_email) || !isset($id_or_email->comment_ID) || $id_or_email->user_id) 
		 return $avatar;
		 
	// check for google picture comment meta
	$gp = get_comment_meta($id_or_email->comment_ID, 'googlepicture', true);
	if ($gp) {
		// return the avatar code
		$avatar = "<img class='avatar avatar-{$size} google-avatar' src='{$gp}?sz={$size}' width='{$size}' height='{$size}' style='width:($size}px;height:($size)px;' />";
	}

	return $avatar;
}

add_action('sgc_help','sgc_comm_help');
function sgc_comm_help($screen) {
	$screen->add_help_tab( array(
		'id'      => 'sgc-comments',
		'title'   => __('Comments', 'sgc'),
		'content' => __("<h3>Comment using Google Credentials!</h3>
			<p>The Comments module will let your users use Google credentials to make comments. 
			This basically eliminates the need for users to type in their Names and Email addresses.</p>
			<p>For newer themes that use the <code>comment_form()</code> function in WordPress, this is completely automatic. For older themes, 
			you may need to edit your theme's comments form to contain the necessary hooks to make the module work. Please see the next section
			for more information on this.</p>
			<p>Note that some themes do checking for 'required' elements via Javascript. Because the Google comments module sets these fields 
			on the back end, the theme may need to be modified to both display the button or to eliminate the javascript checks.</p>"
			,'sgc'),
	));
	
	$screen->add_help_tab( array(
		'id'      => 'sgc-comments-mods',
		'title'   => __('Modifying the Comments Form', 'sgc'),
		'content' => __("<h3>Modifying the Comments Form</h3>
			<p>Note: If you have a theme using the new <code>comment_form()</code> method, then this step is not necessary. The button will automatically 
			appear near your comments form. Just use CSS to style it and move it around on the form.</p> 
			<p>Also note that these same instructions apply to all of the 'Simple-X-Connect' plugin series, and only need to be done once. All 
			plugins can use this same code. So if you already modified the theme for one of the other plugins, you do not have to do it again.</p>
			<p>To modify your comments form, first you have to find it. In most themes, it will be in the comments.php file.</p>
			<p>Next, you need to identify the three input fields for author, email, and url.</p>
			<p>You're going to wrap these three inputs together inside a single DIV. The beginning of this DIV will have this code:</p>
			<pre>
			&lt;div id='comment-user-details'&gt;
			&lt;?php do_action('alt_comment_login'); ?&gt;
			</pre>
			<p>The end of it is simpler:</p>
			<pre>
			&lt;/div&gt;
			</pre>
			<p>Those will go before and after the three inputs, respectively.</p>
			<p>Once this div is in place, the SGC comment button will show up where that <code>do_action('alt_comment_login');</code> 
			code is called. The div itself will be replaced by the welcome message when a user signs in using the Google button.</p>
			"
			,'sgc'),
	));
}