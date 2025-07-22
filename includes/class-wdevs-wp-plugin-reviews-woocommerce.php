<?php

/**
 * WooCommerce-specific functionality of the plugin.
 *
 * @link       https://wijnberg.dev
 * @since      1.0.0
 *
 * @package    Wdevs_WP_Plugin_Reviews
 * @subpackage Wdevs_WP_Plugin_Reviews/includes
 */

/**
 * WooCommerce-specific functionality of the plugin.
 *
 * Defines WooCommerce hooks and filters for product meta fields
 * and other WooCommerce integrations.
 *
 * @package    Wdevs_WP_Plugin_Reviews
 * @subpackage Wdevs_WP_Plugin_Reviews/includes
 * @author     Wijnberg Developments <contact@wijnberg.dev>
 */
class Wdevs_WP_Plugin_Reviews_Woocommerce {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Data manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wdevs_WP_Plugin_Reviews_Data_Manager
	 */
	private $data_manager;

	/**
	 * Current settings section.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $current_section;

	// ===============================================
	// CONSTRUCTOR & CORE SETUP
	// ===============================================

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name  = $plugin_name;
		$this->version      = $version;
		$this->data_manager = Wdevs_WP_Plugin_Reviews_Data_Manager::get_instance();

		$this->current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

		if ( is_admin() ) {
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

			if ( $page === 'wc-settings' && $tab === 'wdevs_wppr' ) {
				$this->handle_sections();
			}
		}
	}

	// ===============================================
	// WOOCOMMERCE COMPATIBILITY
	// ===============================================

	/**
	 * Declare WooCommerce compatibility.
	 *
	 * @since 1.0.0
	 */
	public function declare_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'wp-plugin-reviews-for-woocommerce.php', true );
		}
	}

	// ===============================================
	// PRODUCT META FIELDS
	// ===============================================

	/**
	 * Add custom field to WooCommerce product general tab.
	 *
	 * @since    1.0.0
	 */
	public function add_product_plugin_slug_field() {
		woocommerce_wp_text_input(
			array(
				'id'          => '_wdevs_wppr_plugin_slug',
				'label'       => __( 'WordPress.org Plugin Slug', 'wp-plugin-reviews-for-woocommerce' ),
				'placeholder' => __( 'e.g. woocommerce', 'wp-plugin-reviews-for-woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'The plugin slug from WordPress.org plugin directory (e.g. "woocommerce" from wordpress.org/plugins/woocommerce/).', 'wp-plugin-reviews-for-woocommerce' ),
			)
		);
	}

	/**
	 * Save custom field data.
	 *
	 * @param int $post_id The product ID.
	 *
	 * @since    1.0.0
	 */
	public function save_product_plugin_slug_field( $post_id ) {
		$plugin_slug = isset( $_POST['_wdevs_wppr_plugin_slug'] ) ? sanitize_text_field( $_POST['_wdevs_wppr_plugin_slug'] ) : '';
		update_post_meta( $post_id, '_wdevs_wppr_plugin_slug', $plugin_slug );
	}

	/**
	 * Get the plugin slug for a product.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return   string                The plugin slug or empty string.
	 * @since    1.0.0
	 */
	public static function get_plugin_slug( $product_id ) {
		return get_post_meta( $product_id, '_wdevs_wppr_plugin_slug', true );
	}

	/**
	 * Set the plugin slug for a product.
	 *
	 * @param int $product_id The product ID.
	 * @param string $plugin_slug The plugin slug.
	 *
	 * @return   bool                  True on success, false on failure.
	 * @since    1.0.0
	 */
	public static function set_plugin_slug( $product_id, $plugin_slug ) {
		$sanitized_slug = sanitize_text_field( $plugin_slug );
		return update_post_meta( $product_id, '_wdevs_wppr_plugin_slug', $sanitized_slug );
	}

	// ===============================================
	// REVIEW DATA INTEGRATION (FILTERS)
	// ===============================================

	/**
	 * Filter WooCommerce product review count.
	 *
	 * @param int $count The review count.
	 * @param WC_Product $product The product object.
	 *
	 * @return   int                 Modified review count.
	 * @since    1.0.0
	 */
	public function filter_product_review_count( $count, $product ) {
		$product_data = $this->data_manager->get_product_data( $product->get_id() );

		if ( ! $product_data || ! $product_data['has_data'] ) {
			return $count;
		}

		// Add WordPress.org reviews to WooCommerce review count
		return $count + $product_data['total_count'];
	}

	/**
	 * Filter WooCommerce product average rating.
	 *
	 * @param string $average The average rating.
	 * @param WC_Product $product The product object.
	 *
	 * @return   string              Modified average rating.
	 * @since    1.0.0
	 */
	public function filter_product_average_rating( $average, $product ) {
		$product_data = $this->data_manager->get_product_data( $product->get_id() );

		if ( ! $product_data || ! $product_data['has_data'] || empty( $product_data['rating_info']['ratings'] ) ) {
			return $average;
		}

		// Get WooCommerce ratings data (cached)
		$wc_ratings = $this->data_manager->get_wc_ratings_data( $product->get_id() );

		// Calculate WordPress.org weighted sum and count
		$wporg_ratings_sum   = 0;
		$wporg_ratings_count = 0;

		foreach ( $product_data['rating_info']['ratings'] as $stars => $count ) {
			$wporg_ratings_sum   += ( $stars * $count );
			$wporg_ratings_count += $count;
		}

		// Calculate combined average
		$total_sum   = $wc_ratings['sum'] + $wporg_ratings_sum;
		$total_count = $wc_ratings['count'] + $wporg_ratings_count;

		if ( $total_count > 0 ) {
			return number_format( $total_sum / $total_count, 2, '.', '' );
		}

		return $average;
	}

	/**
	 * Filter WooCommerce product rating counts.
	 *
	 * @param array $counts Array of rating counts indexed by rating value.
	 * @param WC_Product $product The product object.
	 *
	 * @return   array              Modified rating counts.
	 * @since    1.0.0
	 */
	public function filter_product_rating_counts( $counts, $product ) {
		$product_data = $this->data_manager->get_product_data( $product->get_id() );

		if ( ! $product_data || ! $product_data['has_data'] || empty( $product_data['rating_info']['ratings'] ) ) {
			return $counts;
		}

		// Merge WordPress.org ratings with WooCommerce ratings
		foreach ( $product_data['rating_info']['ratings'] as $stars => $count ) {
			if ( isset( $counts[ $stars ] ) ) {
				$counts[ $stars ] += $count;
			} else {
				$counts[ $stars ] = $count;
			}
		}

		return $counts;
	}

	/**
	 * Filter comment metadata.
	 *
	 * @param mixed $value The value of the metadata.
	 * @param int $object_id The comment ID.
	 * @param string $meta_key The metadata key.
	 * @param bool $single Whether to return a single value.
	 *
	 * @return   mixed              Modified metadata value.
	 * @since    1.0.0
	 */
	public function filter_comment_metadata( $value, $object_id, $meta_key, $single ) {
		// Only intercept rating metadata for single values
		if ( 'rating' !== $meta_key || ! $single ) {
			return $value;
		}

		// Check all cached products for this comment ID
		foreach ( $this->data_manager->get_comment_ratings() as $comment_id => $rating ) {
			if ( $comment_id == $object_id ) {
				return $rating;
			}
		}

		return $value;
	}

	/**
	 * Filter WooCommerce reviews title.
	 *
	 * @param string $title The review title.
	 * @param int $count The review count.
	 * @param WC_Product $product The product object.
	 *
	 * @return string Modified review title.
	 * @since 1.0.0
	 */
	public function change_woocommerce_reviews_title( $title, $count, $product ) {
		$product_data = $this->data_manager->get_product_data( $product->get_id() );

		if ( ! $product_data || ! $product_data['has_data'] ) {
			return $title;
		}

		$adjusted_count = max( 0, $count - $product_data['total_count'] );
		$title          = sprintf(
			esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $adjusted_count, 'woocommerce' ) ),
			esc_html( $adjusted_count ),
			'<span>' . get_the_title() . '</span>'
		);

		return $title;
	}

	/**
	 * Filter comment avatar.
	 *
	 * @param string $avatar Avatar HTML.
	 * @param mixed $id_or_email User ID, email, or comment object.
	 * @param int $size Avatar size.
	 * @param string $default Default avatar.
	 * @param string $alt Alt text.
	 * @param array $args Avatar arguments.
	 *
	 * @return string Modified avatar HTML.
	 * @since 1.0.0
	 */
	public function set_comment_avatar( $avatar, $id_or_email, $size, $default, $alt, $args ) {
		if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) && isset( $id_or_email->wdevs_wppr_avatar ) ) {
			if ( isset( $id_or_email->wdevs_wppr_avatar['src'] ) ) {
				if ( isset( $id_or_email->wdevs_wppr_avatar['alt'] ) ) {
					$alt = $id_or_email->wdevs_wppr_avatar['alt'];
				}

				return sprintf(
					'<img alt="%s" src="%s" class="avatar avatar-%d photo" height="%d" width="%d" />',
					esc_attr( $alt ),
					esc_url( $id_or_email->wdevs_wppr_avatar['src'] ),
					$size,
					$size,
					$size
				);
			}
		}

		return $avatar;
	}

	// ===============================================
	// PRODUCT TABS
	// ===============================================

	/**
	 * Add WordPress.org reviews tab to product tabs.
	 *
	 * @param array $tabs Existing product tabs.
	 *
	 * @return   array       Modified tabs array.
	 * @since    1.0.0
	 */
	public function add_wordpress_org_reviews_tab( $tabs ) {
		global $product;

		if ( ! $product ) {
			return $tabs;
		}

		$product_data = $this->data_manager->get_product_data( $product->get_id() );

		if ( ! $product_data || ! $product_data['has_data'] ) {
			return $tabs;
		}

		$tabs['wordpress_org_reviews'] = array(
			'title'    => sprintf( __( 'WordPress.org Reviews (%d)', 'wp-plugin-reviews-for-woocommerce' ), $product_data['total_count'] ),
			'callback' => array( $this, 'wordpress_org_reviews_tab_content' ),
		);

		if ( isset( $tabs['reviews'] ) && is_product() ) {
			$count          = $product->get_review_count();
			$adjusted_count = max( 0, $count - $product_data['total_count'] );

			$tabs['reviews']['title'] = sprintf( __( 'Reviews (%d)', 'woocommerce' ), $adjusted_count );
		}

		return $tabs;
	}

	/**
	 * Display content for WordPress.org reviews tab.
	 *
	 * @since    1.0.0
	 */
	public function wordpress_org_reviews_tab_content() {
		global $product;

		if ( ! $product ) {
			return;
		}

		$product_data = $this->data_manager->get_product_data( $product->get_id() );

		// Handle errors or no data
		if ( ! $product_data ) {
			echo '<div id="wordpress-org-reviews" class="wdevs-wordpress-org-reviews">';
			echo '<p>' . esc_html__( 'Unable to load WordPress.org reviews at this time.', 'wp-plugin-reviews-for-woocommerce' ) . '</p>';
			echo '</div>';

			return;
		}

		if ( ! $product_data['has_data'] ) {
			echo '<div id="wordpress-org-reviews" class="wdevs-wordpress-org-reviews">';
			echo '<p>' . esc_html__( 'No WordPress.org reviews found for this plugin.', 'wp-plugin-reviews-for-woocommerce' ) . '</p>';
			echo '</div>';

			return;
		}

		// Load template with variables (all data already cached)
		$this->load_template(
			'section-wdevs-wp-plugin-reviews-tab-content.php',
			array(
				'total_count' => $product_data['total_count'],
				'wp_comments' => $product_data['wp_comments'],
				'plugin_slug' => $product_data['plugin_slug'],
				'product'     => $product,
				'reviews'     => $product_data['reviews'],
			)
		);
	}

	// ===============================================
	// WOOCOMMERCE SETTINGS
	// ===============================================

	/**
	 * Add settings tab to WooCommerce settings.
	 *
	 * @param array $settings_tabs Existing settings tabs.
	 *
	 * @return array Modified settings tabs.
	 * @since 1.0.0
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['wdevs_wppr'] = __( 'WooCommerce WP Plugin Reviews', 'wp-plugin-reviews-for-woocommerce' );
		return $settings_tabs;
	}

	/**
	 * Display settings tab content.
	 *
	 * @since 1.0.0
	 */
	public function settings_tab() {
		woocommerce_admin_fields( $this->get_settings() );
	}

	/**
	 * Render footer info for settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_footer_info() {
		$text = sprintf(
		/* translators: %s: Link to author site. */
			__( 'WooCommerce WP Plugin Reviews is developed by %s. Your trusted WordPress & WooCommerce plugin partner from the Netherlands.', 'wp-plugin-reviews-for-woocommerce' ),
			'<a href="https://products.wijnberg.dev" target="_blank" rel="noopener">Wijnberg Developments</a>'
		);

		echo '<span style="padding: 0 30px; background: #f0f0f1; display: block;">' . wp_kses_post( $text ) . '</span>';
	}

	/**
	 * Handle WooCommerce settings sections.
	 *
	 * @since 1.0.0
	 */
	private function handle_sections() {
		add_action( 'woocommerce_sections_wdevs_wppr', array( $this, 'output_sections' ) );
		add_action( 'woocommerce_update_options_wdevs_wppr', array( $this, 'update_settings' ) );
	}

	/**
	 * Output settings sections navigation.
	 *
	 * @since 1.0.0
	 */
	public function output_sections() {
		$sections = $this->get_sections();
		$documentationURL = 'https://products.wijnberg.dev/product/wordpress/plugins/wp-plugin-reviews-for-woocommerce/';

		echo '<ul class="subsubsub">';

		foreach ( $sections as $id => $label ) {
			$url       = admin_url( 'admin.php?page=wc-settings&tab=wdevs_wppr&section=' . sanitize_title( $id ) );
			$class     = ( $this->current_section === $id ? 'current' : '' );
			$separator = '|';

			echo '<li><a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</a> ' . esc_html( $separator ) . '</li>';
		}

		?>
        <li>
            <a href="<?php echo esc_attr( $documentationURL ); ?>" target="_blank">
				<?php esc_html_e( 'Documentation', 'wp-plugin-reviews-for-woocommerce' ); ?>
                <svg style="width: 0.8rem; height: 0.8rem; stroke: currentColor; fill: none;"
                     xmlns="http://www.w3.org/2000/svg"
                     stroke-width="10" stroke-dashoffset="0"
                     stroke-dasharray="0" stroke-linecap="round"
                     stroke-linejoin="round" viewBox="0 0 100 100">
                    <polyline fill="none" points="40 20 20 20 20 90 80 90 80 60"/>
                    <polyline fill="none" points="60 10 90 10 90 40"/>
                    <line fill="none" x1="89" y1="11" x2="50" y2="50"/>
                </svg>
            </a>
        </li>
		<?php

		echo '</ul><br class="clear" />';
	}

	/**
	 * Get available settings sections.
	 *
	 * @since 1.0.0
	 * @return array Available sections.
	 */
	public function get_sections() {
		return array(
			'' => __( 'General', 'wp-plugin-reviews-for-woocommerce' ),
		);
	}

	/**
	 * Update settings when form is submitted.
	 *
	 * @since 1.0.0
	 */
	public function update_settings() {
		woocommerce_update_options( $this->get_settings() );
		
		// Check if clear cache was requested
		if ( 'yes' === get_option( 'wdevs_wppr_clear_cache' ) ) {
			$this->clear_all_plugin_caches();
			
			// Reset the checkbox to unchecked after clearing
			update_option( 'wdevs_wppr_clear_cache', 'no' );
			
			// Add admin notice for feedback
			add_action( 'admin_notices', array( $this, 'cache_cleared_notice' ) );
		}
	}

	/**
	 * Get settings configuration based on current section.
	 *
	 * @since 1.0.0
	 * @return array Settings configuration.
	 */
	public function get_settings() {
		switch ( $this->current_section ) {
			default:
				return $this->get_main_settings();
		}
	}

	/**
	 * Get main settings configuration.
	 *
	 * @since 1.0.0
	 * @return array Settings configuration array.
	 */
	private function get_main_settings() {
		return array(
			array(
				'title' => __( 'General Settings', 'wp-plugin-reviews-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Configure how WordPress.org plugin reviews are displayed in your WooCommerce store.', 'wp-plugin-reviews-for-woocommerce' ),
				'id'    => 'wdevs_wppr_general_settings'
			),
			array(
				'title'             => __( 'Cache Duration (Hours)', 'wp-plugin-reviews-for-woocommerce' ),
				'desc'              => __( 'How long to cache WordPress.org review data. Lower values = more fresh data, higher server load.', 'wp-plugin-reviews-for-woocommerce' ),
				'id'                => 'wdevs_wppr_cache_duration',
				'type'              => 'number',
				'default'           => 24,
				'desc_tip'          => true,
				'custom_attributes' => array(
					'min'  => 1,
					'max'  => 168,
					'step' => 1,
				),
			),
			array(
				'title'   => __( 'Clear All Caches', 'wp-plugin-reviews-for-woocommerce' ),
				'desc'    => __( 'Clear all cached WordPress.org review data immediately', 'wp-plugin-reviews-for-woocommerce' ),
				'id'      => 'wdevs_wppr_clear_cache',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wdevs_wppr_general_settings'
			),
		);
	}

	/**
	 * Clear all plugin caches.
	 *
	 * @since 1.0.0
	 */
	private function clear_all_plugin_caches() {
		// Clear data manager cache
		$this->data_manager->clear_all_cache();
		
		// Clear all transients with our prefix
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wdevs_wppr_%' OR option_name LIKE '_transient_timeout_wdevs_wppr_%'" );
		
		// Clear object cache group
		wp_cache_flush_group( 'wdevs_wppr' );
		wp_cache_flush_group( 'wdevs_wppr_api' );
	}

	/**
	 * Display admin notice after cache is cleared.
	 *
	 * @since 1.0.0
	 */
	public function cache_cleared_notice() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'All WordPress.org plugin review caches have been cleared successfully.', 'wp-plugin-reviews-for-woocommerce' ); ?></p>
		</div>
		<?php
	}

	// ===============================================
	// UTILITY/HELPER METHODS
	// ===============================================

	/**
	 * Load a template file with variables.
	 *
	 * @param string $template_name Template file name.
	 * @param array $args Variables to extract for template.
	 * @param string $template_path Optional. Template path. Default 'woocommerce'.
	 * @param string $default_path Optional. Default path. Default plugin public/partials.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_template( $template_name, $args = array(), $template_path = 'woocommerce', $default_path = '' ) {
		// Extract variables for template
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args );
		}

		// Set default path
		if ( empty( $default_path ) ) {
			$default_path = plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/';
		}

		// Look in theme first
		$template = locate_template( array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		) );

		// Use plugin template if not found in theme
		if ( ! $template ) {
			$template = $default_path . $template_name;
		}

		// Load template if exists
		if ( file_exists( $template ) ) {
			include $template;
		}
	}
}