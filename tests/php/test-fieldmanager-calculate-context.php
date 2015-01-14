<?php
/**
 * Tests for calculating the Fieldmanager context of a request.
 */
class Test_Fieldmanager_Calculate_Context extends WP_UnitTestCase {

	/**
	 * Test context calculations for submenus.
	 */
	public function test_submenu_contexts() {
		$screen   = get_current_screen();
		$self     = isset( $_SERVER['PHP_SELF'] ) ? $_SERVER['PHP_SELF'] : null;
		$page     = isset( $_GET['page'] ) ? $_GET['page'] : null;
		$submenus = _fieldmanager_registry( 'submenus' );

		_fieldmanager_registry( 'submenus', array() );

		// Spoof is_admin().
		set_current_screen( 'dashboard-user' );

		// Submenu of a default WordPress menu.
		$options_submenu = rand_str();
		fm_register_submenu_page( $options_submenu, 'options-general.php', 'Options' );
		$_SERVER['PHP_SELF'] = '/options-general.php';
		$_GET['page'] = $options_submenu;
		$this->assertEquals( array( 'submenu', $options_submenu ), fm_calculate_context() );

		// Submenu of a custom menu.
		$custom_menu_submenu = rand_str();
		fm_register_submenu_page( $custom_menu_submenu, rand_str(), 'Custom' );
		$_SERVER['PHP_SELF'] = '/admin.php';
		$_GET['page'] = $custom_menu_submenu;
		$this->assertEquals( array( 'submenu', $custom_menu_submenu ), fm_calculate_context() );

		// Submenu that Fieldmanager didn't register.
		$_SERVER['PHP_SELF'] = '/themes.php';
		$_GET['page'] = rand_str();
		$this->assertEquals( array( null, null ), fm_calculate_context() );

		$GLOBALS['current_screen'] = $screen;
		$_SERVER['PHP_SELF']       = $self;
		$_GET['page']              = $page;
		_fieldmanager_registry( 'submenus', $submenus );
	}

}