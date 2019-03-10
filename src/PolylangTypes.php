<?php

namespace WPGraphQL\Extensions\Polylang;

class PolylangTypes
{
    function __construct()
    {
        add_action('graphql_register_types', [$this, 'register'], 9, 0);
    }

    function register()
    {
        error_log('registerin types');
        $language_codes = [];

        foreach (pll_languages_list() as $lang) {
            $language_codes[strtoupper($lang)] = $lang;
        }

        register_graphql_enum_type('LanguageCodeEnum', [
            'description' => __(
                'Enum of all available language codes',
                'wp-graphql-polylang'
            ),
            'values' => $language_codes,
            // 'defaultValue' => 'FI',
        ]);

        register_graphql_object_type('Language', [
            'description' => __('Language (Polylang)', 'wp-graphql-polylang'),
            'fields' => [
                'id' => [
                    'type' => [
                        'non_null' => 'ID',
                    ],
                    'description' => __(
                        'Language ID (Polylang)',
                        'wp-graphql-polylang'
                    ),
                ],
                'name' => [
                    'type' => 'String',
                    'description' => __(
                        'Human readable language name (Polylang)',
                        'wp-graphql-polylang'
                    ),
                ],
                'code' => [
                    'type' => 'LanguageCodeEnum',
                    'description' => __(
                        'Language code (Polylang)',
                        'wp-graphql-polylang'
                    ),
                ],
                'locale' => [
                    'type' => 'String',
                    'description' => __(
                        'Language locale (Polylang)',
                        'wp-graphql-polylang'
                    ),
                ],
                'slug' => [
                    'type' => 'String',
                    'description' => __(
                        'Language term slug. Prefer the "code" field if possible (Polylang)',
                        'wp-graphql-polylang'
                    ),
                ],
            ],
        ]);
    }
}
