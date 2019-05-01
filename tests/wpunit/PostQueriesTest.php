<?php

class PostQueriesTest extends \Codeception\TestCase\WPTestCase
{
    public function setUp()
    {
        // before
        parent::setUp();
    }

    public function tearDown()
    {
        // your tear down methods here

        // then
        parent::tearDown();
    }

    // tests
    public function testDingDong()
    {
        $post_data = array(
            'post_title' => 'Test regex',
            'post_content' => 'sadfdsa',
            'post_type' => 'post',
        );
        $post_id = wp_insert_post($post_data);

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
        error_log(print_r($data, true));

        $this->assertEquals('foo', 'foo');
    }
}
