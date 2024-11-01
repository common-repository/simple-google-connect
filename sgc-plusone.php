<?php 
/* 
SGC Plus One button module
*/

global $sgc_plusone_defaults;
$sgc_plusone_defaults = array(
	'id' => 0,
	'url' => '',			// you can pass in a direct URL if you don't want to use a post id
	'layout' => 'standard', 	// small, standard, medium, or tall
	'annotation' => 'bubble',	// bubble, inline, or none
	'align' => 'left',		// left or right
	'width' => '',			// width in pixels. minimum for inline annotation is 120, but 250 is recommended
	);

function get_sgc_plusone_button($args='') {
	global $sgc_plusone_defaults;
		
	$options = get_option('sgc_options');
	if (!empty($options['plusone_layout'])) $sgc_plusone_defaults['layout']=$options['plusone_layout'];

	$args = wp_parse_args($args, $sgc_plusone_defaults);
			
	$params = '';
	if (!empty($args['layout'])) $params .= " size={$args['layout']}";
	if (!empty($args['annotation'])) $params .= " annotation={$args['annotation']}";
	if (!empty($args['align'])) $params .= " align={$args['align']}";
	if (!empty($args['width'])) $params .= " width={$args['width']}";
	
	if (!empty($args['url'])) $href = $args['url'];
	
	else $href = get_permalink($args['id']);

	$button = '<div class="plusone"><g:plusone'. $params .' href="'.$href.'"></g:plusone></div>';
	
	return $button;
}

function sgc_plusone_button($args='') {
	echo get_sgc_plusone_button($args);
}

function sgc_plusone_shortcode($atts) {
	global $sgc_plusone_defaults;

	$args = shortcode_atts($sgc_plusone_defaults, $atts);

	return get_sgc_plusone_button($args);
}
add_shortcode('google-plusone', 'sgc_plusone_shortcode');

function sgc_plusone_button_automatic($content) {
	global $post;
	$post_types = apply_filters('sgc_plusone_post_types', get_post_types( array('public' => true) ) );
	if ( !in_array($post->post_type, $post_types) ) return $content;
	
	// exclude bbPress post types 
	if ( function_exists('bbp_is_custom_post_type') && bbp_is_custom_post_type() ) return $content;
	
	$options = get_option('sgc_options');

	$button = get_sgc_plusone_button();
	switch ($options['plusone_position']) {
		case "before":
			$content = $button . $content;
			break;
		case "after":
			$content = $content . $button;
			break;
		case "both":
			$content = $button . $content . $button;
			break;
		case "manual":
		default:
			break;
	}
	return $content;
}
add_filter('the_content', 'sgc_plusone_button_automatic', 30);

function sgc_plusone_enqueue() {
	add_filter('sgc_load_scripts','__return_true');
}
add_action ('wp_enqueue_scripts','sgc_plusone_enqueue');

// add the admin sections to the sgc page
add_action('admin_init', 'sgc_plusone_admin_init');
function sgc_plusone_admin_init() {
	add_settings_section('sgc_plusone', __('Google +1 Button Settings', 'sgc'), 'sgc_plusone_section_callback', 'sgc');
	add_settings_field('sgc_plusone_position', __('Google +1 Button Position', 'sgc'), 'sgc_plusone_position', 'sgc', 'sgc_plusone');
	add_settings_field('sgc_plusone_preview', __('Google +1 Button Preview', 'sgc'), 'sgc_plusone_preview', 'sgc', 'sgc_plusone');
	add_settings_field('sgc_plusone_layout', __('Google +1 Button Layout', 'sgc'), 'sgc_plusone_layout', 'sgc', 'sgc_plusone');
	add_settings_field('sgc_plusone_annnotation', __('Google +1 Button Annotation', 'sgc'), 'sgc_plusone_annotation', 'sgc', 'sgc_plusone');
	wp_enqueue_script('google-plusone', 'https://apis.google.com/js/plusone.js', array(), null);
	add_action('admin_head','sgc_plusone_preview_script');
}

function sgc_plusone_section_callback() {
	echo '<p>'.__('Choose where you want the Google +1 button added to your content.', 'sgc').'</p>';
}

function sgc_plusone_preview_script() {
	$screen = get_current_screen();
	if ( isset($screen) && $screen->id != 'settings_page_sgc' )
		return;
?>
<script type="text/javascript">
function sgc_renderplusonepreview() {
		var params = [];
		params['size'] = jQuery('input:radio[name="sgc_options[plusone_layout]"]:checked').val();
		params['annotation'] = jQuery('input:radio[name="sgc_options[plusone_annotation]"]:checked').val();
		params['href'] = <?php echo json_encode(home_url('/')); ?>;
		gapi.plusone.render('plusonepreview', params);
}
jQuery(document).ready(function() {
	jQuery('input.sgcplusone').change(sgc_renderplusonepreview);
	sgc_renderplusonepreview();
});
</script>
<?php
}

function sgc_plusone_preview() {
	echo "<div id='plusonepreview'></div>";
}
	
function sgc_plusone_position() {
	$options = get_option('sgc_options');
	if (!isset($options['plusone_position'])) $options['plusone_position'] = 'manual';
	?>
	<ul>
	<li><label><input type="radio" class="sgcplusone" name="sgc_options[plusone_position]" value="before" <?php checked('before', $options['plusone_position']); ?> /> <?php _e('Before the content of your post', 'sgc'); ?></label></li>
	<li><label><input type="radio" class="sgcplusone" name="sgc_options[plusone_position]" value="after" <?php checked('after', $options['plusone_position']); ?> /> <?php _e('After the content of your post', 'sgc'); ?></label></li>
	<li><label><input type="radio" class="sgcplusone" name="sgc_options[plusone_position]" value="both" <?php checked('both', $options['plusone_position']); ?> /> <?php _e('Before AND After the content of your post', 'sgc'); ?></label></li>
	<li><label><input type="radio" class="sgcplusone" name="sgc_options[plusone_position]" value="manual" <?php checked('manual', $options['plusone_position']); ?> /> <?php _e('Manually add the button to your theme or posts (use the sgc_plusone_button() function in your theme)', 'sgc'); ?></label></li>
	</ul>
<?php
}

function sgc_plusone_layout() {
	$options = get_option('sgc_options');
	if (!isset($options['plusone_layout'])) $options['plusone_layout'] = 'standard';
	?>
	<ul>
	<li><label><input type="radio" class="sgcplusone" name="sgc_options[plusone_layout]" value="small" <?php checked('small', $options['plusone_layout']); ?> /> <?php _e('Small (15px)', 'sgc'); ?></label></li>
	<li><label><input type="radio" class="sgcplusone" name="sgc_options[plusone_layout]" value="medium" <?php checked('medium', $options['plusone_layout']); ?> /> <?php _e('Medium (20px)', 'sgc'); ?></label></li>
	<li><label><input type="radio" class="sgcplusone" name="sgc_options[plusone_layout]" value="standard" <?php checked('standard', $options['plusone_layout']); ?> /> <?php _e('Standard (24px)', 'sgc'); ?></label></li>
	<li><label><input type="radio" class="sgcplusone" name="sgc_options[plusone_layout]" value="tall" <?php checked('tall', $options['plusone_layout']); ?> /> <?php _e('Tall (60px)', 'sgc'); ?></label></li>
	</ul>
<?php
}

function sgc_plusone_annotation() {
	$options = get_option('sgc_options');
	if (!isset($options['plusone_annotation'])) $options['plusone_annotation'] = 'bubble';
	?>
	<ul>
	<li><label><input type="radio" class="sgcplusone" name="sgc_options[plusone_annotation]" value="bubble" <?php checked('bubble', $options['plusone_annotation']); ?> /> <?php _e('Bubble', 'sgc'); ?></label></li>
	<li><label><input type="radio" class="sgcplusone" name="sgc_options[plusone_annotation]" value="inline" <?php checked('inline', $options['plusone_annotation']); ?> /> <?php _e('Inline', 'sgc'); ?></label></li>
	<li><label><input type="radio" class="sgcplusone" name="sgc_options[plusone_annotation]" value="none" <?php checked('none', $options['plusone_annotation']); ?> /> <?php _e('None', 'sgc'); ?></label></li>
	</ul>
<?php
}

add_action('sgc_help','sgc_plusone_help');
function sgc_plusone_help($screen) {
	$screen->add_help_tab( array(
		'id'      => 'sgc-plusone',
		'title'   => __('+1 Button', 'sgc'),
		'content' => __("<h3>+1 Button</h3>
			<p>The +1 Button module will let you add Google +1 buttons on the site.</p>
			<p>You have the option of automatically adding it to all posts and pages (even custom post types), or using it manually.</p>
			<h3>Manual Usage</h3>
			<p>You can either add <code>sgc_plusone_button();</code> calls in your theme where you want the button to appear, or use the 
			<code>[google-plusone]</code> shortcode in posts.</p>
			<p>For you coders: The sfc_plusone_button() function call accepts arguments in the form of an array or a standard query string. 
			See the <code>sgc_plusone_defaults</code> array in sgc-plusone.php for the possible arguments you can use.</p>"
			,'sgc'),
	));
}