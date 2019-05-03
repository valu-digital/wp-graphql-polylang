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

define('WPGRAPHQL_POLYLANG', true);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/PolylangTypes.php';
require_once __DIR__ . '/src/LanguageRootQueries.php';
require_once __DIR__ . '/src/PostObject.php';
require_once __DIR__ . '/src/StringsTranslations.php';
require_once __DIR__ . '/src/TermObject.php';
require_once __DIR__ . '/src/MenuItem.php';

function isGraphqlContext()
{
    if (\class_exists('\Codeception\TestCase\WPTestCase')) {
        return true;
    }

    if ('/graphql' == $_SERVER['REQUEST_URI']) {
        return true;
    }

    return false;
}

add_filter(
    'pll_model',
    function ($class) {
        if (isGraphqlContext()) {
            return 'PLL_Admin_Model';
        }

        return $class;
    },
    10,
    1
);

add_filter(
    'pll_context',
    function ($class) {
        if (isGraphqlContext()) {
            return 'PLL_Admin';
        }

        return $class;
    },
    10,
    1
);

add_action('graphql_init', function () {
    if (!function_exists('pll_get_post_language')) {
        return;
    }

    (new PolylangTypes())->init();
    (new PostObject())->init();
    (new TermObject())->init();
    (new LanguageRootQueries())->init();
    (new MenuItem())->init();
    (new StringsTranslations())->init();
});
