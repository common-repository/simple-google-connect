<?php
/*
Plus badge widget and shortcode
*/

global $sgc_plusbadge_defaults;
$sgc_plusbadge_defaults = array(
	'page' => 0,		// Google+ page number to use
	'size' => 'badge',	// badge or smallbadge
	);
	
function get_sgc_plusbadge($args='') {	
	$options = get_option('sgc_options');
	if (!empty($options['plus_page'])) $sgc_plusbadge_defaults['page']=$options['plus_page'];
	if (!empty($options['plusbadge_size'])) $sgc_plusbadge_defaults['size']=$options['plusbadge_size'];

	$args = wp_parse_args($args, $sgc_plusbadge_defaults);

	// we need a page number to do this
	if (empty($args['page'])) return '';
	
	$params = '';
	if (!empty($args['size'])) $params .= " size={$args['size']}";
	
	$badge = '<div class="plusbadge"><g:plus href="https://plus.google.com/'.$options['plus_page'].'"'.$params.'></g:plus></div>';
	
	return $badge;
}

function sgc_plusbadge($args='') {
	echo get_sgc_plusbadge($args);
}

add_shortcode('google-plusbadge', 'sgc_plusbadge_shortcode');
function sgc_plusbadge_shortcode($atts) {
	global $sgc_plusbadge_defaults;

	$args = shortcode_atts($sgc_plusbadge_defaults, $atts);

	return get_sgc_plusbadge($args);
}

add_action ('wp_enqueue_scripts','sgc_plusbadge_enqueue');
function sgc_plusbadge_enqueue() {
	add_filter('sgc_load_scripts','__return_true');
}

add_action('admin_init', 'sgc_plusbadge_admin_init');
function sgc_plusbadge_admin_init() {
	$options = get_option('sgc_options');
	add_settings_section('sgc_plusbadge', __('Google+ Badge Settings', 'sgc'), 'sgc_plusbadge_section_callback', 'sgc');
	if (!empty($options['plus_page'])) {
		add_settings_field('sgc_plusbadge_preview', __('Google+ Badge Preview', 'sgc'), 'sgc_plusbadge_preview', 'sgc', 'sgc_plusbadge');
		add_settings_field('sgc_plusbadge_size', __('Google+ Badge Size', 'sgc'), 'sgc_plusbadge_size', 'sgc', 'sgc_plusbadge');
		wp_enqueue_script('google-plusbadge', 'https://apis.google.com/js/plusone.js', array(), null);
		add_action('admin_head','sgc_plusbadge_preview_script');
	}
}

function sgc_plusbadge_section_callback() {
	echo '<p>'.__("The Google+ Badge only works with branded Google+ Pages (like <a href='https://plus.google.com/111166992820603637934'>this one</a>).
	It will not work with individual Google+ user accounts.	You'll need to have a Google+ Page to use it.", 'sgc').'</p>';
}

function sgc_plusbadge_preview_script() {
?>
<script type="text/javascript">
function sgc_renderbadgepreview() {
	var params = [];
	var page = jQuery('input[name="sgc_options[plus_page]"]').val();
	if (!jQuery.isNumeric( page )) return false;
	params['size'] = jQuery('input:radio[name="sgc_options[plusbadge_size]"]:checked').val();
	params['href'] = 'http://plus.google.com/' + page;
	gapi.plus.render('plusbadgepreview', params);
}
jQuery(document).ready(function() {
	jQuery('input.sgcbadge').change(sgc_renderbadgepreview);
	sgc_renderbadgepreview();
});
</script>
<?php
}

function sgc_plusbadge_preview() {
	echo "<div id='plusbadgepreview'></div>";
}
	
function sgc_plusbadge_size() {
	$options = get_option('sgc_options');
	if (!isset($options['plusbadge_size'])) $options['plusbadge_size'] = 'badge';
	?>
	<ul>
	<li><label><input type="radio" class="sgcbadge" name="sgc_options[plusbadge_size]" value="badge" <?php checked('badge', $options['plusbadge_size']); ?> /> <?php _e('Normal', 'sgc'); ?></label></li>
	<li><label><input type="radio" class="sgcbadge" name="sgc_options[plusbadge_size]" value="smallbadge" <?php checked('smallbadge', $options['plusbadge_size']); ?> /> <?php _e('Small', 'sgc'); ?></label></li>
	</ul>
<?php
}

add_action('sgc_help','sgc_plusbadge_help');
function sgc_plusbadge_help($screen) {
	$screen->add_help_tab( array(
		'id'      => 'sgc-plusbadge',
		'title'   => __('Google+ Badge', 'sgc'),
		'content' => __("<h3>Google+ Badge</h3>
			<p>The Google+ Badge is only available to sites that have made a Google+ page for their site or brand.</p>
			<p>After filling in the page number, a widget will be available for you to put the badge in the sidebar.</p>
			<p>(Note: The width of the badge is 300 pixels, and this cannot be changed. Complain to Google, it's their code.)</p>
			<h3>Manual Usage</h3>
			<p>You can either add <code>sgc_plusbadge();</code> calls in your theme where you want the button to appear, or use the 
			<code>[google-plusbadge]</code> shortcode in posts.</p>
			<p>For you coders: The sgc_plusbadge() function call accepts arguments in the form of an array or a standard query string. 
			See the <code>sgc_plusbadge_defaults</code> array in sgc-plusbadge.php for the possible arguments you can use.</p>"
			,'sgc'),
	));
}

class SGC_Plusbadge_Widget extends WP_Widget {
	function SGC_Plusbadge_Widget() {
		$widget_ops = array('classname' => 'widget_sgc-plusbadge', 'description' => __('Google Plus Badge', 'sgc'));
		$this->WP_Widget('sgc-plusbadge', __('Google Plus Badge (SGC)', 'sgc'), $widget_ops);
	}

	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		echo $before_widget;
		if ( $title ) echo $before_title . $title . $after_title;
		
		sgc_plusbadge($instance);
		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, array( 'title' => '', 'width'=>260, 'height'=>400, 'bordercolor'=>'000000', 'font'=>'lucida+grande', 'colorscheme'=>'light') );
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'width'=>260, 'height'=>400, 'bordercolor'=>'000000', 'font'=>'lucida+grande', 'colorscheme'=>'light' ) );
		$title = strip_tags($instance['title']);
		?>
<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?>
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</label></p>
		<?php
	}
}

$options = get_option('sgc_options');
if (!empty($options['plus_page'])) {
	add_action('widgets_init', create_function('', 'return register_widget("SGC_Plusbadge_Widget");'));
}

add_action('wp_head', 'sgc_plusbadge_head');
function sgc_plusbadge_head() {
	if (!empty($options['plus_page'])) {
		echo "<link href='https://plus.google.com/{$options['plus_page']}' rel='publisher' />";
	}
}
