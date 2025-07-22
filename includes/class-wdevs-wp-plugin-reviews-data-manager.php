<?php

/**
 * Centralized data manager for WordPress.org reviews with request-level caching.
 *
 * @link       https://wijnberg.dev
 * @since      1.0.0
 *
 * @package    Wdevs_WP_Plugin_Reviews
 * @subpackage Wdevs_WP_Plugin_Reviews/includes
 */

/**
 * Data manager for efficient WordPress.org reviews handling.
 *
 * Implements singleton pattern with request-level caching to minimize
 * API calls and database queries for the same product.
 *
 * @package    Wdevs_WP_Plugin_Reviews
 * @subpackage Wdevs_WP_Plugin_Reviews/includes
 * @author     Wijnberg Developments <contact@wijnberg.dev>
 */
class Wdevs_WP_Plugin_Reviews_Data_Manager {

	/**
	 * Cache group for wp_cache functions.
	 */
	const CACHE_GROUP = 'wdevs_wppr';

	/**
	 * Cache duration for transients (24 hours).
	 */
	const CACHE_DURATION = DAY_IN_SECONDS;

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var   Wdevs_WP_Plugin_Reviews_Data_Manager
	 */
	private static $instance = null;

	/**
	 * API instances cache per plugin slug.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	private $api_instances = array();

	/**
	 * Product data cache per product ID.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	private $product_data = array();

	/**
	 * WooCommerce ratings cache per product ID.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	private $wc_ratings_cache = array();

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Load API class if needed
		if ( ! class_exists( 'Wdevs_WP_Plugin_Reviews_Api' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wdevs-wp-plugin-reviews-api.php';
		}
	}

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return Wdevs_WP_Plugin_Reviews_Data_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get all WordPress.org data for a product.
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 * @return array|false Product data array or false if no plugin slug.
	 */
	public function get_product_data( $product_id ) {
		// Return in-memory cached data if available
		if ( isset( $this->product_data[ $product_id ] ) ) {
			return $this->product_data[ $product_id ];
		}

		// Get plugin slug
		$plugin_slug = get_post_meta( $product_id, '_wdevs_wppr_plugin_slug', true );
		
		if ( empty( $plugin_slug ) ) {
			$this->product_data[ $product_id ] = false;
			return false;
		}

		// Fetch fresh data from API (API handles its own caching)
		$api = $this->get_api_instance( $plugin_slug );

		$reviews = $api->get_reviews();
		$total_count = $api->get_total_review_count();
		$rating_info = $api->get_rating_info();

		// Convert reviews to WP_Comment objects
		$wp_comments = array();
		$comment_ratings = array();
		
		if ( ! is_wp_error( $reviews ) && ! empty( $reviews ) && is_array( $reviews ) ) {
			foreach ( $reviews as $review ) {
				$wp_comment = $this->convert_review_to_comment( $review, $product_id );
				if ( $wp_comment ) {
					$wp_comments[] = $wp_comment;
					if ( ! empty( $review['rating'] ) ) {
						$comment_ratings[ $wp_comment->comment_ID ] = (int) $review['rating'];
					}
				}
			}
		}

		// Prepare final data
		$product_data = array(
			'plugin_slug'     => $plugin_slug,
			'reviews'         => $reviews,
			'wp_comments'     => $wp_comments,
			'comment_ratings' => $comment_ratings,
			'total_count'     => is_wp_error( $total_count ) ? 0 : $total_count,
			'rating_info'     => is_wp_error( $rating_info ) ? array() : $rating_info,
			'has_data'        => ! is_wp_error( $reviews ) && ! empty( $reviews ),
		);

		// Store in memory for this request
		$this->product_data[ $product_id ] = $product_data;

		return $product_data;
	}

	/**
	 * Get WooCommerce ratings data for a product.
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 * @return array WooCommerce ratings data.
	 */
	public function get_wc_ratings_data( $product_id ) {
		// Return in-memory cached data if available
		if ( isset( $this->wc_ratings_cache[ $product_id ] ) ) {
			return $this->wc_ratings_cache[ $product_id ];
		}

		// Fetch from database
		global $wpdb;

		$results = $wpdb->get_row( $wpdb->prepare(
			"SELECT 
				SUM(meta_value) as ratings_sum,
				COUNT(*) as ratings_count
			FROM {$wpdb->commentmeta}
			LEFT JOIN {$wpdb->comments} ON {$wpdb->commentmeta}.comment_id = {$wpdb->comments}.comment_ID
			WHERE meta_key = 'rating'
			AND comment_post_ID = %d
			AND comment_approved = '1'
			AND meta_value > 0",
			$product_id
		) );

		$ratings_data = array(
			'sum'   => $results ? (float) $results->ratings_sum : 0,
			'count' => $results ? (int) $results->ratings_count : 0,
		);

		// Store in memory for this request
		$this->wc_ratings_cache[ $product_id ] = $ratings_data;

		return $ratings_data;
	}

	/**
	 * Get API instance for plugin slug.
	 *
	 * @since 1.0.0
	 * @param string $plugin_slug Plugin slug.
	 * @return Wdevs_WP_Plugin_Reviews_Api
	 */
	private function get_api_instance( $plugin_slug ) {
		if ( ! isset( $this->api_instances[ $plugin_slug ] ) ) {
			$this->api_instances[ $plugin_slug ] = new Wdevs_WP_Plugin_Reviews_Api( $plugin_slug );
		}
		return $this->api_instances[ $plugin_slug ];
	}

	/**
	 * Convert WordPress.org review to WP_Comment object.
	 *
	 * @since 1.0.0
	 * @param array $review   Review data from WordPress.org.
	 * @param int   $post_id  Product post ID.
	 * @return WP_Comment|null WP_Comment object or null on failure.
	 */
	private function convert_review_to_comment( $review, $post_id ) {
		// Validate required data
		if ( empty( $review ) || ! is_array( $review ) || empty( $review['id'] ) || empty( $post_id ) ) {
			return null;
		}

		// Validate ID and post_id are valid integers
		$comment_id = absint( $review['id'] );
		$post_id = absint( $post_id );
		
		if ( empty( $comment_id ) || empty( $post_id ) ) {
			return null;
		}

		// Parse date
		$comment_date = $this->parse_review_date( $review['date'] ?? '' );

		// Sanitize and prepare content with proper formatting
		$content_parts = array();
		
		// Add title with span wrapper if present
		if ( ! empty( $review['title'] ) ) {
			$sanitized_title = sanitize_text_field( $review['title'] );
			$content_parts[] = '<span class="wporg-review-title">' . esc_html( $sanitized_title ) . '</span>';
		}
		
		// Add review content
		if ( ! empty( $review['content'] ) ) {
			$content_parts[] = wp_kses_post( $review['content'] );
		}
		
		$comment_content = implode( "\n\n", $content_parts );

		// Create comment data object with proper sanitization
		$comment_data = (object) array(
			'comment_ID'           => (string) $comment_id,
			'comment_post_ID'      => (string) $post_id,
			'comment_author'       => ! empty( $review['username']['text'] ) ? sanitize_text_field( $review['username']['text'] ) : '',
			'comment_author_email' => '',
			'comment_author_url'   => ! empty( $review['username']['href'] ) ? esc_url_raw( $review['username']['href'] ) : '',
			'comment_author_IP'    => '',
			'comment_date'         => $comment_date,
			'comment_date_gmt'     => get_gmt_from_date( $comment_date ),
			'comment_content'      => $comment_content,
			'comment_karma'        => '',
			'comment_approved'     => '1',
			'comment_agent'        => '',
			'comment_type'         => 'review',
			'comment_parent'       => '0',
			'user_id'              => '0',
		);

		$comment = new WP_Comment( $comment_data );

		// Add avatar data if available (with URL sanitization)
		if ( ! empty( $review['avatar'] ) && ! empty( $review['avatar']['src'] ) ) {
			$avatar_src = esc_url_raw( $review['avatar']['src'] );
			$avatar_alt = ! empty( $review['avatar']['alt'] ) ? sanitize_text_field( $review['avatar']['alt'] ) : '';
			
			if ( ! empty( $avatar_src ) ) {
				$comment->wdevs_wppr_avatar = array(
					'src' => $avatar_src,
					'alt' => $avatar_alt,
				);
			}
		}

		return $comment;
	}

	/**
	 * Parse review date from WordPress.org format using user locale.
	 *
	 * @since 1.0.0
	 * @param string $date_string Date string from WordPress.org.
	 * @return string MySQL datetime format.
	 */
	private function parse_review_date( $date_string ) {
		if ( empty( $date_string ) ) {
			return current_time( 'mysql' );
		}

		// WordPress.org API uses get_user_locale() for date formatting
		$user_locale = get_user_locale();
		
		// Try IntlDateFormatter first (handles localized dates)
		if ( class_exists( 'IntlDateFormatter' ) ) {
			$formatter = new IntlDateFormatter(
				$user_locale,
				IntlDateFormatter::LONG,
				IntlDateFormatter::NONE
			);
			
			$timestamp = $formatter->parse( $date_string );
			
			if ( false !== $timestamp ) {
				return date( 'Y-m-d H:i:s', $timestamp );
			}
		}
		
		// Fallback to strtotime for English and basic formats
		$timestamp = strtotime( $date_string );
		
		if ( false === $timestamp ) {
			return current_time( 'mysql' );
		}

		// Return MySQL format - WordPress will handle display formatting via mysql2date()
		return date( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Clear cache for a specific product.
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 */
	public function clear_product_cache( $product_id ) {
		// Clear in-memory cache
		unset( $this->product_data[ $product_id ] );
		unset( $this->wc_ratings_cache[ $product_id ] );
		
		// Clear API cache for this product's plugin slug
		$plugin_slug = get_post_meta( $product_id, '_wdevs_wppr_plugin_slug', true );
		if ( ! empty( $plugin_slug ) && isset( $this->api_instances[ $plugin_slug ] ) ) {
			$this->api_instances[ $plugin_slug ]->clear_cache();
		}
	}

	/**
	 * Get all comment ratings from cached products.
	 *
	 * @since 1.0.0
	 * @return array Array of comment_id => rating.
	 */
	public function get_comment_ratings() {
		$all_ratings = array();
		
		foreach ( $this->product_data as $product_data ) {
			if ( ! empty( $product_data['comment_ratings'] ) ) {
				$all_ratings = array_merge( $all_ratings, $product_data['comment_ratings'] );
			}
		}
		
		return $all_ratings;
	}

	/**
	 * Clear all cache.
	 *
	 * @since 1.0.0
	 */
	public function clear_all_cache() {
		// Clear in-memory cache
		$this->product_data = array();
		$this->wc_ratings_cache = array();
		
		// Clear all API instances cache
		foreach ( $this->api_instances as $api ) {
			$api->clear_cache();
		}
		$this->api_instances = array();
	}
}