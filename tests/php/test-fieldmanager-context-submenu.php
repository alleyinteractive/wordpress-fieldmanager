<?php

/**
 * Tests submenu forms/pages
 */
class Test_Fieldmanager_Context_Submenu extends WP_UnitTestCase {

	private $processed_values = array();

	public function setUp() {
		parent::setUp();
		add_filter( 'fm_submenu_presave_data', array( $this, 'presave_alter_number' ), 10, 2 );
	}

	public function tearDown() {
		remove_filter( 'fm_submenu_presave_data', array( $this, 'presave_alter_number' ), 10, 2 );
		parent::tearDown();
	}

	/**
	 * End-to-end test of a submenu page
	 */
	public function test_submenu_context() {
		$name = 'tools_meta_fields';
		fm_register_submenu_page( $name, 'tools.php', 'Tools Meta Fields' );
		$context = $this->get_context( $name );
		$html = $this->get_html( $context, $name );

		$this->assertFalse( $context->is_network_submenu );

		$this->assertContains( '<h2>Tools Meta Fields</h2>', $html );
		$this->assertRegExp( '/<input type="hidden"[^>]+name="fieldmanager-' . $name . '-nonce"/', $html );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="' . $name . '\[name\]"[^>]+value=""/', $html );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="' . $name . '\[email\]"[^>]+value=""/', $html );

		$remember = preg_match( '/<input[^>]+type="checkbox"[^>]+name="' . $name . '\[remember\]"[^>]+value="1"[^>]+>/', $html, $matches );
		$this->assertEquals( 1, $remember );
		$this->assertFalse( strpos( $matches[0], 'checked' ) );

		$fruit = preg_match( '/<input[^>]+type="radio"[^>]+value="Banana"[^>]+name="' . $name . '\[group\]\[preferences\]"[^>]+>/', $html, $matches );
		$this->assertEquals( 1, $fruit );
		$this->assertFalse( strpos( $matches[0], 'checked' ) );

		$this->build_post( $html, $name );
		$this->assertTrue( $context->save_submenu_data() );

		$processed_values = get_option( $name );
		$this->assert_processed_values( $processed_values, $name );

		$processed_html = $this->get_html( $context, $name );

		$remember = preg_match( '/<input[^>]+type="checkbox"[^>]+name="' . $name . '\[remember\]"[^>]+value="1"[^>]+>/', $processed_html, $matches );
		$this->assertEquals( 1, $remember );
		$this->assertContains( 'checked', $matches[0] );

		$banana = preg_match( '/<input[^>]+type="radio"[^>]+value="Banana"[^>]+name="' . $name . '\[group\]\[preferences\]"[^>]+>/', $processed_html, $matches );
		$this->assertEquals( 1, $banana );
		$this->assertFalse( strpos( $matches[0], 'checked' ) );

		$apple = preg_match( '/<input[^>]+type="radio"[^>]+value="Apple"[^>]+name="' . $name . '\[group\]\[preferences\]"[^>]+>/', $processed_html, $matches );
		$this->assertEquals( 1, $apple );
		$this->assertContains( 'checked', $matches[0] );

		$this->assertContains( '<div class="updated success">', $processed_html );

	}

	/**
	 * Test a Network Settings submenu.
	 *
	 * Similar to Test_Fieldmanager_Context_Submenu::test_submenu_context(), but
	 * skips verifying the HTML and checks that a non-site option was not set.
	 */
	public function test_network_submenu() {
		$name = 'network_meta_fields';
		fm_register_submenu_page( $name, 'settings.php', 'Network Submenu Meta Fields' );

		if ( ! is_multisite() ) {
			// We should be stopped after instantiating the Fieldmanager_Context_Submenu.
			$this->setExpectedException( 'FM_Developer_Exception' );
		}
		$context = $this->get_context( $name );

		$this->assertTrue( $context->is_network_submenu );

		$html = $this->get_html( $context, $name );
		$this->build_post( $html, $name );
		$this->assertTrue( $context->save_submenu_data() );

		$this->assert_processed_values( get_site_option( $name ), $name );
		$this->assertFalse( get_option( $name ) );
	}

	/**
	 * Test nonce failure
	 * @expectedException FM_Exception
	 */
	public function test_nonce_failure() {
		$name = 'edit_meta_fields';
		fm_register_submenu_page( $name, 'edit.php', 'Edit Meta Fields' );
		$context = $this->get_context( $name );
		$html = $this->get_html( $context, $name );

		$this->build_post( $html, $name );
		$_POST['fieldmanager-edit_meta_fields-nonce'] = '';
		$context->save_submenu_data();
	}

	/**
	 * Build a html from the default context and fields.
	 *
	 * @param  object $context FM Context.
	 */
	private function get_html( $context ) {
		ob_start();
		$context->render_submenu_page();
		return ob_get_clean();
	}

	/**
	 * Build a fieldmanager context from the default fields.
	 *
	 * @param  string $name The FM name.
	 */
	private function get_context( $name ) {
		$fields = $this->get_fields( $name );
		$fields->activate_submenu_page();
		return _fieldmanager_registry( 'active_submenu' );
	}

	/**
	 * Build a fieldmanager field for testing.
	 *
	 * @param  string $name The FM name.
	 */
	private function get_fields( $name ) {
		return new Fieldmanager_Group( array(
			'name' => $name,
			'children' => array(
				'name' => new Fieldmanager_Textfield( 'Name', array(
					'validation_rules' => array( 'required' => true ),
				) ),
				'email' => new Fieldmanager_Textfield( 'Email' ),
				'remember' => new Fieldmanager_Checkbox( 'Remember Me' ),
				'number' => new Fieldmanager_Textfield( 'Number', array(
					'sanitize' => function( $val ) {
						return intval( $val );
					}
				) ),
				'group' => new Fieldmanager_Group( 'Stupid Subgroup', array(
					'children' => array(
						'preferences' => new Fieldmanager_Radios( 'Food', array(
							'options' => array( 'Apple', 'Banana', 'Orange' ),
						) ),
					)
				) ),
			),
		) );
	}

	/**
	 * Build submission data.
	 *
	 * @param  string $html Rendered page HTML.
	 * @param  string $name The FM name.
	 */
	private function build_post( $html, $name ) {
		$_GET['page'] = $name;
		$_GET['msg'] = 'success';
		$_POST = array(
			"fieldmanager-{$name}-nonce" => $this->extract_nonce( $html, $name ),
			'fm-form-context' => 'test_form',
			$name => array(
				'email' => 'test@example.com',
				'name' => 'Austin Smith<script type="text/javascript">alert("HACKED")</script>', // should get auto-stripped
				'remember' => 1,
				'number' => '7even', // will become 7 due to above sanitizer
				'group' => array(
					'preferences' => 'Apple',
				),
			),
		);
	}

	/**
	 * Assert that the processed-and-saved option values match those in the POST.
	 *
	 * @see Test_Fieldmanager_Context_Submenu::build_post().
	 *
	 * @param array $value The option saved by Fieldmanager_Context_Submenu::save_submenu_data().
	 * @param string $name The Fieldmanager name
	 */
	private function assert_processed_values( $values, $name ) {
		$this->assertEquals( $_POST[ $name ]['email'], $values['email'] );
		$this->assertEquals( $_POST[ $name ]['remember'], $values['remember'] );
		$this->assertNotEquals( $_POST[ $name ]['name'], $values['name'] );
		$this->assertEquals( $values['name'], 'Austin Smith' );
		$this->assertEquals( $_POST[ $name ]['group']['preferences'], $values['group']['preferences'] );
		$this->assertEquals( $values['number'], 11 ); // changed in presave hook and sanitized.
	}

	/**
	 * Extract the nonce value from a rendered form.
	 *
	 * @param  string $html Rendered page HTML.
	 * @param  string $name The FM name.
	 */
	private function extract_nonce( $html, $name ) {
		$nonce_matches = array();
		preg_match( "/name=\"fieldmanager-{$name}-nonce\" value=\"(.*?)\"/m", $html, $nonce_matches );
		return $nonce_matches[1];
	}

	/**
	 * Test altering a field in the presave action.
	 *
	 * @param  array $values Values being saved.
	 * @param  object $context FM Context.
	 * @return array
	 */
	public function presave_alter_number( $values, $context ) {
		$values['number'] = 11;
		return $values;
	}

}