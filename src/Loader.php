<?php

namespace WPGraphQL\Extensions\Polylang;

class Loader
{
    private $pll_context_called = false;

    static function init()
    {
        define('WPGRAPHQL_POLYLANG', true);
        (new Loader())->bind_hooks();
    }

    function bind_hooks()
    {
        add_filter('pll_model', [$this, '__filter_pll_model'], 10, 1);
        add_filter('pll_context', [$this, '__filter_pll_context'], 10, 1);
        add_filter(
            'get_user_metadata',
            [$this, '__filter_get_user_metadata'],
            10,
            4
        );
        add_action('graphql_init', [$this, '__action_graphql_init']);
        add_action('admin_notices', [$this, '__action_admin_notices']);
    }

    /**
     * Disable wp-admin language switcher on graphql context
     */
    function __filter_get_user_metadata($data, $object_id, $meta_key, $single)
    {
        if ($meta_key !== 'pll_filter_content') {
            return $data;
        }

        if (!$this->is_graphql_request()) {
            return $data;
        }

        return ['all'];
    }

    function __action_graphql_init()
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

        // ACF Pro and "ACF Options For Polylang" plugins are required for
        // options pages
        // https://wordpress.org/plugins/acf-options-for-polylang/
        if (
            defined('BEA_ACF_OPTIONS_FOR_POLYLANG_VERSION') &&
            function_exists('acf_add_options_page')
        ) {
            OptionsPages::init();
        }

        (new PolylangTypes())->init();
        (new PostObject())->init();
        (new TermObject())->init();
        (new LanguageRootQueries())->init();
        (new MenuItem())->init();
        (new StringsTranslations())->init();
    }

    function __filter_pll_model($class)
    {
        if ($this->is_graphql_request()) {
            return 'PLL_Admin_Model';
        }

        return $class;
    }

    function __filter_pll_context($class)
    {
        $this->pll_context_called = true;

        if ($this->is_graphql_request()) {
            return 'PLL_Admin';
        }

        return $class;
    }

    function __action_admin_notices()
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

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }

    function is_graphql_request()
    {
        // Detect WPGraphQL activation by checking if the main class is defined
        if (!class_exists('WPGraphQL')) {
            return false;
        }

        if (!defined('POLYLANG_VERSION')) {
            return false;
        }

        if (defined('GRAPHQL_POLYLANG_TESTS')) {
            return true;
        }

        return is_graphql_http_request();
    }
}
