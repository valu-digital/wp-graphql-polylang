<?php

namespace WPGraphQL\Extensions\Polylang;

use GraphQL\Type\Definition\ResolveInfo;

/**
 * Adds support for the "ACF Options For Polylang" plugin
 * https://github.com/BeAPI/acf-options-for-polylang
 *
 *
 * The logic is following
 *
 *  Detect Options Pages exposed to graphql schema and their root query names.
 *
 *  Using this information extend the root queries via graphql_RootQuery_fields
 *  filter to have `language` args.
 *
 *  When query is send use this same information to detect Options Page queries
 *  and save the requested languages to a global ($root_query_locale_mapping).
 *
 *  In a before resolve filter for sub fields detect if the root is in the
 *  global mapping and move that language to the $current_language global
 *
 *  In a ACF filter use the $current_language to get the correct language
 *  version of the options page.
 *
 *  In after resolve filter clear the $current_language global so it won't
 *  affect other Options Pages.
 *
 */
class OptionsPages
{
    /**
     * Mapping of used options page root queries to their selected languages
     */
    static $root_query_locale_mapping = [];

    /**
     * Language of the currently resolving options page field
     */
    static $current_language = null;

    static function init()
    {
        add_filter(
            'graphql_RootQuery_fields',
            [self::class, '__action_graphql_RootQuery_fields'],
            50,
            1
        );

        add_action(
            'graphql_before_resolve_field',
            [self::class, '__action_graphql_before_resolve_field'],
            10,
            9
        );

        add_action(
            'graphql_after_resolve_field',
            [self::class, '__action_graphql_after_resolve_field'],
            10,
            9
        );

        add_filter(
            'acf/validate_post_id',
            [self::class, '__action_acf_validate_post_id'],
            99,
            1
        );
        add_filter(
            'graphql_acf_get_root_id',
            [self::class, '__action_graphql_acf_get_root_id'],
            99,
            1
        );
    }

    /**
     * Add language arg to all options page root queries
     */
    static function __action_graphql_RootQuery_fields($fields)
    {
        foreach (self::get_options_page_root_queries() as $root_query) {
            if (!isset($fields[$root_query])) {
                continue;
            }

            $fields[$root_query]['args'] = [
                'language' => [
                    'type' => 'LanguageCodeFilterEnum',
                    'description' => 'Filter by language code (Polylang)',
                ],
            ];
        }

        return $fields;
    }

    static function __action_graphql_before_resolve_field(
        $source,
        $args,
        $context,
        ResolveInfo $info,
        $field_resolver,
        $type_name,
        $field_key,
        $field
    ) {
        /**
         * Record what languages are used by the options page root queries
         */
        if (
            isset($args['language']) &&
            self::is_options_page_root_query($info)
        ) {
            $model = \PLL()->model;
            $lang = $model->get_language($args['language'])->locale ?? null;

            if ($lang) {
                self::$root_query_locale_mapping[$info->path[0]] = $lang;
            }
        }

        /**
         * If resolving field under options page root query set the current language
         */
        if (self::is_options_page($source)) {
            $root_query = $info->path[0];
            $lang = self::$root_query_locale_mapping[$root_query] ?? null;
            if ($lang) {
                self::$current_language = $lang;
            }
        }
    }

    static function __action_graphql_after_resolve_field(
        $source,
        $args,
        $context,
        ResolveInfo $info,
        $field_resolver,
        $type_name,
        $field_key,
        $field
    ) {
        /**
         * Clear the current language after the field has been resolved
         */
        if (self::is_options_page($source)) {
            self::$current_language = null;
        }
    }

    static function __action_acf_validate_post_id($id)
    {
        return self::add_translation_suffix($id);
    }

    static function __action_graphql_acf_get_root_id($id)
    {
        return self::add_translation_suffix($id);
    }

    static function add_translation_suffix($id)
    {
        if (!self::$current_language) {
            return $id;
        }

        // We'll add the suffix only once to the "options" wp option
        if ($id !== 'options') {
            return $id;
        }

        return $id . '_' . self::$current_language;
    }

    static function is_options_page($source)
    {
        if (!is_array($source)) {
            return false;
        }

        $type = $source['type'] ?? null;
        return $type === 'options_page';
    }

    static function is_options_page_root_query(ResolveInfo $info)
    {
        if (count($info->path) !== 1) {
            return false;
        }

        $root_queries = self::get_options_page_root_queries();

        return in_array($info->fieldName, $root_queries);
    }

    /**
     * Return array of the options page root query names.
     *
     * This requires that 'graphql_field_name' is passed to acf_add_options_page()
     */
    static function get_options_page_root_queries()
    {
        $graphql_options_pages = acf_get_options_pages();

        if (
            empty($graphql_options_pages) ||
            !is_array($graphql_options_pages)
        ) {
            return [];
        }

        $queries = [];

        foreach ($graphql_options_pages as $options_page_key => $options_page) {
            if (empty($options_page['show_in_graphql'])) {
                continue;
            }

            if (empty($options_page['graphql_field_name'])) {
                error_log(
                    "Warning(wp-graphql-polylang): ACF Options Page '$options_page_key' has no 'graphql_field_name'"
                );
                continue;
            }

            $queries[] = $options_page['graphql_field_name'];
        }

        return $queries;
    }
}
