<?php

namespace WPGraphQL\Extensions\Polylang;

class PolylangTypes
{
    function init()
    {
        add_action(
            'graphql_register_types',
            [$this, '__action_graphql_register_types'],
            9,
            0
        );
    }

    function __action_graphql_register_types()
    {
        $language_codes = [];

        foreach (pll_languages_list() as $lang) {
            $language_codes[strtoupper($lang)] = $lang;
        }

        if (empty($language_codes)) {
            $locale = get_locale();
            $language_codes[strtoupper($locale)] = [
                'value' => $locale,
                'description' => __(
                    'The default locale of the site',
                    'wp-graphql-polylang'
                ),
            ];
        }

        register_graphql_enum_type('LanguageCodeEnum', [
            'description' => __(
                'Enum of all available language codes',
                'wp-graphql-polylang'
            ),
            'values' => $language_codes,
        ]);

        register_graphql_enum_type('LanguageCodeFilterEnum', [
            'description' => __(
                'Filter item by specific language, default language or list all languages',
                'wp-graphql-polylang'
            ),
            'values' => array_merge($language_codes, [
                'DEFAULT' => 'default',
                'ALL' => 'all',
            ]),
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
                'homeUrl' => [
                    'type' => 'String',
                    'description' => __(
                        'Language term front page URL',
                        'wp-graphql-polylang'
                    ),
                ],
            ],
        ]);
    }
}
