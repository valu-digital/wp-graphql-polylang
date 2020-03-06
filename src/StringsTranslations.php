<?php

namespace WPGraphQL\Extensions\Polylang;

class StringsTranslations
{
    function init()
    {
        add_action(
            'graphql_register_types',
            [$this, '__action_graphql_register_types'],
            10,
            0
        );
    }

    function __action_graphql_register_types()
    {
        register_graphql_field('RootQuery', 'translateString', [
            'type' => 'String',
            'description' => __(
                'Translate string using pll_translate_string() (Polylang)',
                'wp-graphql-polylang'
            ),
            'args' => [
                'string' => [
                    'type' => [
                        'non_null' => 'String',
                    ],
                ],
                'language' => [
                    'type' => [
                        'non_null' => 'LanguageCodeEnum',
                    ],
                ],
            ],
            'resolve' => function ($source, $args, $context, $info) {
                return pll_translate_string($args['string'], $args['language']);
            },
        ]);
    }
}
