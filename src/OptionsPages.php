<?php

namespace WPGraphQL\Extensions\Polylang;

use GraphQL\Type\Definition\ResolveInfo;

class OptionsPages
{
    static $language_root_queries = [];
    static $current_language = null;

    static function init()
    {
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
        if (self::is_root_query($info) && isset($args['language'])) {
            $model = \PLL()->model;
            $lang = $model->get_language($args['language'])->locale ?? null;

            if ($lang) {
                self::$language_root_queries[$info->path[0]] = strtolower(
                    $lang
                );
            }
        }

        if (self::is_options_page($source)) {
            $root_query = $info->path[0];
            $lang = self::$language_root_queries[$root_query] ?? null;
            if ($lang) {
                error_log(
                    "Setting lang $field_key ::" . self::$current_language
                );
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

        if (self::is_already_localized($id)) {
            return $id;
        }

        return $id . '_' . self::$current_language;
    }

    static function is_already_localized($post_id)
    {
        preg_match('/[a-z]{2}_[A-Z]{2}/', $post_id, $language);

        return !empty($language);
    }

    static function is_options_page($source)
    {
        $type = $source['type'] ?? null;
        return $type === 'options_page';
    }

    static function is_root_query(ResolveInfo $info)
    {
        return count($info->path) === 1;
    }

    static function get_option_page_root_queries()
    {
        return ['siteSettings'];
    }
}
