# WP Plugin Reviews for WooCommerce

Display WordPress.org plugin reviews directly in your WooCommerce product pages to increase trust and boost conversions.

## Description

**WP Plugin Reviews for WooCommerce** seamlessly integrates WordPress.org plugin reviews into your WooCommerce store, allowing you to showcase authentic community feedback alongside your product information. Perfect for developers selling WordPress plugins through WooCommerce.

### Key Features

- **Automatic Review Integration**: Fetches and displays real reviews from WordPress.org
- **Combined Rating System**: Merges WordPress.org ratings with WooCommerce reviews for comprehensive scoring
- **Separate Review Tabs**: Shows WordPress.org reviews in a dedicated product tab
- **Smart Caching**: Built-in dual-layer caching system for optimal performance
- **Customizable Display**: Template override support for complete design control
- **WooCommerce Integration**: Native integration with WooCommerce ratings and review system
- **Multi-language Support**: Translation-ready with proper internationalization

### How It Works

1. **Add Plugin Slug**: Enter the WordPress.org plugin slug in your WooCommerce product settings
2. **Automatic Fetching**: The plugin automatically retrieves reviews and rating data
3. **Seamless Display**: Reviews appear in a new product tab with proper formatting
4. **Combined Ratings**: Product ratings include both WooCommerce and WordPress.org reviews
5. **Performance Optimized**: Smart caching ensures fast loading times

## Installation

### Automatic Installation

1. Go to your WordPress admin dashboard
2. Navigate to **Plugins → Add New**
3. Search for "WP Plugin Reviews for WooCommerce"
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin files
2. Upload the `wdevs-wp-plugin-reviews` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### Requirements

- WordPress 5.0 or higher
- WooCommerce 7.0 or higher
- PHP 7.4 or higher

## Configuration

### WooCommerce Settings

1. Navigate to **WooCommerce → Settings → WooCommerce WP Plugin Reviews**
2. Configure cache duration (default: 24 hours)
3. Use "Clear All Caches" to refresh data immediately

### Product Setup

For each product representing a WordPress plugin:

1. Edit the product in WooCommerce
2. In the **General** tab, find **WordPress.org Plugin Slug**
3. Enter the plugin slug (e.g., "woocommerce" for wordpress.org/plugins/woocommerce/)
4. Save the product

## Features in Detail

### Review Integration

The plugin fetches reviews from WordPress.org and presents them as:
- **Formatted Comments**: Reviews appear as standard WooCommerce comments
- **Rating Stars**: Individual review ratings are preserved and displayed
- **Author Information**: Username and avatar from WordPress.org profiles
- **Review Content**: Full review text with proper formatting

### Rating System

- **Combined Counts**: Total review count includes WordPress.org reviews
- **Average Rating**: Calculates weighted average across all reviews
- **Rating Distribution**: Individual star ratings (1-5 stars) are merged
- **Separate Display**: WordPress.org reviews shown in dedicated tab

### Performance Features

- **Dual-Layer Caching**: Object cache + transients for maximum speed
- **Smart API Usage**: Minimal WordPress.org API calls
- **Request-Level Caching**: Prevents duplicate API calls during single page load
- **Configurable Cache Duration**: Customize refresh frequency

### Developer Features

- **Template Override**: Copy template to theme for customization
- **Hook System**: Comprehensive WordPress action/filter integration
- **Singleton Pattern**: Efficient resource management
- **Error Handling**: Graceful fallbacks for API failures

## Customization

### Template Override

To customize the display:

1. Copy `section-wdevs-wp-plugin-reviews-tab-content.php` from the plugin
2. Place in your theme: `yourtheme/woocommerce/section-wdevs-wp-plugin-reviews-tab-content.php`
3. Modify as needed

### Available Hooks

#### Actions
- `before_woocommerce_init` - WooCommerce compatibility
- `woocommerce_product_options_general_product_data` - Add plugin slug field
- `woocommerce_process_product_meta` - Save plugin slug
- `woocommerce_settings_tabs_wdevs_wppr` - Settings tab content

#### Filters
- `woocommerce_product_get_review_count` - Modify review count
- `woocommerce_product_get_average_rating` - Modify average rating
- `woocommerce_product_get_rating_counts` - Modify rating distribution
- `woocommerce_product_tabs` - Add WordPress.org reviews tab
- `woocommerce_reviews_title` - Adjust review section title
- `get_comment_metadata` - Handle rating metadata
- `get_avatar` - Custom avatars for WordPress.org users

## Technical Architecture

### Class Structure

- **Main Plugin Class** (`Wdevs_WP_Plugin_Reviews`): Core orchestrator
- **WooCommerce Integration** (`Wdevs_WP_Plugin_Reviews_Woocommerce`): All WooCommerce hooks
- **Data Manager** (`Wdevs_WP_Plugin_Reviews_Data_Manager`): Centralized data handling
- **API Handler** (`Wdevs_WP_Plugin_Reviews_Api`): WordPress.org API communication
- **Hook Loader** (`Wdevs_WP_Plugin_Reviews_Loader`): WordPress hook management

### Data Flow

1. **Product Display**: Check for plugin slug in product meta
2. **Data Retrieval**: Data Manager coordinates API calls and caching
3. **API Communication**: Fetch reviews using WordPress plugins_api()
4. **Data Processing**: Convert to WP_Comment objects with ratings
5. **Display Integration**: Merge with WooCommerce review system
6. **Caching**: Store processed data for performance

### Security Features

- **Input Sanitization**: All user inputs properly sanitized
- **Output Escaping**: All output properly escaped
- **Nonce Verification**: CSRF protection on form submissions
- **Capability Checks**: Proper permission validation
- **SQL Injection Prevention**: Prepared statements used throughout

## Troubleshooting

### Common Issues

**Reviews not appearing?**
- Verify the plugin slug is correct
- Check if the plugin exists on WordPress.org
- Clear cache in WooCommerce settings

**Performance issues?**
- Reduce cache duration for fresh data
- Enable object caching on your server
- Check server memory limits

**Rating counts incorrect?**
- Clear all caches to refresh data
- Verify WooCommerce reviews are working properly
- Check for plugin conflicts

### Debug Information

Enable WordPress debug mode to see detailed error information:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

## Changelog

### Version 1.0.0
- Initial release
- WordPress.org review integration
- Combined rating system
- Dual-layer caching
- WooCommerce settings integration
- Template override support
- Multi-language support

## Support

For support, feature requests, or bug reports:

- **Documentation**: [https://products.wijnberg.dev/product/wordpress/plugins/wp-plugin-reviews-for-woocommerce/](https://products.wijnberg.dev/product/wordpress/plugins/wp-plugin-reviews-for-woocommerce/)
- **Developer**: [Wijnberg Developments](https://wijnberg.dev)
- **Email**: contact@wijnberg.dev

## License

This plugin is licensed under the GPL v2.0 or later.

```
Copyright (C) 2024 Wijnberg Developments

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## Credits

Developed by [Wijnberg Developments](https://wijnberg.dev) - Your trusted WordPress & WooCommerce plugin partner from the Netherlands.

---

**Note**: This plugin requires both WordPress and WooCommerce to be installed and activated. It's specifically designed for developers selling WordPress plugins through WooCommerce.