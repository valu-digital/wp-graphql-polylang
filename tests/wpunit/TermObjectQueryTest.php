<?php

// XXX: Can we autoload this somehow?
require_once __DIR__ . '/PolylangUnitTestCase.php';

class TermObjectQueryTest extends PolylangUnitTestCase
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

        $term = wp_insert_term( 'entesttag', 'post_tag' );
        pll_set_term_language($term['term_id'], 'en');

        $term = wp_insert_term( 'fitesttag', 'post_tag' );
        pll_set_term_language($term['term_id'], 'fi');
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
                ]
            ],
            [
                'name' => 'fitesttag',
                'language' => [
                    'name' => 'Suomi',
                ]
            ],
        ];

        $this->assertEquals($expected, $nodes);
    }



}
