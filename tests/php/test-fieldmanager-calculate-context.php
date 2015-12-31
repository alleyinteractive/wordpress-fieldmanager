<?php
/**
 * Tests for calculating the Fieldmanager context of a request.
 *
 * @group context
 */
class Test_Fieldmanager_Calculate_Context extends WP_UnitTestCase {
	protected $screen, $self, $get, $submenus;

	public function setUp() {
		$this->screen    = get_current_screen();
		$this->self      = isset( $_SERVER['PHP_SELF'] ) ? $_SERVER['PHP_SELF'] : null;
		$this->get       = $_GET;
		$this->submenus  = _fieldmanager_registry( 'submenus' );

		_fieldmanager_registry( 'submenus', array() );

		// Spoof is_admin().
		set_current_screen( 'dashboard-user' );
	}

	public function tearDown( $value = null ) {
		$GLOBALS['current_screen'] = $this->screen;
		$_SERVER['PHP_SELF']       = $this->self;
		$_GET                      = $this->get;
		_fieldmanager_registry( 'submenus', $this->submenus );
	}

	/**
	 * Provide data for test_submenu_contexts.
	 *
	 * @see https://phpunit.de/manual/4.7/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers
	 *
	 * @return array
	 */
	public function submenu_context_args() {
		return array(
			// Core WordPress Menus
			array( 'index.php' ),
			array( 'edit.php' ),
			array( 'upload.php' ),
			array( 'edit-comments.php' ),
			array( 'themes.php' ),
			array( 'plugins.php' ),
			array( 'users.php' ),
			array( 'tools.php' ),
			array( 'options-general.php' ),

			// Submenu with another query arg
			array( 'edit.php?post_type=page' ),

			// Submenu of a custom menu.
			array( rand_str(), '/admin.php' ),
		);
	}

	/**
	 * Test context calculations for submenus.
	 *
	 * @dataProvider submenu_context_args
	 */
	public function test_submenu_contexts( $parent, $php_self = null ) {
		$submenu = rand_str();
		fm_register_submenu_page( $submenu, $parent, 'Submenu Page' );
		$_SERVER['PHP_SELF'] = $php_self ? $php_self : '/' . parse_url( $parent, PHP_URL_PATH );
		$query = parse_url( $parent, PHP_URL_QUERY );
		if ( ! empty( $query ) ) {
			parse_str( $query, $_GET );
		}
		$_GET['page'] = $submenu;
		$this->assertEquals( array( 'submenu', $submenu ), fm_calculate_context() );
		$this->_context_action_assertions( 'submenu', $submenu );
	}

	/**
	 * Test a submenu that Fieldmanager didn't register.
	 */
	public function test_non_fm_submenu() {
		$_SERVER['PHP_SELF'] = '/themes.php';
		$_GET['page'] = rand_str();
		$this->assertEquals( array( null, null ), fm_calculate_context() );
		$this->_context_action_assertions( null, null );
	}

	protected function _context_action_assertions( $context, $type ) {
		$a = new MockAction();

		if ( $context ) {
			add_action( "fm_{$context}", array( &$a, 'action' ) );
		}
		if ( $type ) {
			add_action( "fm_{$context}_{$type}", array( &$a, 'action' ) );
		}

		fm_get_context( true );
		fm_trigger_context_action();

		if ( $type ) {
			// only two events occurred for the hook
			$this->assertEquals( 2, $a->get_call_count() );
			// only our hooks were called
			$this->assertEquals( array( "fm_{$context}_{$type}", "fm_{$context}" ), $a->get_tags() );
			// The $type should have been passed as args
			$this->assertEquals( array( array( $type ), array( $type ) ), $a->get_args() );
		} elseif ( $context ) {
			// only one event occurred for the hook
			$this->assertEquals( 1, $a->get_call_count() );
			// only our hook was called
			$this->assertEquals( array( "fm_{$context}" ), $a->get_tags() );
			// null should have been passed as an arg
			$this->assertEquals( array( null ), $a->get_args() );
		} else {
			// No event should have fired
			$this->assertEquals( 0, $a->get_call_count() );
		}
	}
}