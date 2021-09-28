<?php

namespace WPGraphQL\Extensions\Polylang;

use GraphQLRelay\Relay;

class LanguageRootQueries
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
        register_graphql_field('RootQuery', 'languages', [
            'type' => ['list_of' => 'Language'],
            'description' => __(
                'List available languages',
                'wp-graphql-polylang'
            ),
            'resolve' => function ($source, $args, $context, $info) {
                $fields = $info->getFieldSelection();

                // Oh the Polylang api is so nice here. Better ideas?

                $languages = array_map(function ($code) {
                    return [
                        'id' => Relay::toGlobalId('Language', $code),
                        'code' => $code,
                        'slug' => $code,
                    ];
                }, pll_languages_list());

                if (isset($fields['name'])) {
                    foreach (
                        pll_languages_list(['fields' => 'name'])
                        as $index => $name
                    ) {
                        $languages[$index]['name'] = $name;
                    }
                }

                if (isset($fields['locale'])) {
                    foreach (
                        pll_languages_list(['fields' => 'locale'])
                        as $index => $locale
                    ) {
                        $languages[$index]['locale'] = $locale;
                    }
                }

                if (isset($fields['homeUrl'])) {
                    foreach ($languages as &$language) {
                        $language['homeUrl'] = pll_home_url($language['slug']);
                    }
                }

                return $languages;
            },
        ]);

        register_graphql_field('RootQuery', 'defaultLanguage', [
            'type' => 'Language',
            'description' => __('Get language list', 'wp-graphql-polylang'),
            'resolve' => function ($source, $args, $context, $info) {
                $fields = $info->getFieldSelection();
                $language = [];

                // All these fields are build from the same data...
                if (Helpers::uses_slug_based_field($fields)) {
                    $language['code'] = pll_default_language('slug');
                    $language['id'] = Relay::toGlobalId(
                        'Language',
                        $language['code']
                    );
                    $language['slug'] = $language['code'];
                }

                if (isset($fields['name'])) {
                    $language['name'] = pll_default_language('name');
                }

                if (isset($fields['locale'])) {
                    $language['locale'] = pll_default_language('locale');
                }
                if (isset($fields['homeUrl'])) {
                    $language['homeUrl'] = pll_home_url($language['slug']);
                }

                return $language;
            },
        ]);
    }
}
