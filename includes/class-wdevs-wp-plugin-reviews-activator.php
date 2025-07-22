<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wijnberg.dev
 * @since      1.0.0
 *
 * @package    Wdevs_WP_Plugin_Reviews
 * @subpackage Wdevs_WP_Plugin_Reviews/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wdevs_WP_Plugin_Reviews
 * @subpackage Wdevs_WP_Plugin_Reviews/includes
 * @author     Wijnberg Developments <contact@wijnberg.dev>
 */
class Wdevs_WP_Plugin_Reviews_Activator {

	/**
	 * Check for WooCommerce dependency and activate plugin.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );

			wp_die( esc_html__( 'This plugin requires WooCommerce. Please install and activate WooCommerce before activating this plugin.', 'wp-plugin-reviews-for-woocommerce' ) );
		}

	}

}
