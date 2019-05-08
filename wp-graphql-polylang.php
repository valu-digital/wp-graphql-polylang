<?php
/**
 * Plugin Name: WP GraphQL Polylang
 * Plugin URI: https://github.com/valu-digital/wp-graphql-polylang
 * Description: Exposes Polylang languages and translations in the GraphQL schema
 * Author: Esa-Matti Suuronen, Valu Digital Oy
 * Version: 0.2.1
 * Author URI: https://valu.fi/
 *
 * @package wp-graphql-polylang
 */

namespace WPGraphQL\Extensions\Polylang;

use GraphQL\Error\UserError;

define('WPGRAPHQL_POLYLANG', true);

require_once __DIR__ . '/vendor/autoload.php';

class Loader
{
    private $pll_context_called = false;

    function init()
    {
        add_filter('pll_model', [$this, 'get_pll_model'], 10, 1);
        add_filter('pll_context', [$this, 'get_pll_context'], 10, 1);
        add_action('graphql_init', [$this, 'graphql_polylang_init']);
        add_action('admin_notices', [$this, 'admin_notices']);
    }

    function graphql_polylang_init()
    {
        if (!$this->is_graphql_request()) {
            return;
        }

        if (!$this->pll_context_called) {
            \http_response_code(500);
            $msg =
                'wp-graphql-polylang: You are using too old Polylang version. You must use one that implements pll_context filter https://github.com/polylang/polylang/commit/2203b9e16532797fa530f9b73024b53885d728ef';
            error_log($msg);
            die($msg);
        }

        (new PolylangTypes())->init();
        (new PostObject())->init();
        (new TermObject())->init();
        (new LanguageRootQueries())->init();
        (new MenuItem())->init();
        (new StringsTranslations())->init();
    }

    function get_pll_model($class)
    {
        if ($this->is_graphql_request()) {
            return 'PLL_Admin_Model';
        }

        return $class;
    }

    function get_pll_context($class)
    {
        $this->pll_context_called = true;

        if ($this->is_graphql_request()) {
            return 'PLL_Admin';
        }

        return $class;
    }

    function admin_notices()
    {
        if (!is_super_admin()) {
            return;
        }

        if ($this->pll_context_called) {
            return;
        }

        $class = 'notice notice-error';
        $message = __(
            'wp-graphql-polylang: You are using too old Polylang version. You must upgrade to one with pll_context filter support. See the requirement from the README.',
            'wp-graphql-polylang'
        );

        printf(
            '<div class="%1$s"><p>%2$s</p></div>',
            esc_attr($class),
            esc_html($message)
        );
    }

    function is_graphql_request()
    {
        if (!defined('POLYLANG_VERSION')) {
            return false;
        }

        if (defined('GRAPHQL_POLYLANG_TESTS')) {
            return true;
        }

        if (defined('GRAPHQL_HTTP_REQUEST')) {
            return GRAPHQL_HTTP_REQUEST;
        }

        // XXX GRAPHQL_HTTP_REQUEST is not defined early enough!
        if ('/graphql' == $_SERVER['REQUEST_URI']) {
            return true;
        }

        // Used wp-graphiql
        if ('/index.php?graphql' == $_SERVER['REQUEST_URI']) {
            return true;
        }

        return false;
    }
}

(new Loader())->init();
