<?php

// XXX: Can we autoload this somehow?
require_once __DIR__ . '/PolylangUnitTestCase.php';

class PostPreviewQueryTest extends PolylangUnitTestCase
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

        self::initialize_polylang();
    }

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCanHaveLanguageField()
    {
        $post_id = $this->factory()->post->create([
            'post_title' => 'Original post',
            'post_content' => '',
            'post_type' => 'post',
            'post_status' => 'publish',
        ]);
        pll_set_post_language($post_id, 'fi');

        $preview_id = $this->factory()->post->create([
            'post_title' => 'Preview post',
            'post_content' => 'Preview Content',
            'post_type' => 'revision',
            'post_status' => 'inherit',
            'post_parent' => $post_id,
        ]);
        // Note: The language of the preview post is not set at all in a real
        // wp instance so we won't be setting it here neighter. wpgql-polylang
        // must read the language from the original.

        $query = "
        query Preview {
            post(id: \"$post_id\", idType: DATABASE_ID, asPreview: true) {
                title
                language {
                    code
                    name
                    slug
                }
            }
         }
        ";

        wp_set_current_user(1);
        $result = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $result, print_r($result, true));
        $this->assertEquals($result['data']['post']['language']['code'], 'FI');
        $this->assertEquals(
            $result['data']['post']['language']['name'],
            'Suomi'
        );
        $this->assertEquals($result['data']['post']['language']['slug'], 'fi');
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

        $preview_id = $this->factory()->post->create([
            'post_title' => 'Preview post',
            'post_content' => 'Preview Content',
            'post_type' => 'revision',
            'post_status' => 'inherit',
            'post_parent' => $en_post_id,
        ]);

        $query = "
        query Preview {
            post(id: \"$en_post_id\", idType: DATABASE_ID, asPreview: true) {
                title
                translations {
                    title
                }
            }
         }
        ";

        wp_set_current_user(1);
        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));

        $expected = [
            'title' => 'Preview post',
            'translations' => [
                [
                    'title' => 'Finnish post version',
                ],
            ],
        ];

        $this->assertEquals($expected, $data['data']['post']);
    }
}
