<?php
/*
Plugin Name: Simple Google Connect 
Plugin URI: http://ottopress.com/wordpress-plugins/simple-google-connect
Description: Makes it easy for your site to connect to Google, in a wholly modular way.
Author: Otto
Version: 0.1
Author URI: http://ottopress.com
License: GPL2

    Copyright 2011 Samuel Wood  (email : otto@ottodestruct.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2, 
    as published by the Free Software Foundation. 
    
    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    The license for this software can likely be found here: 
    http://www.gnu.org/licenses/gpl-2.0.html
    
*/

function sgc_version() {
	return '0.1';
}

global $wp_version;
// prevent parsing errors on old WP installs
if ( version_compare( $wp_version, '3.2.999', '>' ) ) {
	include 'sgc-base.php';
} else {
	add_action('admin_notices', create_function( '', "echo '<div class=\"error\"><p>".__('Simple Google Connect requires WordPress 3.3 to function. Please upgrade or deactivate the SGC plugin.', 'sgc') ."</p></div>';" ) );
}

// plugin row links
add_filter('plugin_row_meta', 'sgc_donate_link', 10, 2);
function sgc_donate_link($links, $file) {
	if ($file == plugin_basename(__FILE__)) {
		$links[] = '<a href="'.admin_url('options-general.php?page=sgc').'">'.__('Settings', 'sgc').'</a>';
		$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=otto%40ottodestruct%2ecom">'.__('Donate', 'sgc').'</a>';
	}
	return $links;
}

// action links
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'sgc_settings_link', 10, 1);
function sgc_settings_link($links) {
	$links[] = '<a href="'.admin_url('options-general.php?page=sgc').'">'.__('Settings', 'sgc').'</a>';
	return $links;
}

