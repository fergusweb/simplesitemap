<?php
/**
 * Plugin Name: 	SimpleSitemap
 * Plugin URI:  	https://www.ferguson.codes/software/simplesitemap/
 * Description: Generate a sitemap to a page on your site.  By default, it will generate a list of Pages, Posts and Products.  You can use the shortcode to show custom types if you want to: <code>[simplesitemap view="POST_TYPE"]</code>
 * Version:     	1.4.1
 * Author:      	Anthony Ferguson
 * Author URI:  	https://www.ferguson.codes
 * License:     	GPLv3 or later
 * License URI: 	https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package SimpleSitemap
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The necessary plugin classes
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-simplesitemap.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-simplesitemap-public.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-simplesitemap-admin.php';

/**
 * Loader
 */
new SimpleSitemap();
