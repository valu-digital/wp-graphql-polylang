<?php

namespace WPGraphQL\Extensions\Polylang;

use GraphQLRelay\Relay;

class PostObject
{
    function init()
    {
        add_action(
            'graphql_register_types',
            [$this, '__action_graphql_register_types'],
            10,
            0
        );

        add_action(
            'graphql_post_object_mutation_update_additional_data',
            [
                $this,
                '__action_graphql_post_object_mutation_update_additional_data',
            ],
            10,
            4
        );

        add_filter(
            'graphql_map_input_fields_to_wp_query',
            [__NAMESPACE__ . '\\Helpers', 'map_language_to_query_args'],
            10,
            2
        );

        /**
         * Check translated front page
         */
        add_action(
            'graphql_resolve_field',
            [$this, '__action_is_translated_front_page'],
            10,
            8
        );
    }

    /**
     * Handle 'language' in post object create&language mutations
     */
    function __action_graphql_post_object_mutation_update_additional_data(
        $post_id,
        array $input,
        \WP_Post_Type $post_type_object,
        $mutation_name
    ) {
        $is_create = substr($mutation_name, 0, 6) === 'create';

        if (isset($input['language'])) {
            pll_set_post_language($post_id, $input['language']);
        } elseif ($is_create) {
            $default_lang = pll_default_language();
            pll_set_post_language($post_id, $default_lang);
        }
    }

    function __action_graphql_register_types()
    {
        register_graphql_fields('RootQueryToContentNodeConnectionWhereArgs', [
            'language' => [
                'type' => 'LanguageCodeFilterEnum',
                'description' =>
                    'Filter content nodes by language code (Polylang)',
            ],
            'languages' => [
                'type' => [
                    'list_of' => [
                        'non_null' => 'LanguageCodeEnum',
                    ],
                ],
                'description' =>
                    'Filter content nodes by one or more languages (Polylang)',
            ],
        ]);

        foreach (\WPGraphQL::get_allowed_post_types() as $post_type) {
            $this->add_post_type_fields(get_post_type_object($post_type));
        }
    }

    function add_post_type_fields(\WP_Post_Type $post_type_object)
    {
        if (!pll_is_translated_post_type($post_type_object->name)) {
            return;
        }

        $type = ucfirst($post_type_object->graphql_single_name);

        register_graphql_fields("RootQueryTo${type}ConnectionWhereArgs", [
            'language' => [
                'type' => 'LanguageCodeFilterEnum',
                'description' => "Filter by ${type}s by language code (Polylang)",
            ],
            'languages' => [
                'type' => [
                    'list_of' => [
                        'non_null' => 'LanguageCodeEnum',
                    ],
                ],
                'description' => "Filter ${type}s by one or more languages (Polylang)",
            ],
        ]);

        register_graphql_fields("Create${type}Input", [
            'language' => [
                'type' => 'LanguageCodeEnum',
            ],
        ]);

        register_graphql_fields("Update${type}Input", [
            'language' => [
                'type' => 'LanguageCodeEnum',
            ],
        ]);

        register_graphql_field(
            $post_type_object->graphql_single_name,
            'language',
            [
                'type' => 'Language',
                'description' => __('Polylang language', 'wpnext'),
                'resolve' => function (
                    \WPGraphQL\Model\Post $post,
                    $args,
                    $context,
                    $info
                ) {
                    $fields = $info->getFieldSelection();
                    $language = [
                        'name' => null,
                        'slug' => null,
                        'code' => null,
                    ];

                    $post_id = $post->ID;

                    // The language of the preview post is not set at all so we
                    // must get the language using the original post id
                    if ($post->isPreview) {
                        $post_id = wp_get_post_parent_id($post->ID);
                    }

                    $slug = pll_get_post_language($post_id, 'slug');

                    if (!$slug) {
                        return null;
                    }

                    $language['code'] = $slug;
                    $language['slug'] = $slug;
                    $language['id'] = Relay::toGlobalId('Language', $slug);

                    if (isset($fields['name'])) {
                        $language['name'] = pll_get_post_language(
                            $post_id,
                            'name'
                        );
                    }

                    if (isset($fields['locale'])) {
                        $language['locale'] = pll_get_post_language(
                            $post_id,
                            'locale'
                        );
                    }

                    return $language;
                },
            ]
        );

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
                    'language' => [
                        'type' => [
                            'non_null' => 'LanguageCodeEnum',
                        ],
                    ],
                ],
                'resolve' => function (
                    \WPGraphQL\Model\Post $post,
                    array $args
                ) {
                    $translations = pll_get_post_translations($post->ID);
                    $post_id = $translations[$args['language']] ?? null;

                    if (!$post_id) {
                        return null;
                    }

                    return new \WPGraphQL\Model\Post(
                        \WP_Post::get_instance($post_id)
                    );
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
                    'List all translated versions of this post',
                    'wp-graphql-polylang'
                ),
                'resolve' => function (\WPGraphQL\Model\Post $post) {
                    $posts = [];

                    if ($post->isPreview) {
                        $parent = wp_get_post_parent_id($post->ID);
                        $translations = pll_get_post_translations($parent);
                    } else {
                        $translations = pll_get_post_translations($post->ID);
                    }

                    foreach ($translations as $lang => $post_id) {
                        $translation = \WP_Post::get_instance($post_id);

                        if (!$translation) {
                            continue;
                        }

                        if (is_wp_error($translation)) {
                            continue;
                        }

                        if ($post->ID === $translation->ID) {
                            continue;
                        }

                        // If fetching preview do not add the original as a translation
                        if ($post->isPreview && $parent === $translation->ID) {
                            continue;
                        }

                        $model = new \WPGraphQL\Model\Post($translation);

                        // If we do not filter out privates here wp-graphql will
                        // crash with 'Cannot return null for non-nullable field
                        // Post.id.'. This might be a wp-graphql bug.
                        // Interestingly only fetching the id of the translated
                        // post caused the crash. For example title is ok even
                        // without this check
                        if ($model->is_private()) {
                            continue;
                        }

                        $posts[] = $model;
                    }

                    return $posts;
                },
            ]
        );
    }

    function __action_is_translated_front_page(
        $result,
        $source,
        $args,
        $context,
        $info,
        $type_name,
        $field_key
    ) {
        if ('isFrontPage' !== $field_key) {
            return $result;
        }

        if (!($source instanceof \WPGraphQL\Model\Post)) {
            return $result;
        }

        if ('page' !== get_option('show_on_front', 'posts')) {
            return $result;
        }

        if (empty((int) get_option('page_on_front', 0))) {
            return $result;
        }

        $translated_front_page = pll_get_post_translations(
            get_option('page_on_front', 0)
        );

        if (empty($translated_front_page)) {
            return false;
        }

        return in_array($source->ID, $translated_front_page);
    }
}
