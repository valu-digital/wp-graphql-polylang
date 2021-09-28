<?php
/**
 * Plugin Name: WP GraphQL Polylang
 * Plugin URI: https://github.com/valu-digital/wp-graphql-polylang
 * Description: Exposes Polylang languages and translations in the GraphQL schema
 * Author: Esa-Matti Suuronen, Valu Digital Oy
 * Version: 0.6.0
 * Author URI: https://valu.fi/
 *
 * @package wp-graphql-polylang
 */

// Use the local autoload if not using project wide autoload
if (!\class_exists('\WPGraphQL\Extensions\Polylang\Loader')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

\WPGraphQL\Extensions\Polylang\Loader::init();
