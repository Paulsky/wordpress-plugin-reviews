<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used for
 * WooCommerce integration and WordPress.org reviews functionality.
 *
 * @link       https://wijnberg.dev
 * @since      1.0.0
 *
 * @package    Wdevs_WP_Plugin_Reviews
 * @subpackage Wdevs_WP_Plugin_Reviews/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define WooCommerce integration hooks and load dependencies.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wdevs_WP_Plugin_Reviews
 * @subpackage Wdevs_WP_Plugin_Reviews/includes
 * @author     Wijnberg Developments <contact@wijnberg.dev>
 */
class Wdevs_WP_Plugin_Reviews {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wdevs_WP_Plugin_Reviews_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies and set the hooks for WooCommerce integration.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WDEVS_WP_PLUGIN_REVIEWS_VERSION' ) ) {
			$this->version = WDEVS_WP_PLUGIN_REVIEWS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wp-plugin-reviews-for-woocommerce';

		$this->load_dependencies();
		$this->define_woocommerce_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wdevs_WP_Plugin_Reviews_Loader. Orchestrates the hooks of the plugin.
	 * - Wdevs_WP_Plugin_Reviews_Woocommerce. Defines WooCommerce integration.
	 * - Wdevs_WP_Plugin_Reviews_Data_Manager. Handles API and data caching.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wdevs-wp-plugin-reviews-loader.php';

		/**
		 * The class responsible for WooCommerce integration.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wdevs-wp-plugin-reviews-woocommerce.php';
		
		/**
		 * The data manager for efficient API handling.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wdevs-wp-plugin-reviews-data-manager.php';

		$this->loader = new Wdevs_WP_Plugin_Reviews_Loader();

	}



	/**
	 * Register all of the hooks related to WooCommerce functionality.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_woocommerce_hooks() {

		$plugin_woocommerce = new Wdevs_WP_Plugin_Reviews_Woocommerce( $this->get_plugin_name(), $this->get_version() );

		// Product meta field hooks
		$this->loader->add_action( 'woocommerce_product_options_general_product_data', $plugin_woocommerce, 'add_product_plugin_slug_field' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_woocommerce, 'save_product_plugin_slug_field' );

		// Review filtering hooks (comments_pre_query disabled for separate tab approach)
		$this->loader->add_filter( 'woocommerce_product_get_review_count', $plugin_woocommerce, 'filter_product_review_count', 10, 2 );
		$this->loader->add_filter( 'woocommerce_product_get_average_rating', $plugin_woocommerce, 'filter_product_average_rating', 10, 2 );
		$this->loader->add_filter( 'get_comment_metadata', $plugin_woocommerce, 'filter_comment_metadata', 10, 4 );
		$this->loader->add_filter( 'woocommerce_product_get_rating_counts', $plugin_woocommerce, 'filter_product_rating_counts', 10, 2 );
		
		// WordPress.org reviews tab
		$this->loader->add_filter( 'woocommerce_product_tabs', $plugin_woocommerce, 'add_wordpress_org_reviews_tab', 25 );

		$this->loader->add_filter('woocommerce_reviews_title', $plugin_woocommerce, 'change_woocommerce_reviews_title', 10, 3);

		$this->loader->add_filter('get_avatar', $plugin_woocommerce, 'set_comment_avatar', 10, 6);

		// WooCommerce compatibility and settings
		$this->loader->add_action( 'before_woocommerce_init', $plugin_woocommerce, 'declare_compatibility' );
		$this->loader->add_filter( 'woocommerce_settings_tabs_array', $plugin_woocommerce, 'add_settings_tab', 50 );
		$this->loader->add_action( 'woocommerce_settings_tabs_wdevs_wppr', $plugin_woocommerce, 'settings_tab' );
		$this->loader->add_action( 'woocommerce_after_settings_wdevs_wppr', $plugin_woocommerce, 'render_footer_info' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wdevs_WP_Plugin_Reviews_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
