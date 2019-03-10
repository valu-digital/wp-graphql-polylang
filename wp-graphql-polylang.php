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

if (!function_exists('pll_get_post_language')) {
    return;
}

if (!function_exists('register_graphql_field')) {
    return;
}

function usesSlugBasedField(array $fields)
{
    return isset($fields['code']) ||
        isset($fields['slug']) ||
        isset($fields['id']);
}

require_once __DIR__ . '/src/PolylangTypes.php';
require_once __DIR__ . '/src/LanguageRootQueries.php';
require_once __DIR__ . '/src/PostObject.php';
require_once __DIR__ . '/src/StringsTranslations.php';
require_once __DIR__ . '/src/TermObject.php';

add_action('init', function () {
    new PolylangTypes();
    new PostObject();
    new TermObject();
    new LanguageRootQueries();
    new StringsTranslations();
});
