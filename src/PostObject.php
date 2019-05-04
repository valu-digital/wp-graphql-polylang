<?php

namespace WPGraphQL\Extensions\Polylang;

use GraphQLRelay\Relay;

class PostObject
{
    function init()
    {
        add_action('graphql_register_types', [$this, 'register_fields'], 10, 0);

        add_action(
            'graphql_post_object_mutation_update_additional_data',
            [$this, 'mutate_language'],
            10,
            3
        );

        add_filter(
            'graphql_map_input_fields_to_wp_query',
            [__NAMESPACE__ . '\\Helpers', 'map_language_to_query_args'],
            10,
            2
        );
    }

    /**
     * Handle 'language' in post object create&language mutations
     */
    function mutate_language(
        $post_id,
        array $input,
        \WP_Post_Type $post_type_object
    ) {
        if (isset($input['language'])) {
            pll_set_post_language($post_id, $input['language']);
        } else {
            $default_lang = pll_default_language();
            pll_set_post_language($post_id, $default_lang);
        }
    }

    function register_fields()
    {
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

                    $slug = pll_get_post_language($post->ID, 'slug');

                    if (!$slug) {
                        return null;
                    }

                    $language['code'] = $slug;
                    $language['slug'] = $slug;
                    $language['id'] = Relay::toGlobalId('Language', $slug);

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

                    foreach (
                        pll_get_post_translations($post->ID)
                        as $lang => $post_id
                    ) {
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

                        $posts[] = new \WPGraphQL\Model\Post($translation);
                    }

                    return $posts;
                },
            ]
        );
    }
}
