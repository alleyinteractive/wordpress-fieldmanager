<?php

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the Storable Context base.
 *
 * @group context
 */
class FieldmanagerContextStorableTest extends WP_UnitTestCase {
	public static function scalar_sanitize_data() {
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
	 * @param  mixed $test     Test cases.
	 * @param  mixed $expected Expected values.
	 */
	#[DataProvider( 'scalar_sanitize_data' )]
	public function test_sanitize_scalar_values( $test, $expected ) {
		$this->assertSame( $expected, \Fieldmanager_Context_Storable::sanitize_scalar_value( $test ) );
	}
}
