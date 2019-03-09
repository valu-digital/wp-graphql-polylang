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
        $this->show_posts_by_all_languages();

        add_action('graphql_register_types', [$this, 'register_fields'], 10, 0);
    }

    function register_types()
    {
        $values = [];

        foreach (pll_languages_list() as $lang) {
            $values[strtoupper($lang)] = $lang;
        }

        register_graphql_enum_type('LanguageCodeEnum', [
            'description' => __(
                'Enum of all available language codes',
                'wp-graphql-polylang'
            ),
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
                    'type' => 'LanguageCodeEnum',
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

        foreach (\WPGraphQL::get_allowed_post_types() as $post_type) {
            $this->add_post_type_fields(get_post_type_object($post_type));
        }

        foreach (\WPGraphQL::get_allowed_taxonomies() as $taxonomy) {
            $this->add_taxonomy_fields(get_taxonomy($taxonomy));
        }
    }

    function add_lang_root_query(string $type)
    {
        register_graphql_fields("RootQueryTo${type}ConnectionWhereArgs", [
            'lang' => [
                'type' => 'LanguageCodeEnum',
                'description' => "Filter by ${type}s by language code (Polylang)",
            ],
        ]);
    }

    function add_taxonomy_fields(\WP_Taxonomy $taxonomy)
    {
        $type = ucfirst($taxonomy->graphql_single_name);

        $this->add_lang_root_query($type);

        register_graphql_field($type, 'lang', [
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

        register_graphql_field($type, 'translations', [
            'type' => [
                'list_of' => $type,
            ],
            'description' => __(
                'List all translated versions of this object',
                'wp-graphql-polylang'
            ),
            'resolve' => function (\WP_Term $term) {
                $terms = [];

                foreach (
                    pll_get_term_translations($term->term_id)
                    as $lang => $term_id
                ) {
                    if ($term_id === $term->term_id) {
                        continue;
                    }

                    $translation = get_term($term_id);

                    if (!$translation) {
                        continue;
                    }

                    if (is_wp_error($translation)) {
                        continue;
                    }

                    $terms[] = $translation;
                }

                return $terms;
            },
        ]);
    }

    function add_post_type_fields(\WP_Post_Type $post_type_object)
    {
        $type = ucfirst($post_type_object->graphql_single_name);

        $this->add_lang_root_query($type);

        register_graphql_field(
            $post_type_object->graphql_single_name,
            'translation',
            [
                'type' => $type,
                'description' => __(
                    'Get specific translation version of this object',
                    'wp-graphql-polylang'
                ),
                'args' => [
                    'lang' => [
                        'type' => [
                            'non_null' => 'LanguageCodeEnum',
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
                'type' => ['list_of' => 'LanguageCodeEnum'],
                'description' => __(
                    'List available translations for this post',
                    'wpnext'
                ),
                'resolve' => function (\WP_Post $post) {
                    $codes = [];
                    $current_code = pll_get_post_language($post->ID, 'slug');

                    foreach (
                        array_keys(pll_get_post_translations($post->ID))
                        as $code
                    ) {
                        if ($code !== $current_code) {
                            $codes[] = $code;
                        }
                    }

                    return $codes;
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
                    'wp-graphql-polylang'
                ),
                'resolve' => function (\WP_Post $post) {
                    $posts = [];

                    foreach (
                        pll_get_post_translations($post->ID)
                        as $lang => $post_id
                    ) {
                        $translation = get_post($post_id);

                        if (!$translation) {
                            continue;
                        }

                        if (is_wp_error($translation)) {
                            continue;
                        }

                        if ($post->ID === $translation->ID) {
                            continue;
                        }

                        $posts[] = $translation;
                    }

                    return $posts;
                },
            ]
        );

        register_graphql_field($post_type_object->graphql_single_name, 'lang', [
            'type' => 'Language',
            'description' => __('Polylang language', 'wpnext'),
            'resolve' => function (\WP_Post $post, $args, $context, $info) {
                $fields = $info->getFieldSelection();
                $language = [];

                if (isset($fields['code'])) {
                    $language['code'] = pll_get_post_language(
                        $post->ID,
                        'slug'
                    );
                }

                if (isset($fields['name'])) {
                    $language['name'] = pll_get_post_language(
                        $post->ID,
                        'name'
                    );
                }

                if (isset($fields['locale'])) {
                    $language['locale'] = pll_get_post_language(
                        $post->ID,
                        'locale'
                    );
                }

                return $language;
            },
        ]);
    }

    function show_posts_by_all_languages()
    {
        add_filter(
            'graphql_post_object_connection_query_args',
            function () {
                $query_args['show_all_languages_in_graphql'] = true;
                return $query_args;
            },
            10,
            1
        );

        /**
         * Handle query var added by the above filter in Polylang which
         * causes all languages to be shown in the queries.
         * See https://github.com/polylang/polylang/blob/2ed446f92955cc2c952b944280fce3c18319bd85/include/query.php#L125-L134
         */
        add_filter(
            'pll_filter_query_excluded_query_vars',
            function () {
                $excludes[] = 'show_all_languages_in_graphql';
                return $excludes;
            },
            3,
            10
        );
    }
}

add_action('init', function () {
    new Polylang();
});
