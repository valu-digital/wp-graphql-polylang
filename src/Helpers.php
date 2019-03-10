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
     * query arg if it is set
     */
    static function prepare_lang_field(array $query_args)
    {
        if (!isset($query_args['language'])) {
            return $query_args;
        }

        $lang = $query_args['language'];
        unset($query_args['language']);

        if ('all' === $lang) {
            // No need to do anything. We show all languages by default
            return $query_args;
        }

        if ('default' === $lang) {
            $lang = pll_default_language('slug');
        }

        $query_args['lang'] = $lang;

        return $query_args;
    }
}
