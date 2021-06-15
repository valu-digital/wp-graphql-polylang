<?php

namespace WPGraphQL\Extensions\Polylang;

class StringsTranslations
{
    protected $groupNames;
    protected $groups;
    protected $strings;
    protected $translations;

    function init()
    {
        $this->strings = \PLL_Admin_Strings::get_strings();
        $this->groupNames = array_unique(array_values(wp_list_pluck($this->strings, 'context')));
        $this->get_all_entries();

        add_action(
            'graphql_register_types',
            [$this, '__action_graphql_register_types'],
            10,
            0
        );
    }

    function get_all_entries()
    {
        if (function_exists('pll_languages_list')  
            && function_exists('get_transient') 
            && class_exists('PLL_MO')
        ) {
            $languages = \get_transient('pll_languages_list');

            if (!empty($languages)) {
                $mo = [];
                foreach ( $languages as $language ) {
                    $language = (object) $language;
                    $mo[ $language->slug ] = new \PLL_MO();
                    $mo[ $language->slug ]->import_from_db($language);
                }

                $groups       = [];
                $translations = [];
                foreach ( $languages as $language ) {
                    $slug = $language['slug'];
                    foreach ( $this->strings as $id => $obj ) {
                        $string = $mo[ $slug ]->translate($obj['string']);

                        $groups[$obj['context']][$slug][$obj['string']] = $string;
                        $translations[ $slug ][$obj['string']] = $string;
                    }
                }

                $this->groups       = $groups;
                $this->translations = $translations;   
            }
        }
    }

    function get_translations($args)
    {
        $translations = [];
        $return_content = (object) [];
        if (isset($args['group']) && !empty($args['group'])) {
            $translations = $this->groups[$args['group']];
        } else {
            $translations = $this->translations;
        }

        if (isset($args['language']) && !empty($args['language'])) {
            foreach ($translations as $lang => $obj) {
                if ($args['language'] !== $lang) {
                    unset($translations[$lang]);
                }
            }
        }
        
        if (isset($args['includes']) && !empty($args['includes'])) {
            foreach ($translations as $lang => $langTranslations) {
                foreach ($langTranslations as $key => $s) {
                    if (false !== stripos($key, $args['includes']) ) {
                        if (isset($args['group']) && !empty($args['group'])) {
                            $group = $args['group'];
                            $return_content->$group->$lang->$key = $s;
                        } else {
                            $return_content->$lang->$key = $s;
                        }
                    }
                }
            }
        } else {
            $return_content = $translations;
        }

        return $return_content;
    }

    function __action_graphql_register_types()
    {
        
        $groupNames = [];
        foreach ($this->groupNames as $groupName) {
            $key = strtoupper($groupName);
            $groupNames[$key] = $groupName;
        }

        register_graphql_enum_type(
            'TranslationGroupEnum', [
                'description' => __(
                    'Enum of all available groups',
                    'wp-graphql-polylang'
                ),
                'values' => $groupNames,
            ]
        );

        register_graphql_scalar(
            'JSON', [
                'description'  => __(
                    'Scalar for content returns where defining a type is impossible',
                    'wp-graphql-polylang'
                ),
                'serialize'    => function ($value) {
                    return $value;
                },
                'parseValue'   => function ($value) {
                    return $value;
                },
                'parseLiteral' => function ($valueNode, array $variables = null) {
                    return $valueNode->value;
                }
            ]
        );

        register_graphql_fields(
            'RootQuery', [
                'translateString' => [
                    'type' => 'String',
                    'description' => __(
                        'Translate string using pll_translate_string() (Polylang)',
                        'wp-graphql-polylang'
                    ),
                    'args' => [
                        'language' => [
                            'type' => 'LanguageCodeEnum',
                        ],
                        'string' => [
                            'type' => 'String',
                        ],
                    ],
                    'resolve' => function ($source, $args, $context, $info) {
                        return pll_translate_string($args['string'], $args['language']);
                    },
                ],
                'translatedStrings' => [
                    'type' => 'JSON',
                    'description' => __(
                        'All translated strings, filterable by group and/or language (Polylang)',
                        'wp-graphql-polylang'
                    ),
                    'args' => [
                        'group' => [
                            'type' => 'TranslationGroupEnum',
                        ],
                        'includes' => [
                            'type' => 'String',
                        ],
                        'language' => [
                            'type' =>  'LanguageCodeEnum',
                        ],
                    ],
                    'resolve' => function ($source, $args, $context, $info) {
                        return $this->get_translations($args);
                    },
                ],
            ]
        );
    }
}
