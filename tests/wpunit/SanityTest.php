<?php

require_once __DIR__ . '/PolylangUnitTestCase.php';

class SanityTest extends PolylangUnitTestCase
{
    static function wpSetUpBeforeClass()
    {
        parent::wpSetUpBeforeClass();

        self::set_default_language('en_US');
        self::create_language('en_US');
        self::create_language('fr_FR');
        self::create_language('de_DE_formal');
        self::create_language('es_ES');
    }

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testCanInsertPosts()
    {
        $post_data = array(
            'post_title' => 'Test regex',
            'post_content' => 'sadfdsa',
            'post_type' => 'post',
        );
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
        $this->assertEquals($langs, ['en', 'fr', 'de', 'es']);
    }

    public function testPluginIsActivated()
    {
        $this->assertTrue(defined('WPGRAPHQL_POLYLANG'));
    }
}
