<?php

// XXX: Can we autoload this somehow?
require_once __DIR__ . '/PolylangUnitTestCase.php';

class TermObjectQueryTest extends PolylangUnitTestCase
{
    public $fi_term_id = null;
    public $en_term_id = null;

    static function wpSetUpBeforeClass()
    {
        parent::wpSetUpBeforeClass();

        self::set_default_language('en_US');
        self::create_language('en_US');
        self::create_language('fr_FR');
        self::create_language('fi');
        self::create_language('de_DE_formal');
        self::create_language('es_ES');

        self::initialize_polylang();
    }

    public function setUp(): void
    {
        parent::setUp();

        $term = wp_insert_term('entesttag', 'post_tag');
        pll_set_term_language($term['term_id'], 'en');
        $this->en_term_id = $term['term_id'];

        $term = wp_insert_term('fitesttag', 'post_tag');
        pll_set_term_language($term['term_id'], 'fi');
        $this->fi_term_id = $term['term_id'];
    }

    public function testListsTermsFromAllLanguages()
    {
        $query = '
        query Tags {
            tags {
              nodes {
                name
              }
            }
         }
        ';

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $nodes = $data['data']['tags']['nodes'];
        $expected = [
            //
            ['name' => 'entesttag'],
            ['name' => 'fitesttag'],
        ];

        $this->assertEquals($expected, $nodes);
    }

    public function testTermsHaveLanguageField()
    {
        $query = '
        query Tags {
            tags {
              nodes {
                name
                language {
                    name
                }
              }
            }
         }
        ';

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $nodes = $data['data']['tags']['nodes'];
        $expected = [
            [
                'name' => 'entesttag',
                'language' => [
                    'name' => 'English',
                ],
            ],
            [
                'name' => 'fitesttag',
                'language' => [
                    'name' => 'Suomi',
                ],
            ],
        ];

        $this->assertEquals($expected, $nodes);
    }

    public function testCanFilterByLanguage()
    {
        $query = '
        query Tags {
            tags(where: {language: FI}) {
              nodes {
                name
              }
            }
         }
        ';

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $nodes = $data['data']['tags']['nodes'];

        $expected = [['name' => 'fitesttag']];

        $this->assertEquals(1, count($nodes));
        $this->assertEquals($expected, $nodes);
    }

    public function testCanFetchTranslatedTermVersions()
    {
        pll_save_term_translations([
            'en' => $this->en_term_id,
            'fi' => $this->fi_term_id,
        ]);

        $query = "
        query Tags {
            tags {
                nodes {
                    name
                    translations {
                        name
                    }
                }
            }
         }
        ";

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));

        $expected = [
            [
                'name' => 'entesttag',
                'translations' => [
                    [
                        'name' => 'fitesttag',
                    ],
                ],
            ],
            [
                'name' => 'fitesttag',
                'translations' => [
                    [
                        'name' => 'entesttag',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $data['data']['tags']['nodes']);
    }

    public function testCanFetchTranslatedTermVersionsWithIds()
    {
        pll_save_term_translations([
            'en' => $this->en_term_id,
            'fi' => $this->fi_term_id,
        ]);

        $query = "
        query Tags {
            tags {
                nodes {
                    name
                    translations {
                        id
                    }
                }
            }
         }
        ";

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
    }

    public function testCanFetchSpecificTranslatedVersion()
    {
        pll_save_term_translations([
            'en' => $this->en_term_id,
            'fi' => $this->fi_term_id,
        ]);

        $query = "
        query Tags {
            tags {
                nodes {
                    name
                    translation(language: FI) {
                        name
                    }
                }
            }
         }
        ";

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));

        $expected = [
            [
                'name' => 'entesttag',
                'translation' => [
                    'name' => 'fitesttag',
                ],
            ],
            [
                'name' => 'fitesttag',
                'translation' => [
                    'name' => 'fitesttag',
                ],
            ],
        ];

        $this->assertEquals($expected, $data['data']['tags']['nodes']);
    }
}
