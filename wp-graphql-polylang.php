<?php
/**
 * Plugin Name: WP GraphQL Polylang
 * Plugin URI: https://github.com/valu-digital/wp-graphql-polylang
 * Description: Exposes Polylang languages and translations in the GraphQL schema
 * Author: Esa-Matti Suuronen, Valu Digital Oy
 * Version: 0.1.0
 * Author URI: https://valu.fi/
 *
 * @package wp-graphql-polylang
 */

namespace WPGraphQL\Extensions\Polylang;

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/PolylangTypes.php';
require_once __DIR__ . '/src/LanguageRootQueries.php';
require_once __DIR__ . '/src/PostObject.php';
require_once __DIR__ . '/src/StringsTranslations.php';
require_once __DIR__ . '/src/TermObject.php';

add_action('init', function () {
    if (!function_exists('pll_get_post_language')) {
        return;
    }

    if (!function_exists('register_graphql_field')) {
        return;
    }
    new PolylangTypes();
    new PostObject();
    new TermObject();
    new LanguageRootQueries();
    new StringsTranslations();
});


/**
 * Force Polylang Admin mode for GraphQL requests. Polylang defaults to the
 * frontend mode which is not good for us becaus it adds implicit language
 * filterin by the current language which is a concept that does not exists in
 * the graphql api.
 *
 * The REST Request mode would be probably better or even a custom mode but
 * Polylang does not have an API for customizing it here:
 *
 * https://github.com/polylang/polylang/blob/7115d32e21e4441ce199b632d577ef9f074b3e34/include/class-polylang.php#L201-L209
 *
 * I hope we can get better solution in future:
 *
 * https://github.com/polylang/polylang/pull/340
 */
add_action( 'plugins_loaded', function () {
    if ('/graphql' == $_SERVER['REQUEST_URI']) {
        define( 'PLL_ADMIN', true );
    }
}, -1 ); // Use very high priority to set this before polylang does
