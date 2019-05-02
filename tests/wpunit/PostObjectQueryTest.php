<?php

// XXX: Can we autoload this somehow?
require_once __DIR__ . '/PolylangUnitTestCase.php';

class PostObjectQueryTest extends PolylangUnitTestCase
{
    static function wpSetUpBeforeClass()
    {
        parent::wpSetUpBeforeClass();

        self::set_default_language('en_US');
        self::create_language('en_US');
        self::create_language('fr_FR');
        self::create_language('fi');
        self::create_language('de_DE_formal');
        self::create_language('es_ES');
    }

    public function setUp()
    {
        parent::setUp();

        $post_id = wp_insert_post([
            'post_title' => 'Finnish post',
            'post_content' => '',
            'post_type' => 'post',
            'post_status' => 'publish',
        ]);
        pll_set_post_language($post_id, 'fi');

        $post_id = wp_insert_post([
            'post_title' => 'English post',
            'post_content' => '',
            'post_type' => 'post',
            'post_status' => 'publish',
        ]);
        pll_set_post_language($post_id, 'en');
    }

    public function testListsPostsFromAllLanguages()
    {
        $query = '
        query Posts {
            posts {
              nodes {
                title
              }
            }
         }
        ';

        $data = do_graphql_request($query);
        $nodes = $data['data']['posts']['nodes'];
        $expected = [
            //
            ['title' => 'English post'],
            ['title' => 'Finnish post'],
        ];

        $this->assertEquals($nodes, $expected);
    }

    public function testCanHaveLanguageField()
    {
        $query = '
        query Posts {
            posts {
              nodes {
                title
                language {
                    name
                    locale
                }
              }
            }
         }
        ';

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $nodes = $data['data']['posts']['nodes'];
        $expected = [
            //
            [
                'title' => 'English post',
                'language' => [
                    'name' => 'English',
                    'locale' => 'en_US',
                ],
            ],
            [
                'title' => 'Finnish post',
                'language' => [
                    'name' => 'Suomi',
                    'locale' => 'fi',
                ],
            ],
        ];

        $this->assertEquals($nodes, $expected);
    }

    public function testCanFilterByLanguage()
    {
        $query = '
        query Posts {
            posts(where: {language: FI}) {
              nodes {
                title
              }
            }
         }
        ';

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $nodes = $data['data']['posts']['nodes'];
        $expected = [['title' => 'Finnish post']];

        // XXX
        // $this->assertEquals($expected, $nodes);
    }
}
