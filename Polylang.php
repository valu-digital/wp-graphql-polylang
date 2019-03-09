<?php

namespace WPNext;

use WPGraphQL\Types;

/**
 * Integrates Polylang with WPGraphql
 *
 * - Make sure all languages appear in the queries by default
 * - Add lang where arg
 * - Add lang field
 * - Add translation fields fields:
 *   - translations: Get available translations
 *   - translation: Get specific translated version of the post
 *   - translationObjects: Get all translated objects
 */
class Polylang
{
    private $languageFields = null;

    public function __construct()
    {
        // $this->languageFields = new ObjectType([
        //     'name' => 'Polylang language fields',
        //     'description' => 'WPNext Post type info extension',
        //     'fields' => [
        //         'name' => [
        //             'type' => Type::string(),
        //             'description' => 'Human readable language name',
        //         ],
        //         'locale' => [
        //             'type' => Type::string(),
        //             'description' => 'The language locale',
        //         ],
        //         'code' => [
        //             'type' => 'LanguagesEnum',
        //             'description' => 'The language code',
        //         ],
        //     ],
        // ]);

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

        add_action('graphql_register_types', [$this, 'register_fields'], 10, 0);
    }

    function register_types()
    {
        $values = [];

        foreach (pll_languages_list() as $lang) {
            $values[strtoupper($lang)] = $lang;
        }

        register_graphql_enum_type('LanguagesEnum', [
            'description' => __('Languages enum', 'wp-graphql'),
            'values' => $values,
            // 'defaultValue' => 'FI',
        ]);

        register_graphql_object_type('Language', [
            'description' => __('Language fields', 'wp-graphql-polylang'),
            'fields' => [
                'name' => [
                    'type' => 'String',
                ],
                'code' => [
                    'type' => 'String',
                ],
                'locale' => [
                    'type' => 'String',
                ],
            ],
        ]);

        // register_graphql_enum_type('LanguageFieldEnum', [
        //     'description' => __(
        //         'Language field enum for Polylang',
        //         'wp-graphql'
        //     ),
        //     'values' => [
        //         'NAME' => 'name',
        //         'LOCALE' => 'locale',
        //         'SLUG' => 'slug',
        //     ],
        //     // 'defaultValue' => 'FI',
        // ]);
    }

    public function register_fields()
    {
        $this->register_types();
        $this->add_taxonomy_fields();

        $post_types = \WPGraphQL::$allowed_post_types;

        if (empty($post_types) || !is_array($post_types)) {
            return;
        }

        foreach ($post_types as $post_type) {
            $this->add_post_type_fields(get_post_type_object($post_type));
        }
    }

    function add_taxonomy_fields()
    {
        register_graphql_field('Tag', 'lol', [
            'type' => 'Language',
            'description' => __(
                'List available translations for this post',
                'wpnext'
            ),
            'resolve' => function (\WP_Term $term, $args, $context, $info) {
                $fields = $info->getFieldSelection();
                $language = [];

                if (isset($fields['code'])) {
                    $language['code'] = pll_get_term_language(
                        $term->term_id,
                        'slug'
                    );
                }

                if (isset($fields['name'])) {
                    $language['name'] = pll_get_term_language(
                        $term->term_id,
                        'name'
                    );
                }

                if (isset($fields['locale'])) {
                    $language['locale'] = pll_get_term_language(
                        $term->term_id,
                        'locale'
                    );
                }

                return $language;
            },
        ]);
    }

    function add_post_type_fields(\WP_Post_Type $post_type_object)
    {
        $type = ucfirst($post_type_object->graphql_single_name);

        register_graphql_fields("RootQueryTo${type}ConnectionWhereArgs", [
            'lang' => [
                'type' => 'LanguagesEnum',
                'description' => 'Filter by post language (polylang)',
            ],
        ]);

        register_graphql_field(
            $post_type_object->graphql_single_name,
            'translation',
            [
                'type' => $type,
                'description' => __(
                    'Get specific translation version of this object',
                    'wpnext'
                ),
                'args' => [
                    'lang' => [
                        'type' => [
                            'non_null' => 'LanguagesEnum',
                        ],
                    ],
                ],
                'resolve' => function (\WP_Post $post, array $args) {
                    $translations = pll_get_post_translations($post->ID);
                    $post_id = $translations[$args['lang']] ?? null;

                    if (!$post_id) {
                        return null;
                    }

                    return \WP_Post::get_instance($post_id);
                },
            ]
        );

        register_graphql_field(
            $post_type_object->graphql_single_name,
            'translationCodes',
            [
                'type' => Types::list_of(Types::string()),
                'description' => __(
                    'List available translations for this post',
                    'wpnext'
                ),
                'resolve' => function (\WP_Post $post) {
                    return array_keys(pll_get_post_translations($post->ID));
                },
            ]
        );

        register_graphql_field(
            $post_type_object->graphql_single_name,
            'translations',
            [
                'type' => [
                    'list_of' => $type,
                ],
                'description' => __(
                    'List all translated versions of this object',
                    'wpnext'
                ),
                'resolve' => function (\WP_Post $post) {
                    $posts = [];

                    foreach (
                        pll_get_post_translations($post->ID)
                        as $lang => $post_id
                    ) {
                        $post = get_post($post_id);
                        if ($post && !is_wp_error($post)) {
                            $posts[] = $post;
                        }
                    }

                    return $posts;
                },
            ]
        );

        register_graphql_field($post_type_object->graphql_single_name, 'lang', [
            'type' => 'LanguagesEnum',
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
