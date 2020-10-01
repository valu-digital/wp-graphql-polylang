
<?php class PolylangUnitTestCase extends \Codeception\TestCase\WPTestCase
{
    static $polylang;
    static $hooks;

    static function wpSetUpBeforeClass()
    {
        $polylang = PLL();
    }

    static function wpTearDownAfterClass()
    {
        self::delete_all_languages();
    }
    function setUp(): void
    {
        parent::setUp();
    }
    function tearDown(): void
    {
        parent::tearDown();
        \WPGraphQL::clear_schema();
    }
    static function create_language($locale, $args = [])
    {
        $languages = include POLYLANG_DIR . '/settings/languages.php';
        $values = $languages[$locale];
        $values['slug'] = $values['code'];
        $values['rtl'] = (int) ('rtl' === $values['dir']);
        $values['term_group'] = 0; // default term_group
        $args = array_merge($values, $args);
        $polylang = PLL();
        $polylang->model->add_language($args);
        unset($GLOBALS['wp_settings_errors']); // Clean "errors"
    }

    /**
     * Must be called after adding the languages because many of the polylang
     * initializations happen only when at least one language has been added
     *
     * https://github.com/polylang/polylang/blob/2.8.2/include/base.php#L71-L82
     */
    static function initialize_polylang()
    {
        $polylang = PLL();
        $polylang->init();
        $polylang->add_filters();
        $polylang->nav_menu->create_nav_menu_locations();
    }

    static function set_default_language($lang)
    {
        // XXX Not enough
        PLL()->links_model->options['default_lang'] = $lang;
    }
    static function delete_all_languages()
    {
        $languages = PLL()->model->get_languages_list();
        if (is_array($languages)) {
            // Delete the default categories first
            $tt = wp_get_object_terms(
                get_option('default_category'),
                'term_translations'
            );
            $terms = PLL()->model->term->get_translations(
                get_option('default_category')
            );
            wp_delete_term($tt, 'term_translations');
            foreach ($terms as $t) {
                wp_delete_term($t, 'category');
            }
            foreach ($languages as $lang) {
                PLL()->model->delete_language($lang->term_id);
                unset($GLOBALS['wp_settings_errors']);
            }
        }
    }
}
