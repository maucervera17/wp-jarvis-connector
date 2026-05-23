<?php
/**
 * Plugin Name:       WP Jarvis Connector
 * Plugin URI:        https://wpjarvis.com
 * Description:       Connect your WordPress site to WP Jarvis — AI page builder. Build, preview and publish pages with AI without leaving your site.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            WP Jarvis
 * Author URI:        https://wpjarvis.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-jarvis-connector
 */

defined( 'ABSPATH' ) || exit;

define( 'WJC_VERSION',     '1.0.0' );
define( 'WJC_PLUGIN_FILE', __FILE__ );
define( 'WJC_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WJC_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'WJC_BACKEND_URL', 'https://wp-jarvis-dashboard-owner.onrender.com' );

require_once WJC_PLUGIN_DIR . 'includes/class-wjc-connect.php';
require_once WJC_PLUGIN_DIR . 'includes/class-wjc-api.php';
require_once WJC_PLUGIN_DIR . 'includes/class-wjc-admin.php';

function wjc_init() {
	( new WJC_Admin() )->init();
	( new WJC_API() )->init();
}
add_action( 'plugins_loaded', 'wjc_init' );

register_activation_hook( __FILE__, [ 'WJC_Connect', 'on_activate' ] );
register_deactivation_hook( __FILE__, [ 'WJC_Connect', 'on_deactivate' ] );
