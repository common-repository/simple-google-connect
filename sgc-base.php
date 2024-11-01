<?php
/*
Base file for Simple Google Connect. Contains major settings and basic connectivity code.
*/

// Load the textdomain
add_action('init', 'sgc_load_textdomain');
function sgc_load_textdomain() {
	load_plugin_textdomain('sgc', false, dirname(plugin_basename(__FILE__)));
}

global $sgc_plugin_list;
$sgc_plugin_list = array(
	'plugin_login'=>'sgc-login.php',
	'plugin_comments'=>'sgc-comments.php',
	'plugin_plusone'=>'sgc-plusone.php',
	'plugin_plusbadge'=>'sgc-plusbadge.php',
	'plugin_import'=>'sgc-import.php',
	'plugin_publish'=>'sgc-publish.php', // soon... soon...
);

global $sgc_plugin_descriptions;
$sgc_plugin_descriptions = array(
	'plugin_login'		=>__('Login using Google Credentials','sgc'),
	'plugin_comments'	=>__('Allow Google Login to Comment (for non-registered users)','sgc'),
	'plugin_plusone'	=>__('Add Plus One button to posts (manual or automatic)','sgc'),
	'plugin_plusbadge'	=>__('Add an official badge on your site linking to a Google+ Brand Page','sgc'),
	'plugin_import'		=>__('Import posts and comments from Google+','sgc'),
	
);

// load all the subplugins
add_action('plugins_loaded','sgc_plugin_loader');
function sgc_plugin_loader() {
	global $sgc_plugin_list;
	$options = get_option('sgc_options');
	if (!empty($options)) foreach ($options as $key=>$value) {
		if ($value === 'enable' && array_key_exists($key, $sgc_plugin_list)) {
			include_once($sgc_plugin_list[$key]);
		}
	}
}

// add the admin settings and such
add_action('admin_init', 'sgc_admin_init',9); // 9 to force it first, subplugins should use default
function sgc_admin_init(){
	$options = get_option('sgc_options');
	if (empty($options['app_secret']) || empty($options['appid'])) {
		add_action('admin_notices', create_function( '', "echo '<div class=\"error\"><p>".sprintf(__('Simple Google Connect needs configuration information on its <a href="%s">settings</a> page.', 'sgc'), admin_url('options-general.php?page=sgc'))."</p></div>';" ) );
	} else {

	}
	wp_enqueue_script('jquery');
	register_setting( 'sgc_options', 'sgc_options', 'sgc_options_validate' );
	add_settings_section('sgc_main', __('Main Settings', 'sgc'), 'sgc_section_text', 'sgc');
	if (!defined('SGC_APP_ID')) add_settings_field('sgc_appid', __('Google Client ID', 'sgc'), 'sgc_setting_appid', 'sgc', 'sgc_main');
	if (!defined('SGC_APP_SECRET')) add_settings_field('sgc_app_secret', __('Google Client Secret', 'sgc'), 'sgc_setting_app_secret', 'sgc', 'sgc_main');
	add_settings_field('sgc_oauth2callback', __('Google OAuth2 Callback', 'sgc'), 'sgc_setting_oauthcallback', 'sgc', 'sgc_main');

	add_settings_field('sgc_plus_page', __('Google+ Page Number', 'sgc'), 'sgc_plus_page', 'sgc', 'sgc_main');
	
	add_settings_section('sgc_plugins', __('SGC Plugins', 'sgc'), 'sgc_plugins_text', 'sgc');
	add_settings_field('sgc_subplugins', __('Plugins', 'sgc'), 'sgc_subplugins', 'sgc', 'sgc_plugins');

	add_settings_section('sgc_meta', __('Schema.org Metadata', 'sgc'), 'sgc_meta_text', 'sgc');

	if (!function_exists('sfc_base_meta')) {
		add_settings_field('sgc_default_image', __('Default Image', 'sgc'), 'sgc_default_image', 'sgc', 'sgc_meta');
		add_settings_field('sgc_default_description', __('Default Description', 'sgc'), 'sgc_default_description', 'sgc', 'sgc_meta');
	}
	
	add_settings_field('sgc_multi_author', __('Multi-Author Blog?', 'sgc'), 'sgc_multi_author', 'sgc', 'sgc_meta');
	add_settings_field('sgc_single_author', __('Single Author (default)', 'sgc'), 'sgc_single_author', 'sgc', 'sgc_meta');
}

// add the admin options page
add_action('admin_menu', 'sgc_admin_add_page');
function sgc_admin_add_page() {
	global $sgc_options_page;
	$sgc_options_page = add_options_page(__('Simple Google Connect', 'sgc'), __('Simple Google Connect', 'sgc'), 'manage_options', 'sgc', 'sgc_options_page');
	add_action("load-$sgc_options_page", 'sgc_plugin_help');
}

function sgc_plugin_help() {
	global $sgc_options_page;
	
	$screen = get_current_screen();
	
	if ($screen->id != $sgc_options_page) 
		return;
	
	$options = get_option('sgc_options');
	if ( empty( $options['oauthcallback'] ) ) $options['oauthcallback'] = "oauth2callback";

	$home = home_url('/');
	$homecallback = home_url('/'.$options['oauthcallback']);
	
	$sgc_help_base = __("<h3>Connecting to Google</h3>
		<p>To connect your site to Google, you will first need to create a Google Application.
		If you have already created one, please insert your Client ID and Client Secret below.</p>
		<h3>Quick Link</h3>
		<p><a target='_blank' href='https://code.google.com/apis/console/'>Google API Console</a></p>
		
		<h3>Detailed Setup Instructions</h3>
		<ol>
		<li>First, visit the Google API Console: <a target='_blank' href='https://code.google.com/apis/console/'>Google API Console</a>.</li>
		<li>Here, you will need to create a new project. Name the project using the name of your website, to make it easy to find later.</li>
		<li>In the Services section, you will need to turn on the 'Google+ API' to enable the plugin to get information from Google+.</li>
		<li>On the 'API Access' Tab, click the 'Create an OAuth 2.0 client ID' button.</li>
		<li>The 'Product name' will be the name of your website. You can also upload a logo if you wish (you can do this later if you want).</li>
		<li>The 'Application type' should be 'Web Application' and put in the address of your application. <br />
		Also don't forget to select http or https correctly for the address of your website, if you choose incorrectly it will not work.</li>
		<li>Note that you must put in your website's URL <strong>exactly</strong>. If your website doesn't actually have a www in it, then 
		<em>don't put one in there</em>.</li>
		<li>For reference, your website's url is <strong>%s</strong> and this must be <em>exactly</em> what you enter into this screen.</li>
		<li>The Redirect URI will thus be <strong>%s</strong>. If you see anything else, then you've entered your URL wrong.</li>
		<li>Finally, after you click the 'Create ID' button, you will find the Client ID and Client Secret fields. Copy and paste those onto 
		this configuration screen.</li>
		</ol>
		", 'sgc');
		
	$sgc_help_base = sprintf( $sgc_help_base, $home, $homecallback );
		
	$screen->add_help_tab( array(
		'id'      => 'sgc-base',
		'title'   => __('Connecting to Google', 'sgc'),
		'content' => $sgc_help_base,
	));
	
	$screen->set_help_sidebar(
		'<p><strong>' . __( 'Useful links:' ) . '</strong></p>' .
		'<p>' . __( '<a href="http://ottopress.com" target="_blank">Otto on WordPress</a>' ) . '</p>' .
		'<p>' . __( '<a href="http://wordpress.org/support/" target="_blank">WordPress Support Forums</a>' ) . '</p>'
	);
	
	do_action('sgc_help',$screen);

}

// display the admin options page
function sgc_options_page() {
?>
	<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('Simple Google Connect', 'sgc'); ?></h2>
	<p><?php _e('Options relating to the Simple Google Connect plugin.', 'sgc'); ?> </p>
	<form method="post" action="options.php">
	<?php settings_fields('sgc_options'); ?>
	<table><tr><td>
	<?php do_settings_sections('sgc'); ?>
	</td><td style='vertical-align:top;'>
	<div style='width:20em; float:right; background: #ffc; border: 1px solid #333; margin: 2px; padding: 5px'>
			<h3 align='center'><?php _e('About the Author', 'sgc'); ?></h3>
		<p><a href="http://ottopress.com/blog/wordpress-plugins/simple-google-connect/">Simple Google Connect</a> is developed and maintained by <a href="http://ottodestruct.com">Otto</a>.</p>
			<p>He blogs at <a href="http://ottodestruct.com">Nothing To See Here</a> and <a href="http://ottopress.com">Otto on WordPress</a>, chats on <a href="http://twitter.com/otto42">Twitter</a>, and plays around on <a href="https://plus.google.com/100201852715113506716">Google+</a>.</p>
			<p>You can follow his site on either <a href="http://www.facebook.com/ottopress">Facebook</a> or <a href="http://twitter.com/ottodestruct">Twitter</a>, if you like.</p>
			<p>If you'd like to <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=otto%40ottodestruct%2ecom">buy him a beer</a>, then he'd be perfectly happy to drink it.</p>
		</div>
	</td></tr></table>
	<?php submit_button(); ?>
	</form>
	</div>

<?php
}


function sgc_section_text() {
	_e('These main settings are required by all the Simple Google Connect sub-modules.','sgc');
}

function sgc_setting_appid() {
	if (defined('SGC_APP_ID')) return;
	$options = get_option('sgc_options');
	echo "<input type='text' id='sgcappid' name='sgc_options[appid]' value='{$options['appid']}' size='100' /> ";
	_e('(required)', 'sgc');
}

function sgc_setting_app_secret() {
	if (defined('SGC_APP_SECRET')) return;
	$options = get_option('sgc_options');
	echo "<input type='text' id='sgcappsecret' name='sgc_options[app_secret]' value='{$options['app_secret']}' size='40' /> ";
	_e('(required)', 'sgc');
}

function sgc_setting_oauthcallback() {
	$options = get_option('sgc_options');
	if ( empty( $options['oauthcallback'] ) ) $options['oauthcallback'] = "oauth2callback";
	echo home_url('/')." <input type='text' id='sgcoauthcallback' name='sgc_options[oauthcallback]' value='{$options['oauthcallback']}' size='30' /><br />";
	_e("You can use this field to change the callback endpoint. If you don't know what that means, leave it set to 'oauth2callback'. <br /> (Note: Setting this value incorrectly will break the plugin.)",'sgc');
}

function sgc_plus_page() {

	echo '<p>';
	_e('If you have made a Google+ Page to connect to your site, fill in the ID number of the page here.','sgc');
	echo '</p>';
	$options = get_option('sgc_options');
	if ( empty( $options['plus_page'] ) ) $options['plus_page'] = '';
	echo "<label> http://plus.google.com/ <input type='text' id='sgc_plus_page' name='sgc_options[plus_page]' value='{$options['plus_page']}' size='40' />";
	_e('(optional)', 'sgc');
}

function sgc_options_validate($input) {
	// TODO validation
	$output = $input;
	
	if ( empty( $output['oauthcallback'] ) ) $output['oauthcallback'] = "oauth2callback";
	
	// flush the rules to make sure the callback sticks
	flush_rewrite_rules(false);
	
	return $output;
}


function sgc_plugins_text() {
?>
<p><?php _e('SGC is a modular system. Click the checkboxes by the sub-plugins of SGC that you want to use. All of these are optional.', 'sgc'); ?></p>
<?php
}

function sgc_subplugins() {
	global $sgc_plugin_descriptions;
	$options = get_option('sgc_options');
	if ($options['appid']) {
	
	foreach ($sgc_plugin_descriptions as $key=>$val) {
	?>
	<p><label><input type="checkbox" name="sgc_options[<?php echo $key; ?>]" value="enable" <?php @checked('enable', $options[$key]); ?> /> <?php echo $val; ?></label></p>
	<?php
	}
	
	do_action('sgc_subplugins');
	}
}

function sgc_meta_text() {
	if (function_exists('sfc_base_meta')) {
		_e("<p>SGC has detected that you are also using the Simple Facebook Connect plugin. SFC automatically populates your site with 
		<a href='http://ogp.me/'>OpenGraph metadata</a>, which can also be used by Google+. Therefore, SGC will not duplicate this 
		work by adding other metadata to the site as well. Both Facebook and Google+ will thus be consistent in their sharing 
		mechanisms for your site.</p>", 'sgc');
	} else {
		_e("<p>SGC automatically populates your site with <a href='http://schema.org/'>Schema.org</a> meta tags for Google+ and other sites to use for things like sharing and publishing.</p>", 'sgc');
	}
}

function sgc_default_image() {
	$options = get_option('sgc_options');
	?>
	<p><label><?php _e('SGC will automatically choose an image from your content if one is available. When one is not available, you can specify the URL to a default image to use here.','sgc'); ?><br />
	<input type="text" name="sgc_options[default_image]" value="<?php echo esc_url($options['default_image']); ?>" size="80" placeholder="http://example.com/path/to/image.jpg"/></label></p>
	<?php
}

function sgc_default_description() {
	$options = get_option('sgc_options');
	?>
	<p><label><?php _e('SGC will automatically create descriptions for single post pages based on the excerpt of the content. For other pages, you can put in a default description here.','sgc'); ?><br />
	<textarea cols="80" rows="3" name="sgc_options[default_description]"><?php echo esc_textarea($options['default_description']); ?></textarea></label></p>
	<?php
}

function sgc_multi_author() {
	$options = get_option('sgc_options');
	?>
	<p><?php _e("If you have multiple different people writing posts on the site, then please check the box below. This will affect how the plugin handles metadata on the various pages and posts of the site. If only one person writes on this blog, leave the box unchecked.",'sgc'); ?></p>
	<p><label><input type="checkbox" name="sgc_options[multi_author]" value="enable" <?php @checked('enable', $options[multi_author]); ?> /> 
	<?php _e('Enable Multi-Author Support','sgc'); ?></label></p>
	<?php
}

function sgc_single_author() {
	$options = get_option('sgc_options');
	?>
	<p><?php _e("For single author sites, put in the Google ID of the author here. For multi-author sites, put in the 'default' author here (SGC will take Multi-Author info from the Login module, if you're using it).",'sgc'); ?></p>	
	<?php
	echo "<label> http://plus.google.com/ <input type='text' id='sgc_plus_page' name='sgc_options[single_author]' value='".esc_attr($options['single_author'])."' size='40' />";
	_e('(required)', 'sgc');
}

function sgc_oauth_request_link($scope, $action='', $forceprompt=false) {
	$options = get_option('sgc_options');
	
	$args['response_type'] = 'code';
	$args['client_id'] = $options['appid'];
	$args['redirect_uri'] = home_url($options['oauthcallback']);
	$args['scope'] = $scope;
	if ($forceprompt) {
		$args['approval_prompt'] = 'force';
		$args['access_type'] = 'offline';
	} else {
		$args['approval_prompt'] = 'auto';
		$args['access_type'] = 'online';
	}
	
	if ( !empty($action) ) $args['state'] = $action;
	
	$q = http_build_query($args);
	
	$auth = 'https://accounts.google.com/o/oauth2/auth?'.$q;
	
	return $auth;
}

add_action('wp_footer','sgc_poptastic');
add_action('admin_footer','sgc_poptastic');
function sgc_poptastic() {
?>
<script>
function sgc_poptastic(url) {
	var newWindow = window.open(url, 'name', 'height=600,width=450');
	if (window.focus) {
		newWindow.focus();
	}
}
</script>
<?php
}

add_action('init','sgc_add_rewrite');
function sgc_add_rewrite() {
       global $wp;
       $options = get_option('sgc_options');
       $wp->add_query_var($options['oauthcallback']);
       add_rewrite_rule($options['oauthcallback'].'?$', 'index.php?oauth2callback=1', 'top');
}

add_action('template_redirect','sgc_oauth_catcher');
function sgc_oauth_catcher() {
	if ( get_query_var('oauth2callback') == 1 ) {
	
		$oauth = array();
		
		if ( !empty( $_REQUEST['code'] ) ) {
			$oauth['code'] = $_REQUEST['code'];
			$oauth['token'] = sgc_get_token( $oauth['code'] );
		}
		else if ( !empty($_REQUEST['error']) ) {
			$oauth['error'] = $_REQUEST['error'];
		}
		
		if ( !empty( $_REQUEST['state'] ) )
			do_action('sgc_state_'.$_REQUEST['state'], $oauth );
		
		// if we made it here, then the action didn't do anything so redirect to the home page
		wp_redirect(home_url());
	}
}

function sgc_get_token($code, $refresh=false) {
	$options = get_option('sgc_options');
	
	if ($refresh) $req['refresh_token'] = $code;
	else $req['code'] = $code;
	$req['client_id'] = $options['appid'];
	$req['client_secret'] = $options['app_secret'];
	if (!$refresh) $req['redirect_uri'] = home_url($options['oauthcallback']);
	if ($refresh) $req['grant_type'] = 'refresh_token';
	else $req['grant_type'] = 'authorization_code';
	
	$args['sslverify'] = false;
	$args['body'] = $req;
	
	$data = wp_remote_post('https://accounts.google.com/o/oauth2/token', $args);

	if ( is_wp_error( $data ) || 200 != wp_remote_retrieve_response_code( $data ) )
		return false;
	
	$resp = json_decode( wp_remote_retrieve_body( $data ), true );

	return $resp;
}


function sgc_get_userinfo($token) {
	$headers['Authorization'] = 'Bearer '.$token;
	$request['headers'] = $headers;
	$request['sslverify'] = false;
	
	$data = wp_remote_get('https://www.googleapis.com/oauth2/v1/userinfo', $request);

	if ( is_wp_error( $data ) || 200 != wp_remote_retrieve_response_code( $data ) )
		return false;
	
	$resp = json_decode( wp_remote_retrieve_body( $data ), true );

	return $resp;
}

function sgc_pointer_enqueue( $hook_suffix ) {
	global $sgc_options_page;
	if ( $hook_suffix != $sgc_options_page ) return;
	
	$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

	if ( ! in_array( 'sgc-help', $dismissed ) ) {
		$enqueue = true;
		add_action( 'admin_print_footer_scripts', '_sgc_pointer' );
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
	}
}
add_action( 'admin_enqueue_scripts', 'sgc_pointer_enqueue' );

function _sgc_pointer() {
	$pointer_content  = '<h3>' . __('Help is available!', 'sgc') . '</h3>';
	$pointer_content .= '<p>' . __('Make sure to check the Help dropdown box for information on installing and using Simple Google Connect.','sgc') . '</p>';
?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready( function($) {
	$('#contextual-help-link-wrap').pointer({
		content: '<?php echo $pointer_content; ?>',
		position: {
			edge:  'top',
			align: 'right'
		},
		pointerClass: 'sgc-help-pointer',
		close: function() {
			$.post( ajaxurl, {
					pointer: 'sgc-help',
				//	_ajax_nonce: $('#_ajax_nonce').val(),
					action: 'dismiss-wp-pointer'
			});
		}
	}).pointer('open');
	
	$(window).resize(function() {
		if ( $('.sgc-help-pointer').is(":visible") ) $('#contextual-help-link-wrap').pointer('reposition');
	});
	
	$('#contextual-help-link-wrap').click( function () {
		setTimeout( function () {
			$('#contextual-help-link-wrap').pointer('reposition');
		}, 1000);
	});
});
//]]>
</script>
<style>
.sgc-help-pointer .wp-pointer-arrow {
	right:10px;
	left:auto;
}
</style>
<?php
}

add_action('wp_footer','sgc_async_script_loader');
function sgc_async_script_loader() {
	$load = apply_filters('sgc_load_scripts',false);
	if (!$load) return;
?>
<script type="text/javascript">
  (function() {
    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
    po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
  })();
</script>
<?php
}


// code to create a pretty excerpt given a post object
function sgc_base_make_excerpt($post) { 
	
	if (!empty($post->post_excerpt)) $text = $post->post_excerpt;
	else $text = $post->post_content;
	
	$text = strip_shortcodes( $text );

	remove_filter( 'the_content', 'wptexturize' );
	$text = apply_filters('the_content', $text);
	add_filter( 'the_content', 'wptexturize' );

	$text = str_replace(']]>', ']]&gt;', $text);
	$text = wp_strip_all_tags($text);
	$text = str_replace(array("\r\n","\r","\n"),' ',$text);

	$excerpt_more = apply_filters('excerpt_more', '[...]');
	$excerpt_more = html_entity_decode($excerpt_more, ENT_QUOTES, 'UTF-8');
	$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

	$max = min(1000,apply_filters('sgc_excerpt_length',1000));
	$max -= strlen ($excerpt_more) + 1;

	if ($max<1) return ''; // nothing to send
	
	if (strlen($text) >= $max) {
		$text = substr($text, 0, $max);
		$words = explode(' ', $text);
		array_pop ($words);
		array_push ($words, $excerpt_more);
		$text = implode(' ', $words);
	}

	return $text;
}

if (function_exists('sfc_base_meta')) {
	add_action('wp_head','sgc_base_meta');
}

// fix up the html tag to have the schema.org extensions
add_filter('language_attributes','sgc_lang_atts');
function sgc_lang_atts($lang) {
	if (is_singular()) $scope = 'http://schema.org/BlogPosting';
	else $scope = 'http://schema.org/Blog';
	return ' itemscope itemtype="'.$scope.'" '.$lang;
}

function sgc_base_meta() {
	$meta = array();

	$options = get_option('sgc_options');
	// exclude bbPress post types 
	if ( function_exists('bbp_is_custom_post_type') && bbp_is_custom_post_type() ) return;

	$excerpt = '';
	if (is_singular()) {

		global $wp_the_query;
		if ( $id = $wp_the_query->get_queried_object_id() ) {
			$post = get_post( $id );
		}

		// get the content from the main post on the page
		$content = sgc_base_make_excerpt($post);

		$title = get_the_title();
		$permalink = get_permalink();

		$meta['name'] = esc_attr($title);
		$meta['description'] = esc_attr($content);
		$image = sgc_find_image($post);
		if ($image) $meta['image'] = $image;

	} else { // non singular pages need images and descriptions too
		if (!empty($options['default_image'])) {
			$meta['image'] = $options['default_image'];
		}
		if (!empty($options['default_description'])) { 
			$meta['description'] = esc_attr($options['default_description']);
		}
	}

	if (is_home()) {
		$meta['name'] = get_bloginfo("name");
	}

	$meta = apply_filters('sgc_base_meta',$meta, $post);

	foreach ($meta as $prop=>$content) {
		echo "<meta itemprop='{$prop}' content='{$content}' />\n";
	}
}

// finds a useful image for the post
function sgc_find_image($post) {
	
	$options = get_option('sgc_options');
	
	$content = apply_filters('the_content', $post->post_content);
	
	// if we get the post thumbnail, return it immediately
	if ( current_theme_supports('post-thumbnails') && has_post_thumbnail($post->ID) ) {
		$thumbid = get_post_thumbnail_id($post->ID);
		$att = wp_get_attachment_image_src($thumbid, 'full');
		if (!empty($att[0])) {
			return $att[0];
		}
	}
	
	if (is_attachment() && preg_match('!^image/!', get_post_mime_type( $post ))) {	
	    return wp_get_attachment_url($post->ID);
	}
	
	// now search for images in the content itself
	if ( preg_match_all('/<img\s+(.+?)>/i', $content, $matches) ) {
		foreach($matches[1] as $match) {
			foreach ( wp_kses_hair($match, array('http')) as $attr)
				$img[strtolower($attr['name'])] = $attr['value'];
			if ( isset($img['src']) ) {
				if ( !isset( $img['class'] ) || ( isset( $img['class'] ) && false === straipos( $img['class'], apply_filters( 'sgc_img_exclude', array( 'wp-smiley' ) ) ) ) ) { // ignore smilies
					if ( strpos( $img['src'], '/plugins/' ) === false // exclude any images put in from plugin dirs
					     ) {
						return $img['src'];
					}
				}
			}
		}
	}
	
	// no image found, return the default if we have one
	if (!empty($options['default_image'])) return $options['default_image'];
	
	// got nothing, return false
	return false;
}

// add rel=publisher link to front page only (as per google recommendations)
add_action('wp_head','sgc_rel_publisher');
function sgc_rel_publisher() {
	$options = get_option('sgc_options');	
	if ( is_front_page() && !empty($options['plus_page']) ) {
		echo '<link rel="publisher" href="https://plus.google.com/'.esc_attr($options['plus_page']).'" />'."\n";
	}
}

// add rel=author links to single pages (or everywhere if not multi-author blog)
add_action('wp_head','sgc_rel_author');
function sgc_rel_author() {
	$options = get_option('sgc_options');
	if ( is_singular() ) {
		// find the main post
		global $wp_the_query;
		if ( $id = $wp_the_query->get_queried_object_id() ) {
			$post = get_post( $id );
		}
		
		$googid = get_user_meta($post->post_author, 'goog_id', true);
		
		if (!$googid && !empty($options['single_author'])) $googid = $options['single_author'];
		
		if ($googid) {
			echo '<link rel="author" href="https://plus.google.com/'.esc_attr($googid).'" />'."\n";		
		}
	} else if ( !empty($options['single_author']) && empty($options['multi_author']) ) {
		echo '<link rel="author" href="https://plus.google.com/'.esc_attr($options['single_author']).'" />'."\n";
	}
}

