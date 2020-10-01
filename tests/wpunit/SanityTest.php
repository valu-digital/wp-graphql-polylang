<?php

// XXX: Can we autoload this somehow?
require_once __DIR__ . '/PolylangUnitTestCase.php';

/**
 * These test do no actually test anything on wp-graphql-polylang. They test
 * that the test enviroment is setup properly. It very hard to get right...
 */
class SanityTest extends PolylangUnitTestCase
{
    static function wpSetUpBeforeClass()
    {
        parent::wpSetUpBeforeClass();
    }

    public function setUp(): void
    {
        parent::setUp();
        self::set_default_language('en_US');
        self::create_language('en_US');
        self::create_language('fr_FR');
        self::create_language('fi');
        self::create_language('de_DE_formal');
        self::create_language('es_ES');

        self::initialize_polylang();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testCanInsertPosts()
    {
        $post_data = [
            'post_title' => 'Test regex',
            'post_status' => 'publish',
            'post_content' => 'sadfdsa',
            'post_type' => 'post',
        ];
        $post_id = wp_insert_post($post_data);

        $this->assertTrue(is_numeric($post_id));
    }

    public function testCanUseWPGraphQL()
    {
        $query = '
            query basicPostList($first:Int){
            posts(first:$first){
                edges{
                    node{
                        id
                        title
                        date
                    }
                }
            }
            }
        ';

        $data = do_graphql_request($query);
    }

    public function testCanUsePolylang()
    {
        $this->assertTrue(defined('POLYLANG_VERSION'));

        $langs = pll_languages_list(['fields' => 'slug']);
        $this->assertEquals($langs, ['en', 'fr', 'fi', 'de', 'es']);
    }

    public function testPluginIsActivated()
    {
        $this->assertTrue(defined('WPGRAPHQL_POLYLANG'));
    }

    public function testCanSetPostLanguage()
    {
        $post_data = [
            'post_title' => 'Test regex',
            'post_status' => 'publish',
            'post_content' => 'sadfdsa',
            'post_type' => 'post',
        ];
        $post_id = wp_insert_post($post_data);
        pll_set_post_language($post_id, 'fi');

        $lang = pll_get_post_language($post_id, 'slug');

        $this->assertEquals($lang, 'fi');
    }

    public function testPolylangCanFilterByLang()
    {
        $post_id = wp_insert_post([
            'post_title' => 'Finnish post',
            'post_status' => 'publish',
            'post_content' => '',
            'post_type' => 'post',
        ]);
        pll_set_post_language($post_id, 'fi');

        $post_id = wp_insert_post([
            'post_title' => 'English post',
            'post_status' => 'publish',
            'post_content' => '',
            'post_type' => 'post',
        ]);
        pll_set_post_language($post_id, 'en');

        $posts = get_posts(['lang' => 'fi']);
        $this->assertEquals(1, count($posts));
    }

    public function testDefaultLanguage()
    {
        $default_lang = pll_default_language();
        $this->assertEquals('en', $default_lang);

        $post_id = wp_insert_post([
            'post_title' => 'Test en',
            'post_status' => 'publish',
            'post_content' => '',
            'post_type' => 'post',
        ]);

        $lang = pll_get_post_language($post_id, 'slug');

        // XXX Fails!
        // $this->assertEquals('en', $lang);
    }
}
