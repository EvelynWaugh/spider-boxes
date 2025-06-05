<?php
/**
 * Plugin Name: Spider Boxes
 * Plugin URI: https://example.com/spider-boxes
 * Description: A WordPress plugin with WooCommerce integration — designed more as a developer library or module for creating custom meta boxes for various page types (post meta, taxonomy meta, comments meta, settings).
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: spider-boxes
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.1
 * WC requires at least: 7.0
 * WC tested up to: 8.0
 *
 * @package SpiderBoxes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants
define( 'SPIDER_BOXES_VERSION', '1.0.0' );
define( 'SPIDER_BOXES_PLUGIN_FILE', __FILE__ );
define( 'SPIDER_BOXES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPIDER_BOXES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SPIDER_BOXES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Check if Composer autoloader exists
$autoloader = SPIDER_BOXES_PLUGIN_DIR . 'vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p><strong>Spider Boxes:</strong> Please run <code>composer install</code> in the plugin directory.</p></div>';
		}
	);
	return;
}

require_once $autoloader;

// Include the main plugin class
require_once SPIDER_BOXES_PLUGIN_DIR . 'includes/class-spider-boxes.php';

/**
 * The main function to load the plugin
 */
function spider_boxes() {
	return SpiderBoxes\SpiderBoxes::get_instance();
}

// Initialize the plugin
spider_boxes();

/**
 * Plugin activation hook
 */
register_activation_hook(
	__FILE__,
	function () {
		do_action( 'spider_boxes_activation' );
	}
);

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(
	__FILE__,
	function () {
		do_action( 'spider_boxes_deactivation' );
	}
);

/**
 * HPOS compatible.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
