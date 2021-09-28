<?php

function add_language($lang, $extra_args = [])
{
    if (PLL_ADMIN !== true) {
        throw new Error('Can add languages only in the admin mode');
    }

    $model = \PLL()->model;

    $languages = include POLYLANG_DIR . '/settings/languages.php';

    if (!isset($languages[$lang])) {
        echo "Unknown language $lang. Available languages:\n";
        echo implode(', ', array_keys($languages));
        exit(2);
    }

    $args = $languages[$lang];

    $args['slug'] = $args['code'];
    $args['rtl'] = (int) ('rtl' === $args['dir']);
    $args['term_group'] = 0;

    $model->add_language(array_merge($args, $extra_args));
}

add_language('en_US', ['term_group' => 10]);
add_language('fi', ['term_group' => 11]);
add_language('sv_SE', ['term_group' => 12]);

function insert_post($title, $content, $lang)
{
    $postID = wp_insert_post([
        'post_content' => $content,
        'post_status' => 'publish',
        'post_title' => $title,
    ]);

    if (is_wp_error($postID)) {
        echo "Unable to insert post.\n";
        echo implode("\n\n", $postID->get_error_messages());
        exit(2);
    }

    PLL()->model->post->set_language($postID, $lang);
}

insert_post('English', 'English content', 'en_US');
insert_post('Suomalainen', 'Suomalainen sisältö', 'fi');
insert_post('Svenska', 'Svenskt innehåll', 'sv_SE');
