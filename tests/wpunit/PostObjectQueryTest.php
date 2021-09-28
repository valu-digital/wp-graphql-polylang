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

    public function setUp(): void
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

    public function testDraftTranslationDoesNotCrash()
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
            'post_status' => 'draft',
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
                  id
                }
            }
         }
        ";

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));

        $expected = [
            'title' => 'Finnish post version',
            'translations' => [],
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

    public function testContentNodesFiltering()
    {
        $query = '
        query Posts {
            contentNodes(where: {language: FI}) {
              nodes {
                ... on Post {
                  title
                }
              }
            }
         }
        ';

        $data = do_graphql_request($query);
        $nodes = $data['data']['contentNodes']['nodes'] ?? [];
        $expected = [
            [
                'title' => 'Finnish post',
            ],
        ];

        $this->assertEquals($nodes, $expected);
    }

    public function testListsPostsFromAllLanguagesWhenLanguageIsSelectedInWPAdmin()
    {
        wp_set_current_user(1);
        update_user_meta(1, 'pll_filter_content', 'en');

        // XXX This is not enough to initialize the admin mode for Polylang
        // set_current_screen( 'edit-post' );
        set_current_screen('edit.php');

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

    public function testPagination()
    {
        foreach (range(1, 15) as $number) {
            $number = str_pad($number, 2, '0', STR_PAD_LEFT);
            $lang = $number % 2 === 0 ? 'en' : 'fi';

            $post_id = self::factory()->post->create([
                'post_title' => "Post $number $lang",
            ]);

            pll_set_post_language($post_id, $lang);
        }

        $query = '
        query Posts {
            posts(first: 3, where: {language: FI, orderby: {field: TITLE, order: ASC}}) {
              pageInfo {
                  hasNextPage
                  endCursor
              }
              nodes {
                title
                language {
                    code
                }
              }
            }
         }
        ';

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $nodes = $data['data']['posts']['nodes'];

        $expected = [
            0 => [
                // Created in the setup()
                'title' => 'Finnish post',
                'language' => [
                    'code' => 'FI',
                ],
            ],
            1 => [
                'title' => 'Post 01 fi',
                'language' => [
                    'code' => 'FI',
                ],
            ],
            2 => [
                'title' => 'Post 03 fi',
                'language' => [
                    'code' => 'FI',
                ],
            ],
        ];

        $this->assertEquals($nodes, $expected);
        $this->assertEquals(
            $data['data']['posts']['pageInfo']['hasNextPage'],
            true
        );
        $cursor = $data['data']['posts']['pageInfo']['endCursor'];

        ////////////////////////////
        //         PAGE 2         //
        ////////////////////////////

        $query = "
        query Posts {
            posts(first: 3, after: \"$cursor\", where: {language: FI, orderby: {field: TITLE, order: ASC}}) {
              pageInfo {
                  hasNextPage
                  endCursor
              }
              nodes {
                title
                language {
                    code
                }
              }
            }
         }
        ";

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $nodes = $data['data']['posts']['nodes'];

        $expected = [
            0 => [
                'title' => 'Post 05 fi',
                'language' => [
                    'code' => 'FI',
                ],
            ],
            1 => [
                'title' => 'Post 07 fi',
                'language' => [
                    'code' => 'FI',
                ],
            ],
            2 => [
                'title' => 'Post 09 fi',
                'language' => [
                    'code' => 'FI',
                ],
            ],
        ];

        $this->assertEquals($nodes, $expected);
        $this->assertEquals(
            $data['data']['posts']['pageInfo']['hasNextPage'],
            true
        );
        $cursor = $data['data']['posts']['pageInfo']['endCursor'];

        ////////////////////////////
        //         PAGE 3         //
        ////////////////////////////

        $query = "
        query Posts {
            posts(first: 3, after: \"$cursor\", where: {language: FI, orderby: {field: TITLE, order: ASC}}) {
              pageInfo {
                  hasNextPage
                  endCursor
              }
              nodes {
                title
                language {
                    code
                }
              }
            }
         }
        ";

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $nodes = $data['data']['posts']['nodes'];

        $expected = [
            0 => [
                'title' => 'Post 11 fi',
                'language' => [
                    'code' => 'FI',
                ],
            ],
            1 => [
                'title' => 'Post 13 fi',
                'language' => [
                    'code' => 'FI',
                ],
            ],
            2 => [
                'title' => 'Post 15 fi',
                'language' => [
                    'code' => 'FI',
                ],
            ],
        ];

        // error_log(var_export($nodes, true));

        $this->assertEquals($nodes, $expected);
        $this->assertEquals(
            $data['data']['posts']['pageInfo']['hasNextPage'],
            false
        );
    }

    public function testCanDetectTranslatedFrontPage()
    {
        update_option('show_on_front', 'page');

        $fi_post_id = wp_insert_post([
            'post_title' => 'Finnish post version',
            'post_content' => '',
            'post_type' => 'page',
            'post_status' => 'publish',
        ]);
        pll_set_post_language($fi_post_id, 'fi');

        $en_post_id = wp_insert_post([
            'post_title' => 'English post version',
            'post_content' => '',
            'post_type' => 'page',
            'post_status' => 'publish',
        ]);
        pll_set_post_language($en_post_id, 'en');

        pll_save_post_translations([
            'en' => $en_post_id,
            'fi' => $fi_post_id,
        ]);

        update_option('page_on_front', $en_post_id);

        $query = "
        query Page {
            pageBy(pageId: $fi_post_id) {
                isFrontPage
            }
         }
        ";

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));

        $expected = ['isFrontPage' => true];

        $this->assertEquals($expected, $data['data']['pageBy']);

        // Test normal frontpage as well

        $query = "
        query Page {
            pageBy(pageId: $en_post_id) {
                isFrontPage
            }
         }
        ";

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));

        $expected = ['isFrontPage' => true];

        $this->assertEquals($expected, $data['data']['pageBy']);
    }
}
