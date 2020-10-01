<?php

use GraphQLRelay\Relay;

// XXX: Can we autoload this somehow?
require_once __DIR__ . '/PolylangUnitTestCase.php';

class PostObjectMutationTest extends PolylangUnitTestCase
{
    public $admin_id = 1;

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

    public function testPostCreate()
    {
        wp_set_current_user($this->admin_id);

        $query = '
        mutation InsertPost {
            createPost(input: {clientMutationId: "1", title: "test", language: FI}) {
              clientMutationId
              post {
                title
                postId
                language {
                  code
                }
              }
            }
          }
        ';

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $post_id = $data['data']['createPost']['post']['postId'];
        $lang = pll_get_post_language($post_id, 'slug');
        $this->assertEquals('fi', $lang);
    }

    public function testPostCreateUsesDefaultLang()
    {
        self::set_default_language('fi');
        wp_set_current_user($this->admin_id);

        $query = '
        mutation InsertPost {
            createPost(input: {clientMutationId: "1", title: "test"}) {
              clientMutationId
              post {
                title
                postId
                language {
                  code
                }
              }
            }
          }
        ';

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $post_id = $data['data']['createPost']['post']['postId'];
        $lang = pll_get_post_language($post_id, 'slug');
        $this->assertEquals('fi', $lang);
    }

    public function testCanUpdateLanguage()
    {
        wp_set_current_user($this->admin_id);

        $post_id = wp_insert_post([
            'post_title' => 'Finnish post',
            'post_content' => '',
            'post_type' => 'post',
            'post_status' => 'publish',
        ]);
        pll_set_post_language($post_id, 'fi');

        $id = Relay::toGlobalId('post', $post_id);

        $query = "
        mutation UpdatePost {
            updatePost(input: {id: \"$id\", clientMutationId: \"1\", language: FR}) {
              clientMutationId
              post {
                title
                postId
                language {
                  code
                }
              }
            }
          }
        ";

        $data = do_graphql_request($query);
        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $post_id = $data['data']['updatePost']['post']['postId'];
        $lang = pll_get_post_language($post_id, 'slug');
        $this->assertEquals('fr', $lang);
    }

    public function testUnrelatedMuationDoesNotTouchLanguage()
    {
        wp_set_current_user($this->admin_id);

        $post_id = wp_insert_post([
            'post_title' => 'Finnish post',
            'post_content' => '',
            'post_type' => 'post',
            'post_status' => 'publish',
        ]);
        pll_set_post_language($post_id, 'fi');

        $id = Relay::toGlobalId('post', $post_id);

        $query = "
        mutation UpdatePost {
            updatePost(input: {id: \"$id\", clientMutationId: \"1\", title: \"new title\"}) {
              clientMutationId
              post {
                title
                postId
                language {
                  code
                }
              }
            }
          }
        ";

        $data = do_graphql_request($query);

        $this->assertArrayNotHasKey('errors', $data, print_r($data, true));
        $lang = pll_get_post_language($post_id, 'slug');
        $this->assertEquals('fi', $lang);
    }
}
