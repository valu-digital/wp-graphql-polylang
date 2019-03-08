<?php

namespace WPNext;

use WPGraphQL\Types;

/**
 * Integrates Polylang with WPGraphql
 *
 * - Make sure all languages appear in the queries by default
 * - Add lang field
 * - Add lang where arg
 */
class Polylang
{
    public function __construct()
    {
        add_filter(
            'graphql_post_object_connection_query_args',
            [$this, 'add_show_all_languages_query'],
            10,
            1
        );

        add_filter(
            'pll_filter_query_excluded_query_vars',
            [$this, 'handle_show_all_languages_in_pll'],
            3,
            10
        );

        add_action('graphql_register_types', [$this, 'register_types'], 10, 0);
    }

    public function register_types()
    {
        WPGraphQLExtensions::each_post_type(function (
            \WP_Post_Type $post_type_object
        ) {
            $this->add_lang_field($post_type_object);
            $this->add_translations_field($post_type_object);
            $this->add_lang_where_args($post_type_object);
        });
    }

    function add_lang_where_args(\WP_Post_Type $post_type_object)
    {
        $name = ucfirst($post_type_object->graphql_single_name);
        register_graphql_fields("RootQueryTo${name}ConnectionWhereArgs", [
            'lang' => [
                'type' => 'String',
                'description' => 'Filter by post language (polylang)',
            ],
        ]);
    }

    function add_translations_field(\WP_Post_Type $post_type_object)
    {
        register_graphql_field(
            $post_type_object->graphql_single_name,
            'translations',
            [
                'type' => Types::list_of(Types::string()),
                'description' => __('List available translations for this post', 'wpnext'),
                'resolve' => function (\WP_Post $post) {
                    return array_keys(pll_get_post_translations($post->ID));
                },
            ]
        );
    }

    function add_lang_field(\WP_Post_Type $post_type_object)
    {
        register_graphql_field($post_type_object->graphql_single_name, 'lang', [
            'type' => 'String',
            'description' => __('Polylang language', 'wpnext'),
            'resolve' => function (\WP_Post $post) {
                $terms = wp_get_post_terms($post->ID, 'language');

                if (!$terms || count($terms) === 0) {
                    return null;
                }
                if (!$terms[0]->slug) {
                    return null;
                }

                return $terms[0]->slug;
            },
        ]);
    }

    function add_show_all_languages_query($query_args)
    {
        $query_args['show_all_languages_in_graphql'] = true;
        return $query_args;
    }

    /**
     * Handle query var added by graphql in Polylang which causes all languages to be shown
     * in the queries.
     * See https://github.com/polylang/polylang/blob/2ed446f92955cc2c952b944280fce3c18319bd85/include/query.php#L125-L134
     */
    function handle_show_all_languages_in_pll($excludes)
    {
        $excludes[] = 'show_all_languages_in_graphql';
        return $excludes;
    }
}

add_action('init', function () {
    new Polylang();
});
