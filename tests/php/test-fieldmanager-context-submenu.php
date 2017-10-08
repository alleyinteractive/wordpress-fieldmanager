<?php

/**
 * Tests submenu forms/pages
 *
 * @group context
 * @group submenu
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

		$this->assertContains( '<h1>Tools Meta Fields</h1>', $html );
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

		$this->assertEquals( $_POST[ $name ]['email'], $processed_values['email'] );
		$this->assertEquals( $_POST[ $name ]['remember'], $processed_values['remember'] );
		$this->assertNotEquals( $_POST[ $name ]['name'], $processed_values['name'] );
		$this->assertEquals( $processed_values['name'], 'Austin "Smith"' );
		$this->assertEquals( $_POST[ $name ]['group']['preferences'], $processed_values['group']['preferences'] );
		$this->assertEquals( $processed_values['number'], 11 ); // changed in presave hook and sanitized.

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
	 * Test nonce failure
	 * @expectedException FM_Exception
	 */
	public function test_nonce_failure() {
		$name = 'edit_meta_fields';
		fm_register_submenu_page( $name, 'edit.php', 'Edit Meta Fields' );
		$context = $this->get_context( $name );
		$html = $this->get_html( $context, $name );

		$this->build_post( $html, $name );
		$_GET['page'] = $name;
		$_POST["fieldmanager-{$name}-nonce"] = 'abc123';
		$context->handle_submenu_save();
	}

	public function test_urls() {
		$current_user = get_current_user_id();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		// Test URL generation with a normal parent, tools.php
		$name_1 = rand_str();
		fm_register_submenu_page( $name_1, 'tools.php', 'Testing URLs' );
		$context_1 = $this->get_context( $name_1 );

		// Test with a parent with an additional query arg
		$name_2 = rand_str();
		fm_register_submenu_page( $name_2, 'edit.php?post_type=page', 'Testing URLs' );
		$context_2 = $this->get_context( $name_2 );

		// Test with a null parent
		$name_3 = rand_str();
		fm_register_submenu_page( $name_3, null, 'Testing URLs' );
		$context_3 = $this->get_context( $name_3 );

		// Test with a parent slug
		$name_4 = rand_str();
		$parent = rand_str();
		add_submenu_page( null, 'Test parent', 'Test parent', 'manage_options', $parent );
		fm_register_submenu_page( $name_4, $parent, 'Testing URLs' );
		$context_4 = $this->get_context( $name_4 );

		wp_set_current_user( $current_user );

		// We're running the assertions at the end so we can be guaranteed that
		// we set the current user back.
		$this->assertEquals( admin_url( 'tools.php?page=' . $name_1 ), $context_1->url() );
		$this->assertEquals( admin_url( 'edit.php?post_type=page&page=' . $name_2 ), $context_2->url() );
		$this->assertEquals( admin_url( 'admin.php?page=' . $name_3 ), $context_3->url() );
		$this->assertEquals( admin_url( 'admin.php?page=' . $name_4 ), $context_4->url() );
	}

	public function test_skip_save() {
		$name = 'skip_save';
		fm_register_submenu_page( $name, 'tools.php', 'Skip Save Fields' );
		// Should save the first time
		$context = $this->get_context( 'skip_save' );
		$data = array(
			'name'      => 'Foo',
			'email'     => 'foo@alleyinteractive.com',
			'remember'  => true,
			'number'    => 11,
			'group'     => array( 'preferences' => '' ),
		);
		$this->assertTrue( $context->save_submenu_data( $data ) );
		$this->assertEquals( $data, get_option( 'skip_save' ) );
		// Shouldn't save the second time
		$context->fm->skip_save = true;
		delete_option( 'skip_save' );
		$this->assertFalse( get_option( 'skip_save' ) );
		$this->assertTrue( $context->save_submenu_data( $data ) );
		$this->assertFalse( get_option( 'skip_save' ) );
		// Permit saving the group, but not an individual field
		$context->fm->skip_save = false;
		$context->fm->children['name']->skip_save = true;
		$this->assertTrue( $context->save_submenu_data( $data ) );
		$option = get_option( 'skip_save' );
		$this->assertFalse( isset( $option['name'] ) );
		$this->assertEquals( 'foo@alleyinteractive.com', $option['email'] );
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
				'name' => 'Austin \\"Smith\\"<script type="text/javascript">alert(/HACKED/)</script>', // both the script and slashes should get auto-stripped
				'remember' => 1,
				'number' => '7even', // will become 7 due to above sanitizer
				'group' => array(
					'preferences' => 'Apple',
				),
			),
		);
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

	public function test_updated_message() {
		$name = 'message_customization';
		$updated_message = rand_str();
		fm_register_submenu_page( $name, 'tools.php', 'Message Customization' );
		$context = $this->get_context( $name );
		$context->updated_message = $updated_message;
		$html = $this->get_html( $context, $name );
		$this->build_post( $html, $name );
		$this->assertContains( "<div class=\"updated success\"><p>{$updated_message}</p></div>", $this->get_html( $context, $name ) );
	}

}
