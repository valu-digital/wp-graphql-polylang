<?php

namespace WPGraphQL\Extensions\Polylang;

use WPGraphQL\Data\Connection\MenuItemConnectionResolver;
use WPGraphQL\Data\Connection\AbstractConnectionResolver;

class MenuItem
{
    function init()
    {
        $this->create_nav_menu_locations();

        add_action(
            'graphql_register_types',
            [$this, '__action_graphql_register_types'],
            10,
            0
        );

        // https://github.com/wp-graphql/wp-graphql/blob/release/v1.26.0/src/Data/Connection/MenuItemConnectionResolver.php#L107
        add_filter(
            'graphql_menu_item_connection_args',
            [$this, '__filter_graphql_menu_item_connection_args'],
            10,
            2
        );
    }

    function __filter_graphql_menu_item_connection_args(
        array $args,
        $unfiltered
    ) {
        if (!isset($args['where']['location'])) {
            return $args;
        }

        $lang = $args['where']['language'] ?? null;

        if (!$lang) {
            return $args;
        }

        // Required only when using other than the default language because the
        // menu location for the default language is the original location
        if (pll_default_language('slug') === $lang) {
            return $args;
        }

        // Ex. TOP_MENU -> TOP_MENU___fi
        $args['where']['location'] .= '___' . $lang;

        return $args;
    }

    /**
     * Nav menu locations are created on admin_init with PLL_Admin but GraphQL
     * requests do not call so we must manually call it
     */
    function create_nav_menu_locations()
    {
        // graphql_init is bit early. Delay to wp_loaded so the nav_menu object is avalable
        add_action(
            'wp_loaded',
            function () {
                global $polylang;

                if (
                    property_exists($polylang, 'nav_menu') &&
                    $polylang->nav_menu
                ) {
                    $polylang->nav_menu->create_nav_menu_locations();
                }
            },
            50
        );
    }

    function __action_graphql_register_types()
    {
        register_graphql_fields('RootQueryToMenuItemConnectionWhereArgs', [
            'language' => [
                'type' => 'LanguageCodeFilterEnum',
                'description' => '',
            ],
        ]);
    }
}
