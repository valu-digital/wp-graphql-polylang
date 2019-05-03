
<?php class PolylangUnitTestCase extends \Codeception\TestCase\WPTestCase
{
    static $polylang;
    static $hooks;
    static function wpSetUpBeforeClass()
    {
        $polylang = PLL();

        // This is called only when there are configured languages
        // https://github.com/polylang/polylang/blob/ca1209a0204a34946adf88c9b859ff43e2eb91b9/admin/admin.php#L62
        // The test setup adds languages too late so we must manually call this.
        $polylang->add_filters();
    }

    static function wpTearDownAfterClass()
    {
        self::delete_all_languages();
    }
    function setUp()
    {
        parent::setUp();
    }
    function tearDown()
    {
        parent::tearDown();
    }
    static function create_language($locale, $args = array())
    {
        $languages = include PLL_SETTINGS_INC . '/languages.php';
        $values = $languages[$locale];
        $values['slug'] = $values['code'];
        $values['rtl'] = (int) ('rtl' === $values['dir']);
        $values['term_group'] = 0; // default term_group
        $args = array_merge($values, $args);
        $polylang = PLL();
        $polylang->model->add_language($args);
        unset($GLOBALS['wp_settings_errors']); // Clean "errors"
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
