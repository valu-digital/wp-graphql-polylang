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

function usesSlugBasedField(array $fields)
{
    return isset($fields['code']) ||
        isset($fields['slug']) ||
        isset($fields['id']);
}

// Polylang handles 'lang' query arg so convert our 'language'
// query arg if it is set
function prepare_lang_field(array $query_args)
{
    if (!isset($query_args['language'])) {
        return $query_args;
    }

    $lang = $query_args['language'];
    unset($query_args['language']);

    if ('all' === $lang) {
        // No need to do anything. We show all languages by default
        return $query_args;
    }

    if ('default' === $lang) {
        $lang = pll_default_language('slug');
    }

    $query_args['lang'] = $lang;

    return $query_args;
}

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
