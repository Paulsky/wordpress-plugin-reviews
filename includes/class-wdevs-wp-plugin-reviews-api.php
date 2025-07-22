<?php

/**
 * WordPress.org Plugin Reviews API class.
 *
 * @link       https://wijnberg.dev
 * @since      1.0.0
 *
 * @package    Wdevs_WP_Plugin_Reviews
 * @subpackage Wdevs_WP_Plugin_Reviews/includes
 */

/**
 * Modern WordPress.org Plugin Reviews API class.
 *
 * Fetches plugin information and reviews from WordPress.org using
 * the standard plugins_api() function with modern HTML parsing.
 *
 * @package    Wdevs_WP_Plugin_Reviews
 * @subpackage Wdevs_WP_Plugin_Reviews/includes
 * @author     Wijnberg Developments <contact@wijnberg.dev>
 */
class Wdevs_WP_Plugin_Reviews_Api {

	/**
	 * Cache group for wp_cache functions.
	 */
	const CACHE_GROUP = 'wdevs_wppr_api';

	/**
	 * Default cache duration for transients (24 hours).
	 */
	const DEFAULT_CACHE_DURATION = DAY_IN_SECONDS;

	/**
	 * Short cache duration for lightweight data (1 hour).
	 */
	const SHORT_CACHE_DURATION = HOUR_IN_SECONDS;

	/**
	 * Plugin slug to fetch reviews for.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	private $plugin_slug;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string $plugin_slug Plugin slug from WordPress.org.
	 */
	public function __construct( $plugin_slug ) {
		$this->plugin_slug = sanitize_text_field( $plugin_slug );
	}

	/**
	 * Get plugin information from WordPress.org (only reviews).
	 *
	 * @since 1.0.0
	 * @access private
	 * @return object|WP_Error Plugin information object or WP_Error on failure.
	 */
	private function get_plugin_information() {
		// Load plugin install functions if not available
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		// Fetch only reviews section
		$plugin_info = plugins_api(
			'plugin_information',
			array(
				'slug'   => $this->plugin_slug,
				'fields' => array(
					'reviews' => true,
					'sections' => array( 'reviews' => true ),
				),
			)
		);

		return $plugin_info;
	}

	/**
	 * Get processed reviews array.
	 *
	 * @since 1.0.0
	 * @return array|WP_Error Array of processed reviews or WP_Error on failure.
	 */
	public function get_reviews() {
		$cache_key = "wdevs_wppr_reviews_{$this->plugin_slug}";
		$transient_key = "wdevs_wppr_reviews_{$this->plugin_slug}";

		// Try dual-layer cache
		$cached_reviews = $this->get_cached_data( $cache_key, $transient_key );
		if ( false !== $cached_reviews ) {
			return $cached_reviews;
		}

		// Fetch fresh from API
		$plugin_info = $this->get_plugin_information();

		if ( is_wp_error( $plugin_info ) ) {
			return $plugin_info;
		}

		$reviews = array();

		// Extract and process reviews
		if ( isset( $plugin_info->sections['reviews'] ) && ! empty( $plugin_info->sections['reviews'] ) ) {
			$reviews = $this->parse_reviews_html( $plugin_info->sections['reviews'] );

			// Cache in both tiers
			if ( ! empty( $reviews ) ) {
				$this->set_cached_data( $cache_key, $transient_key, $reviews );
			}
		}

		return $reviews;
	}

	/**
	 * Parse reviews HTML into structured array.
	 *
	 * @since 1.0.0
	 * @param string $reviews_html HTML string containing reviews.
	 * @return array Processed reviews array.
	 */
	private function parse_reviews_html( $reviews_html ) {
		if ( empty( $reviews_html ) ) {
			return array();
		}

		$reviews = array();
		
		// Create DOMDocument without the hacky XML encoding prefix
		$dom = new DOMDocument();
		
		// Suppress warnings for malformed HTML and use UTF-8 encoding
		libxml_use_internal_errors( true );
		$dom->loadHTML( mb_convert_encoding( $reviews_html, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );
		
		// Find all review elements using proven WordPress.org structure
		$review_nodes = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' review ')]" );

		foreach ( $review_nodes as $review_node ) {
			$review_data = $this->extract_review_data( $review_node, $xpath );
			
			if ( ! empty( $review_data ) ) {
				$reviews[] = $review_data;
			}
		}

		return $reviews;
	}

	/**
	 * Extract data from a single review node.
	 *
	 * @since 1.0.0
	 * @param DOMElement $review_node Review DOM element.
	 * @param DOMXPath   $xpath       XPath object for queries.
	 * @return array Review data array.
	 */
	private function extract_review_data( $review_node, $xpath ) {
		$review_data = array();

		// Debug: dump complete HTML of this review node
		$review_data['debug_html'] = $review_node->ownerDocument->saveHTML( $review_node );

		// Extract username
		$username_nodes = $xpath->query( ".//a[contains(@class, 'reviewer-name')]", $review_node );
		if ( $username_nodes->length > 0 ) {
			$review_data['username'] = array(
				'text' => trim( $username_nodes->item( 0 )->textContent ),
				'href' => $username_nodes->item( 0 )->getAttribute( 'href' ),
			);
		}

		// Extract avatar
		//<p class="reviewer">
		//Door <a href="https://profiles.wordpress.org/williedgarcia/"><img alt="" src="https://secure.gravatar.com/avatar/0fa87555931fffbebf02902d2eff36fc0bd098081f6303fbb039cabdb99af724?s=16&d=monsterid&r=g" srcset="https://secure.gravatar.com/avatar/0fa87555931fffbebf02902d2eff36fc0bd098081f6303fbb039cabdb99af724?s=32&d=monsterid&r=g 2x" class="avatar avatar-16 photo" height="16" width="16" loading="lazy" decoding="async"></a><a href="https://profiles.wordpress.org/williedgarcia/" class="reviewer-name">williedgarcia</a> op <span class="review-date">juli 21, 2025</span>			</p>
		$avatar_nodes = $xpath->query( ".//img[contains(@class, 'avatar')]", $review_node );
		if ( $avatar_nodes->length > 0 ) {
			$review_data['avatar'] = array(
				'src' => $avatar_nodes->item( 0 )->getAttribute( 'src' ),
				'alt' => $avatar_nodes->item( 0 )->getAttribute( 'alt' ),
			);
		}

		// Extract rating
		$rating_nodes = $xpath->query( ".//*[contains(@class, 'wporg-ratings')]", $review_node );
		if ( $rating_nodes->length > 0 ) {
			$review_data['rating'] = (int) $rating_nodes->item( 0 )->getAttribute( 'data-rating' );
		}

		// Extract review title
		$title_nodes = $xpath->query( ".//h4", $review_node );
		if ( $title_nodes->length > 0 ) {
			$review_data['title'] = trim( $title_nodes->item( 0 )->textContent );
		}

		// Extract review content
		$content_nodes = $xpath->query( ".//*[contains(@class, 'review-body')]", $review_node );
		if ( $content_nodes->length > 0 ) {
			$review_data['content'] = trim( $content_nodes->item( 0 )->textContent );
		}

		// Extract review date
		$date_nodes = $xpath->query( ".//*[contains(@class, 'review-date')]", $review_node );
		if ( $date_nodes->length > 0 ) {
			$review_data['date'] = trim( $date_nodes->item( 0 )->textContent );
		}

		// Generate unique numeric ID
		$review_data['id'] = $this->generate_review_id( $review_data );

		return $review_data;
	}

	/**
	 * Generate unique numeric ID for a review with fallback strategy.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array $review_data Review data array.
	 * @return int Unique numeric ID.
	 */
	private function generate_review_id( $review_data ) {
		$id_parts = array();
		
		// Primary identifiers
		if ( isset( $review_data['username']['text'] ) && ! empty( $review_data['username']['text'] ) ) {
			$id_parts[] = $review_data['username']['text'];
		}
		
		if ( isset( $review_data['title'] ) && ! empty( $review_data['title'] ) ) {
			$id_parts[] = $review_data['title'];
		}
		
		if ( isset( $review_data['date'] ) && ! empty( $review_data['date'] ) ) {
			$id_parts[] = $review_data['date'];
		}
		
		// Fallback: if we have less than 2 parts, add content snippet
		if ( count( $id_parts ) < 2 && isset( $review_data['content'] ) && ! empty( $review_data['content'] ) ) {
			$id_parts[] = substr( $review_data['content'], 0, 50 ); // First 50 chars
		}
		
		// Ultimate fallback: if still not enough, add full content hash
		if ( count( $id_parts ) < 1 ) {
			// No usable data found, return null for incomplete review
			return null;
		}
		
		// Generate consistent numeric ID using more robust hash
		$id_string = implode( '|', $id_parts );
		$hash = hash( 'sha256', $id_string );
		
		// Convert first 8 chars of hash to integer to ensure uniqueness
		return hexdec( substr( $hash, 0, 8 ) );
	}


	/**
	 * Get data from dual-layer cache (object cache + transient).
	 *
	 * @since 1.0.0
	 * @param string $cache_key      Object cache key.
	 * @param string $transient_key  Transient key.
	 * @param int    $cache_duration Cache duration in seconds.
	 * @return mixed|false Cached data or false if not found.
	 */
	private function get_cached_data( $cache_key, $transient_key, $cache_duration = null ) {
		if ( null === $cache_duration ) {
			$cache_duration = $this->get_cache_duration();
		}
		// First: try object cache
		$cached_data = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached_data ) {
			return $cached_data;
		}

		// Second: try transient
		$cached_data = get_transient( $transient_key );
		if ( false !== $cached_data ) {
			wp_cache_set( $cache_key, $cached_data, self::CACHE_GROUP, $cache_duration );
			return $cached_data;
		}

		return false;
	}

	/**
	 * Store data in dual-layer cache (object cache + transient).
	 *
	 * @since 1.0.0
	 * @param string $cache_key      Object cache key.
	 * @param string $transient_key  Transient key.
	 * @param mixed  $data           Data to cache.
	 * @param int    $cache_duration Cache duration in seconds.
	 * @return bool True if data was cached successfully.
	 */
	private function set_cached_data( $cache_key, $transient_key, $data, $cache_duration = null ) {
		if ( null === $cache_duration ) {
			$cache_duration = $this->get_cache_duration();
		}
		wp_cache_set( $cache_key, $data, self::CACHE_GROUP, $cache_duration );
		return set_transient( $transient_key, $data, $cache_duration );
	}

	/**
	 * Get cache duration from settings.
	 *
	 * @since 1.0.0
	 * @return int Cache duration in seconds.
	 */
	private function get_cache_duration() {
		$hours = get_option( 'wdevs_wppr_cache_duration', 24 );
		return absint( $hours ) * HOUR_IN_SECONDS;
	}

	/**
	 * Clear cached reviews for this plugin.
	 *
	 * @since 1.0.0
	 * @return bool True if cache was cleared successfully.
	 */
	public function clear_cache() {
		// Clear object cache
		wp_cache_delete( "wdevs_wppr_reviews_{$this->plugin_slug}", self::CACHE_GROUP );
		wp_cache_delete( "wdevs_wppr_count_{$this->plugin_slug}", self::CACHE_GROUP );
		wp_cache_delete( "wdevs_wppr_rating_{$this->plugin_slug}", self::CACHE_GROUP );
		
		// Clear transients
		delete_transient( "wdevs_wppr_reviews_{$this->plugin_slug}" );
		delete_transient( "wdevs_wppr_count_{$this->plugin_slug}" );
		delete_transient( "wdevs_wppr_rating_{$this->plugin_slug}" );
		
		return true;
	}

	/**
	 * Get total review count from WordPress.org using plugins_api.
	 *
	 * @since 1.0.0
	 * @return int|WP_Error Total number of reviews or WP_Error on failure.
	 */
	public function get_total_review_count() {
		$cache_key = "wdevs_wppr_count_{$this->plugin_slug}";
		$transient_key = "wdevs_wppr_count_{$this->plugin_slug}";

		// Try dual-layer cache
		$cached_count = $this->get_cached_data( $cache_key, $transient_key, self::SHORT_CACHE_DURATION );
		if ( false !== $cached_count ) {
			return absint( $cached_count );
		}

		// Fetch fresh from API
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$plugin_info = plugins_api(
			'plugin_information',
			array(
				'slug'   => $this->plugin_slug,
				'fields' => array(
					'num_ratings' => true,
					'ratings'     => true,
					'sections'    => false,
					'reviews'     => false,
				),
			)
		);

		if ( is_wp_error( $plugin_info ) ) {
			return $plugin_info;
		}

		$total_count = 0;

		if ( isset( $plugin_info->num_ratings ) && is_numeric( $plugin_info->num_ratings ) ) {
			$total_count = absint( $plugin_info->num_ratings );
		} elseif ( isset( $plugin_info->ratings ) && is_array( $plugin_info->ratings ) ) {
			$total_count = array_sum( $plugin_info->ratings );
		}

		// Cache in both tiers
		if ( $total_count > 0 ) {
			$this->set_cached_data( $cache_key, $transient_key, $total_count, self::SHORT_CACHE_DURATION );
		}

		return $total_count;
	}

	/**
	 * Get plugin rating information from WordPress.org.
	 *
	 * @since 1.0.0
	 * @return array|WP_Error Array with rating data or WP_Error on failure.
	 */
	public function get_rating_info() {
		$cache_key = "wdevs_wppr_rating_{$this->plugin_slug}";
		$transient_key = "wdevs_wppr_rating_{$this->plugin_slug}";

		// Try dual-layer cache
		$cached_rating = $this->get_cached_data( $cache_key, $transient_key, self::SHORT_CACHE_DURATION );
		if ( false !== $cached_rating ) {
			return $cached_rating;
		}

		// Fetch fresh from API
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$plugin_info = plugins_api(
			'plugin_information',
			array(
				'slug'   => $this->plugin_slug,
				'fields' => array(
					'rating'      => true,
					'num_ratings' => true,
					'ratings'     => true,
					'sections'    => false,
					'reviews'     => false,
				),
			)
		);

		if ( is_wp_error( $plugin_info ) ) {
			return $plugin_info;
		}

		$rating_data = array(
			'average_rating' => isset( $plugin_info->rating ) ? floatval( $plugin_info->rating ) : 0,
			'total_ratings'  => isset( $plugin_info->num_ratings ) ? absint( $plugin_info->num_ratings ) : 0,
			'ratings'        => isset( $plugin_info->ratings ) && is_array( $plugin_info->ratings ) ? $plugin_info->ratings : array(),
		);

		if ( $rating_data['total_ratings'] === 0 && ! empty( $rating_data['ratings'] ) ) {
			$rating_data['total_ratings'] = array_sum( $rating_data['ratings'] );
		}

		// Cache in both tiers
		if ( $rating_data['total_ratings'] > 0 ) {
			$this->set_cached_data( $cache_key, $transient_key, $rating_data, self::SHORT_CACHE_DURATION );
		}

		return $rating_data;
	}

	/**
	 * Get the plugin slug.
	 *
	 * @since 1.0.0
	 * @return string Plugin slug.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

}