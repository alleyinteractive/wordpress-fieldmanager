<?php
/**
 * @group customize
 */
class Test_Fieldmanager_Customize_Control extends Fieldmanager_Customize_UnitTestCase {
	protected $mock_context;

	function setUp() {
		parent::setUp();

		$this->mock_context = $this->getMockBuilder( 'Fieldmanager_Context_Customize' )
			->disableOriginalConstructor()
			->getMock();
	}

	function test_parent_construction() {
		$priority = 99;

		$actual = new Fieldmanager_Customize_Control( $this->manager, 'foo', array(
			'context' => $this->mock_context,
			'priority' => $priority,
		) );

		$this->assertSame( $priority, $actual->priority );
	}

	/**
	 * @expectedException FM_Developer_Exception
	 */
	function test_invalid_construction() {
		new Fieldmanager_Customize_Control( $this->manager, 'foo', array() );
	}

	function test_type() {
		$control = new Fieldmanager_Customize_Control( $this->manager, rand_str(), array( 'context' => $this->mock_context ) );
		$this->assertSame( 'fieldmanager', $control->type );
	}

	/**
	 * Tests for scripts registered with wp_register_script().
	 */
	function test_register_scripts() {
		$before = wp_scripts()->registered;

		$control = new Fieldmanager_Customize_Control( $this->manager, rand_str(), array( 'context' => $this->mock_context ) );
		$control->enqueue();

		$after = wp_scripts()->registered;
		$this->assertSame( 1, ( count( $after ) - count( $before ) ) );
	}

	function test_render_content() {
		$name        = rand_str();
		$value       = rand_str();
		$label       = rand_str();
		$description = rand_str();

		$context = new Fieldmanager_Context_Customize( array(
			// Bypass capability checks.
			'section_args' => array( 'capability' => 'exist' ),
			'setting_args' => array( 'capability' => 'exist' ),
			'control_args' => array( 'label' => $label, 'description' => $description ),
		), new Fieldmanager_Textfield( array( 'name' => $name ) ) );

		update_option( $name, $value );
		$this->register();

		$actual = $this->manager->get_control( $name )->get_content();
		$this->assertContains( 'fm-element', $actual );
		$this->assertContains( sprintf( 'name="%s"', $name ), $actual );
		$this->assertRegExp( '#<([^ ]+)[^>]*?>' . $label . '</\1>#', $actual );
		$this->assertRegExp( '#<([^ ]+)[^>]*?>' . $description . '</\1>#', $actual );
		// Slip in a test that the option value is also used.
		$this->assertContains( sprintf( 'value="%s"', $value ), $actual );
	}
}
