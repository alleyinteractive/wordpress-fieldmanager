<?php

/**
 * Tests the Storable Context base.
 *
 * @group context
 */
class Test_Fieldmanager_Context_Storable extends WP_UnitTestCase {
	public function scalar_sanitize_data() {
		return array(
			array( 1, '1' ),
			array( 0, '0' ),
			array( true, '1' ),
			array( false, '' ),
			array( 'abc', 'abc' ),
			array( array(), array() ),
			array( array( 1, 2, 3 ), array( 1, 2, 3 ) ),
			array( 1.234, '1.234' ),
			array( null, null ),
			array( '', '' ),
		);
	}

	/**
	 * @dataProvider scalar_sanitize_data
	 * @param  mixed $test     Test cases.
	 * @param  mixed $expected Expected values.
	 */
	public function test_sanitize_scalar_values( $test, $expected ) {
		$this->assertSame( $expected, \Fieldmanager_Context_Storable::sanitize_scalar_value( $test ) );
	}
}
