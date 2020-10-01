<?php

// XXX: Can we autoload this somehow?
require_once __DIR__ . '/PolylangUnitTestCase.php';

class MenuItemsQueriesTest extends PolylangUnitTestCase
{
    static function wpSetUpBeforeClass()
    {
        parent::wpSetUpBeforeClass();

        add_theme_support('nav_menu_locations');
        register_nav_menu('my-menu-location', 'My Menu');
        set_theme_mod('nav_menu_locations', ['my-menu-location' => 0]);

        self::create_language('en_US');
        self::set_default_language('en');
        self::create_language('fr_FR');

        self::initialize_polylang();
    }

    private function createMenuItem($menu_id, $options)
    {
        return wp_update_nav_menu_item($menu_id, 0, $options);
    }

    private function createMenuItems($slug, $lang, $count)
    {
        $menu_id = wp_create_nav_menu($slug);
        $menu_item_ids = [];
        $post_ids = [];

        // Create some Post menu items.
        for ($x = 1; $x <= $count; $x++) {
            $post_id = wp_insert_post([
                'post_title' => "Test post $$x in $lang",
                'post_content' => '',
                'post_type' => 'post',
                'post_status' => 'publish',
            ]);
            $post_ids[] = $post_id;

            pll_set_post_language($post_id, $lang);

            $menu_item_ids[] = $this->createMenuItem($menu_id, [
                'menu-item-title' => "Menu item {$x} in $lang",
                'menu-item-object' => 'post',
                'menu-item-object-id' => $post_id,
                'menu-item-status' => 'publish',
                'menu-item-type' => 'post_type',
            ]);
        }

        // Make sure menu items were created.
        $this->assertEquals($count, count($menu_item_ids));
        $this->assertEquals($count, count($post_ids));

        return [
            'menu_id' => $menu_id,
            'menu_item_ids' => $menu_item_ids,
            'post_ids' => $post_ids,
        ];
    }

    public function createMultiLanguageMenus()
    {
        $menu_en = $this->createMenuItems('my-test-menu-id-en', 'en', 5);
        $menu_fr = $this->createMenuItems('my-test-menu-id-fr', 'fr', 5);

        // Assign menu to location.
        set_theme_mod('nav_menu_locations', [
            'my-menu-location' => $menu_en['menu_id'], // the default en
            'my-menu-location___fr' => $menu_fr['menu_id'],
        ]);
    }

    public function testQueryDefaultLanguage()
    {
        $this->createMultiLanguageMenus();

        $query = '
		{
			menuItems( where: { location: MY_MENU_LOCATION } ) {
				nodes {
                    label
					databaseId
				}
			}
		}
		';

        $result = do_graphql_request($query);

        $this->assertEquals(5, count($result['data']['menuItems']['nodes']));
        $this->assertEquals(
            'Menu item 1 in en',
            $result['data']['menuItems']['nodes'][0]['label']
        );
    }

    public function testQueryExplicitlyDefaultLanguage()
    {
        $this->createMultiLanguageMenus();

        $query = '
		{
			menuItems( where: { location: MY_MENU_LOCATION, language: EN } ) {
				nodes {
                    label
					databaseId
				}
			}
		}
		';

        $result = do_graphql_request($query);

        $this->assertEquals(count($result['data']['menuItems']['nodes']), 5);
        $this->assertEquals(
            'Menu item 1 in en',
            $result['data']['menuItems']['nodes'][0]['label']
        );
    }

    public function testQueryInOtherLanguage()
    {
        $this->createMultiLanguageMenus();

        $query = '
		{
			menuItems( where: { location: MY_MENU_LOCATION, language: FR } ) {
				nodes {
                    label
					databaseId
				}
			}
		}
		';

        $result = do_graphql_request($query);

        $this->assertEquals(count($result['data']['menuItems']['nodes']), 5);
        $this->assertEquals(
            'Menu item 1 in fr',
            $result['data']['menuItems']['nodes'][0]['label']
        );
    }
}
