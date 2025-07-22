<?php

defined( 'ABSPATH' ) || exit;

/**
 * Template for WordPress.org reviews tab content
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/section-wdevs-wp-plugin-reviews-tab-content.php.
 *
 * @var int $total_count Total number of reviews on WordPress.org
 * @var array $wp_comments Array of WP_Comment objects from WordPress.org
 * @var string $plugin_slug WordPress.org plugin slug
 * @var WC_Product $product Current product object
 */

?>
<div id="wordpress-org-reviews" class="woocommerce-Reviews">
    <div id="reviews">
        <div id="comments">
            <h2 class="woocommerce-Reviews-title">
				<?php esc_html_e( 'Latest reviews from WordPress.org',  'wp-plugin-reviews-for-woocommerce' ); ?>
            </h2>

			<?php if ( ! empty( $plugin_slug ) && $total_count > 0 ) : ?>
                <p class="wordpress-org-link">
                    <a href="https://wordpress.org/support/plugin/<?php echo esc_attr( $plugin_slug ); ?>/reviews"
                       target="_blank">
						<?php 
						/* translators: %d: total number of reviews */
						printf( esc_html__( 'View all %d reviews on WordPress.org',  'wp-plugin-reviews-for-woocommerce' ), $total_count ); 
						?>
                    </a>
                </p>
			<?php endif; ?>

			<?php if ( ! empty( $wp_comments ) ) : ?>
                <ol class="commentlist">
					<?php wp_list_comments(
						apply_filters( 'woocommerce_product_review_list_args', array( 'callback' => 'woocommerce_comments' ) ),
						$wp_comments
					); ?>
                </ol>
			<?php else : ?>
                <p class="woocommerce-noreviews">
					<?php esc_html_e( 'No WordPress.org reviews found for this plugin.',  'wp-plugin-reviews-for-woocommerce' ); ?>
                </p>
			<?php endif; ?>
        </div>
    </div>
    <div class="clear"></div>
</div>
