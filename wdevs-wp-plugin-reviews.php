<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wijnberg.dev
 * @since             1.0.0
 * @package           Wdevs_WP_Plugin_Reviews
 *
 * @wordpress-plugin
 * Plugin Name:       WP Plugin Reviews for WooCommerce
 * Plugin URI:        https://products.wijnberg.dev/product/wordpress/plugins/wp-plugin-reviews-for-woocommerce/
 * Description:       Display WordPress.org plugin reviews directly in your WooCommerce product pages.
 * Version:           1.0.0
 * Author:            Wijnberg Developments
 * Author URI:        https://wijnberg.dev/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-plugin-reviews-for-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 7.0.0
 * WC tested up to:      10.0.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WDEVS_WP_PLUGIN_REVIEWS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wdevs-wp-plugin-reviews-activator.php
 */
function activate_wdevs_wp_plugin_reviews() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wdevs-wp-plugin-reviews-activator.php';
	Wdevs_WP_Plugin_Reviews_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wdevs-wp-plugin-reviews-deactivator.php
 */
function deactivate_wdevs_wp_plugin_reviews() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wdevs-wp-plugin-reviews-deactivator.php';
	Wdevs_WP_Plugin_Reviews_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wdevs_wp_plugin_reviews' );
register_deactivation_hook( __FILE__, 'deactivate_wdevs_wp_plugin_reviews' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wdevs-wp-plugin-reviews.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wdevs_wp_plugin_reviews() {

	$plugin = new Wdevs_WP_Plugin_Reviews();
	$plugin->run();

}
run_wdevs_wp_plugin_reviews();
