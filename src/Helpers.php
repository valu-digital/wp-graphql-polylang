<?php

namespace WPGraphQL\Extensions\Polylang;

class Helpers
{
    /**
     * code and id are both computed based on the slug
     */
    static function uses_slug_based_field(array $fields)
    {
        return isset($fields['code']) ||
            isset($fields['slug']) ||
            isset($fields['id']);
    }

    /**
     * Polylang handles 'lang' query arg so convert our 'language'
     * or 'languages' query arg if it is set
     */
    static function map_language_to_query_args(
        array $query_args,
        array $where_args
    ) {
        $lang = '';
        if (
            isset($where_args['languages']) &&
            is_array($where_args['languages']) &&
            !empty($where_args['languages'])
        ) {
            $langs = $where_args['languages'];
            unset($where_args['languages']);
            $lang = implode(',', $langs);
        } elseif (isset($where_args['language'])) {
            $lang = $where_args['language'];
            unset($where_args['language']);

            if ('all' === $lang) {
                // No need to do anything. We show all languages by default
                return $query_args;
            }

            if ('default' === $lang) {
                $lang = pll_default_language('slug');
            }
        } else {
            return $query_args;
        }

        $query_args['lang'] = $lang;

        return $query_args;
    }
}
