<?php

namespace WPGraphQL\Extensions\Polylang;

use GraphQLRelay\Relay;

class TermObject
{
    function init()
    {
        add_action(
            'graphql_register_types',
            [$this, '__action_graphql_register_types'],
            10,
            0
        );

        add_filter(
            'graphql_map_input_fields_to_get_terms',
            [__NAMESPACE__ . '\\Helpers', 'map_language_to_query_args'],
            10,
            2
        );

        add_filter(
            'graphql_term_object_insert_term_args',
            [$this, '__filter_graphql_term_object_insert_term_args'],
            10,
            2
        );
    }

    function __filter_graphql_term_object_insert_term_args($insert_args, $input)
    {
        if (isset($input['language'])) {
            $insert_args['language'] = $input['language'];
        }

        return $insert_args;
    }

    function __action_graphql_register_types()
    {
        foreach (\WPGraphQL::get_allowed_taxonomies() as $taxonomy) {
            $this->add_taxonomy_fields(get_taxonomy($taxonomy));
        }
    }

    function add_taxonomy_fields(\WP_Taxonomy $taxonomy)
    {
        if (!pll_is_translated_taxonomy($taxonomy->name)) {
            return;
        }

        $type = ucfirst($taxonomy->graphql_single_name);

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

        /**
         * Handle language arg for term inserts
         */
        add_action(
            "graphql_insert_{$taxonomy->name}",
            function ($term_id, $args) {
                if (isset($args['language'])) {
                    pll_set_term_language($term_id, $args['language']);
                } else {
                    $default_lang = pll_default_language();
                    pll_set_term_language($term_id, $default_lang);
                }
            },
            10,
            2
        );

        /**
         * Handle language arg for term updates
         */
        add_action(
            "graphql_update_{$taxonomy->name}",
            function ($term_id, $args) {
                if (isset($args['language'])) {
                    pll_set_term_language($term_id, $args['language']);
                }
            },
            10,
            2
        );

        register_graphql_field($type, 'language', [
            'type' => 'Language',
            'description' => __(
                'List available translations for this post',
                'wpnext'
            ),
            'resolve' => function (
                \WPGraphQL\Model\Term $term,
                $args,
                $context,
                $info
            ) {
                $fields = $info->getFieldSelection();
                $language = [];

                if (Helpers::uses_slug_based_field($fields)) {
                    $language['code'] = pll_get_term_language(
                        $term->term_id,
                        'slug'
                    );
                    $language['slug'] = $language['code'];
                    $language['id'] = Relay::toGlobalId(
                        'Language',
                        $language['code']
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
                'List all translated versions of this term',
                'wp-graphql-polylang'
            ),
            'resolve' => function (\WPGraphQL\Model\Term $term) {
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

                    $terms[] = new \WPGraphQL\Model\Term($translation);
                }

                return $terms;
            },
        ]);

        register_graphql_field($type, 'translation', [
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
            'resolve' => function (\WPGraphQL\Model\Term $term, array $args) {
                $translations = pll_get_term_translations($term->term_id);
                $term_id = $translations[$args['language']] ?? null;

                if (!$term_id) {
                    return null;
                }

                return new \WPGraphQL\Model\Term(get_term($term_id));
            },
        ]);
    }
}
