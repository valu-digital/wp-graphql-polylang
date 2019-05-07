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

        $this->assertEquals($expected, $nodes);
    }

    public function testCanFetchTranslatedVersions()
    {
        $fi_post_id = wp_insert_post([
            'post_title' => 'Finnish post version',
            'post_content' => '',
            'post_type' => 'post',
            'post_status' => 'publish',
        ]);
        pll_set_post_language($fi_post_id, 'fi');

        $en_post_id = wp_insert_post([
            'post_title' => 'English post version',
            'post_content' => '',
            'post_type' => 'post',
            'post_status' => 'publish',
        ]);
        pll_set_post_language($en_post_id, 'en');

        pll_save_post_translations([
            'en' => $en_post_id,
            'fi' => $fi_post_id,
        ]);

        $query = "
        query Post {
            postBy(postId: $fi_post_id) {
                title
                translations {
                  title
                }
            }
         }
        ";

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));

        $expected = [
            'title' => 'Finnish post version',
            'translations' => [
                [
                    'title' => 'English post version',
                ],
            ],
        ];

        $this->assertEquals($expected, $data['data']['postBy']);
    }

    public function testCanFetchSingleTranslatedVersion()
    {
        $fi_post_id = wp_insert_post([
            'post_title' => 'Finnish post version',
            'post_content' => '',
            'post_type' => 'post',
            'post_status' => 'publish',
        ]);
        pll_set_post_language($fi_post_id, 'fi');

        $en_post_id = wp_insert_post([
            'post_title' => 'English post version',
            'post_content' => '',
            'post_type' => 'post',
            'post_status' => 'publish',
        ]);
        pll_set_post_language($en_post_id, 'en');

        pll_save_post_translations([
            'en' => $en_post_id,
            'fi' => $fi_post_id,
        ]);

        $query = "
        query Post {
            postBy(postId: $fi_post_id) {
                title
                translation(language: EN) {
                  title
                }
            }
         }
        ";

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));

        $expected = [
            'title' => 'Finnish post version',
            'translation' => [
                'title' => 'English post version',
            ],
        ];

        $this->assertEquals($expected, $data['data']['postBy']);
    }
}
