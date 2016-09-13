<?php
/**
 * Tests for functions in fieldmanager.php.
 */

class Test_Fieldmanager extends WP_UnitTestCase {
	/**
	 * @expectedException        WPDieException
	 * @expectedExceptionMessage test_fm_failed_validation_to_wp_die
	 */
	public function test_fm_failed_validation_to_wp_die() {
		fm_failed_validation_to_wp_die( __FUNCTION__ );
	}
}
