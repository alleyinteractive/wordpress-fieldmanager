<?php
/**
 * Tests for functions in fieldmanager.php.
 */

class Test_Fieldmanager extends WP_UnitTestCase {
	public function test_fm_failed_validation_to_wp_die() {
		$rand_str = rand_str();

		try {
			fm_failed_validation_to_wp_die( $rand_str );
		} catch ( WPDieException $e ) {
			$this->assertContains( $rand_str, $e->getMessage() );
		}
	}
}
