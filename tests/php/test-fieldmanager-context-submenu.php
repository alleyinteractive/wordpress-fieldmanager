<?php

/**
 * Tests user-facing forms (e.g. not in the admin backend)
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

		// a basic input matcher.
		// see http://phpunit.de/manual/3.7/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.assertions.assertTag
		$h2_match = array(
			'tag' => 'h2',
			'content' => 'Tools Meta Fields',
		);
		$this->assertTag( $h2_match, $html );

		$nonce_matcher = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'hidden',
				'name' => "fieldmanager-{$name}-nonce",
			),
		);
		$this->assertTag( $nonce_matcher, $html );

		$name_matcher = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'text',
				'name' => $name . '[name]',
				'value' => '',
			),
		);
		$this->assertTag( $name_matcher, $html );

		$email_matcher = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'text',
				'name' => $name . '[email]',
				'value' => '',
			),
		);
		$this->assertTag( $email_matcher, $html );

		$remember_matcher = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'checkbox',
				'name' => $name . '[remember]',
				'value' => '1',
				'checked' => false,
			),
		);
		$this->assertTag( $remember_matcher, $html );

		$fruit_matcher = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'radio',
				'name' => $name . '[group][preferences]',
				'value' => 'Banana',
				'checked' => false,
			),
		);
		$this->assertTag( $fruit_matcher, $html );

		$this->build_post( $html, $name );
		$this->assertTrue( $context->save_submenu_data() );

		$processed_values = get_option( $name );

		$this->assertEquals( $_POST[ $name ]['email'], $processed_values['email'] );
		$this->assertEquals( $_POST[ $name ]['remember'], $processed_values['remember'] );
		$this->assertNotEquals( $_POST[ $name ]['name'], $processed_values['name'] );
		$this->assertEquals( $processed_values['name'], 'Austin Smith' );
		$this->assertEquals( $_POST[ $name ]['group']['preferences'], $processed_values['group']['preferences'] );
		$this->assertEquals( $processed_values['number'], 11 ); // changed in presave hook and sanitized.

		$processed_html = $this->get_html( $context, $name );

		unset( $fruit_matcher['attributes']['checked'] );
		$this->assertTag( $fruit_matcher, $processed_html );

		$remember_matcher['attributes']['checked'] = true;
		$this->assertTag( $remember_matcher, $processed_html );

		$fruit_matcher['attributes']['checked'] = true;
		$fruit_matcher['attributes']['value'] = 'Apple';
		$this->assertTag( $fruit_matcher, $processed_html );

		$success_match = array(
			'tag' => 'div',
			'attributes' => array(
				'class' => 'updated success',
			)
		);
		$this->assertTag( $success_match, $processed_html );

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