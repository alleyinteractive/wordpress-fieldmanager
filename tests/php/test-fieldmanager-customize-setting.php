<?php
/**
 * @group customize
 */
class Test_Fieldmanager_Customize_Setting extends Fieldmanager_Customizer_UnitTestCase {
	protected $field, $context;

	function setUp() {
		parent::setUp();
		$this->field   = new Fieldmanager_TextField( array( 'name' => 'foo' ) );
		$this->context = new Fieldmanager_Context_Customizer( array(), $this->field );
	}

	function test_construction() {
		$actual = new Fieldmanager_Customize_Setting( $this->manager, $this->field->name, array(
			'context' => $this->context,
		) );

		$this->assertInstanceOf( 'Fieldmanager_Customize_Setting', $actual );
		$this->assertSame( $this->field->default_value, $actual->default );
		$this->assertSame( array( $this->context, 'sanitize_callback' ), $actual->sanitize_callback );
		$this->assertSame( 'option', $actual->type );
	}

	/**
	 * Test that properties set by default by Fieldmanager_Customize_Setting can be overridden.
	 */
	function test_parent_construction() {
		$actual = new Fieldmanager_Customize_Setting( $this->manager, $this->field->name, array(
			'context' => $this->context,
			'default' => 123456,
			'sanitize_callback' => 'absint',
			'type' => 'bar',
		) );

		$this->assertInstanceOf( 'Fieldmanager_Customize_Setting', $actual );
		$this->assertSame( 123456, $actual->default );
		$this->assertSame( 'absint', $actual->sanitize_callback );
		$this->assertSame( 'bar', $actual->type );
	}

	/**
	 * @expectedException FM_Developer_Exception
	 */
	function test_invalid_construction() {
		new Fieldmanager_Customize_Setting( $this->manager, $this->field->name, array() );
	}

	function test_preview_filter() {
		global $wp_current_filter;
		$_current_filter = $wp_current_filter;

		$original = rand_str();
		$preview  = rand_str();

		update_option( $this->field->name, $original );

		// Spoof a POSTed value so the manager thinks this is a preview.
		$this->manager->set_post_value( $this->field->name, $preview );

		$setting = new Fieldmanager_Customize_Setting( $this->manager, $this->field->name, array(
			'context' => $this->context,
		) );

		// Verify the preview filter was added.
		$this->assertNotFalse( $setting->preview() );
		// Verify that the option value is filtered to return the previewed value.
		$this->assertSame( $preview, $setting->_preview_filter( $original ) );

		// Spoof the current filter.
		$wp_current_filter = array( "customize_sanitize_{$this->field->name}" );

		// Verify that the option value is not filtered with the previewed value.
		$this->assertSame( $original, $setting->_preview_filter( $original ) );

		$wp_current_filter = $_current_filter;
	}
}
