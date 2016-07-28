<?php

/**
 * Tests user-facing forms (e.g. not in the admin backend)
 */
class Test_Fieldmanager_Context_Form extends WP_UnitTestCase {

	private $processed_values = array();

	public function setUp() {
		parent::setUp();
		add_action( 'fm_form_test_form_save', array( $this, 'fm_form_test_form_save' ), 10, 3 );
		add_action( 'fm_form_test_form_presave', array( $this, 'fm_form_test_form_presave' ), 10, 2 );
		add_action( 'fm_form_test_form_values', array( $this, 'fm_form_test_form_values' ), 10, 2 );
		Fieldmanager_Field::$debug = TRUE;
	}

	/**
	 * End-to-end test of a frontend form
	 */
	public function test_form_context() {
		$context = $this->get_form_context();
		$html = $context->page_form_html();

		$v = $context->get_validator();
		$this->assertInstanceOf( 'Fieldmanager_Util_Validation', $v );
		$this->assertTrue( count( $v->get_rules() ) == 1 );

		$this->assertContains( 'name="userform[name]', $html );
		$this->assertContains( 'name="userform[email]', $html );
		$this->assertContains( 'name="userform[remember]', $html );
		$this->assertContains( 'name="userform[group][preferences]', $html );

		$this->build_post( $html );

		// Trash the context and reset internal cache.
		unset( $context );
		Fieldmanager_Context_Form::$forms = array();

		// Rebuild the context, which will trigger the save routine as if the page had just loaded.
		$context = $this->get_form_context();

		$this->assertEquals( $_POST['userform']['email'], $this->processed_values['email'] );
		$this->assertEquals( $_POST['userform']['remember'], $this->processed_values['remember'] );
		$this->assertNotEquals( $_POST['userform']['name'], $this->processed_values['name'] );
		$this->assertEquals( $this->processed_values['name'], 'Austin Smith' );
		$this->assertEquals( $_POST['userform']['group']['preferences'], $this->processed_values['group']['preferences'] );
		$this->assertEquals( $this->processed_values['number'], 11 ); // changed in presave hook and sanitized.
		$this->assertEquals( $context->messages, array( array( 'name', 'Test Message' ) ) );
		$this->assertEquals( $context->errors, array( array( 'name', 'Test Error' ) ) );
		$this->assertTrue( $context->has_errors() );

		$processed_html = $context->page_form_html(); // should have errors and messages
		$this->assertContains( 'class="fm-messages"', $processed_html );
		$this->assertContains( 'name="userform[name]', $processed_html );
		$this->assertContains( 'name="userform[email]', $processed_html );
		$this->assertContains( 'name="userform[remember]', $processed_html );
		$this->assertContains( 'name="userform[group][preferences]', $processed_html );
	}

	/**
	 * Test individual element rendering
	 */
	public function test_element_rendering() {
		$context = $this->get_form_context();
		$name_html = $context->element_html( 'name' );
		$this->assertRegExp( '/Name/', $name_html );
		$this->assertRegExp( '/<input/', $name_html );

		$preferences_html = $context->element_html( 'group', 'preferences' );
		$this->assertRegExp( '/preferences/', $preferences_html );
		$this->assertRegExp( '/<input/', $preferences_html );

		$group_html = $context->element_html( 'group' );
		$this->assertRegExp( '/preferences/', $group_html );
		$this->assertRegExp( '/Stupid Subgroup/', $group_html );
		$this->assertRegExp( '/<input/', $group_html );
	}

	/**
	 * Test nonce failure
	 * @expectedException FM_Exception
	 */
	public function test_nonce_failure() {
		$context = $this->get_form_context();
		$html = $context->page_form_html();
		$this->build_post( $html );
		$_POST['fieldmanager-userform-nonce'] = '';
		$context->save_page_form();
	}

	/**
	 * Test invalid element exception
	 * @expectedException FM_Developer_Exception
	 */
	public function test_invalid_element_exception() {
		$context = $this->get_form_context();
		$name_html = $context->element_html( 'not_valid_name' );
	}

	/**
	 * Test direct output of render_page_form
	 */
	public function test_render_page_form() {
		$this->expectOutputRegex( '/<form/' );
		$this->expectOutputRegex( '/Name/' );
		$context = $this->get_form_context();
		fm_the_form( 'test_form' );
	}

	/**
	 * Test direct output for elements
	 */
	public function test_render_element() {
		$context = $this->get_form_context();
		$this->expectOutputString( $context->element_html( 'name' ) );
		$context->element( 'name' );
	}

	/**
	 * Test direct output for errors
	 */
	public function test_render_errors() {
		$context = $this->get_form_context();
		$html = $context->page_form_html();
		$this->build_post( $html );
		$context->save_page_form();
		$this->expectOutputString( $context->get_the_errors() );
		$context->the_errors();
	}

	/**
	 * Test direct output for messages
	 */
	public function test_render_messages() {
		$context = $this->get_form_context();
		$html = $context->page_form_html();
		$this->build_post( $html );
		$context->save_page_form();
		$this->expectOutputString( $context->get_the_messages() );
		$context->the_messages();
	}

	/**
	 * Test direct output for form start
	 */
	public function test_render_form_start() {
		$context = $this->get_form_context();
		$this->expectOutputString( $context->get_form_start() );
		$context->form_start();
	}

	/**
	 * Test direct output for form end
	 */
	public function test_render_form_end() {
		$context = $this->get_form_context();
		$this->expectOutputString( $context->get_form_end() );
		$context->form_end();
	}

	/**
	 * Test direct output for form meta
	 */
	public function test_render_form_meta() {
		$context = $this->get_form_context();
		$this->expectOutputString( $context->get_form_meta() );
		$context->form_meta();
	}

	/**
	 * Test that nothing happens if you request a form that doesn't exist.
	 */
	public function test_null_form() {
		$this->assertEquals( null, fm_get_form( 'no-such-form' ) );
	}

	/**
	 * Build a form context for testing
	 */
	private function get_form_context() {
		$group = new Fieldmanager_Group( array(
			'name' => 'userform',
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
		return $group->add_form( 'test_form' );
	}

	/**
	 * Build submission data
	 */
	private function build_post( $html ) {
		$_POST = array(
			'fieldmanager-userform-nonce' => $this->extract_nonce( $html ),
			'fm-form-context' => 'test_form',
			'userform' => array(
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
	 * Extract the nonce value from a rendered form
	 */
	private function extract_nonce( $html ) {
		$nonce_matches = array();
		preg_match( '/name="fieldmanager-userform-nonce" value="(.*?)"/m', $html, $nonce_matches );
		return $nonce_matches[1];
	}

	/**
	 * Save callback, just sets the values so we can test them
	 */
	public function fm_form_test_form_save( $values, $old, $context ) {
		$this->processed_values = $values;
	}

	/**
	 * Presave callback, changes a value and adds messages and errors so we can test them
	 */
	public function fm_form_test_form_presave( $values, $context ) {
		$context->message( 'name', 'Test Message' );
		$context->error( 'name', 'Test Error' );
		$values['number'] = '11eleven';
		return $values;
	}

	/**
	 * Load callback, just returns some placeholder values
	 */
	public function fm_form_test_form_values( $values, $context ) {
		if ( !empty( $values ) ) {
			return $values;
		}
		return array(
			'name' => 'placeholder name',
			'email' => 'placeholder email',
			'group' => array(
				'preferences' => 'Banana',
			),
		);
	}

}