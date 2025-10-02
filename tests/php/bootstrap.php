<?php
/**
 * Bootstrap the testing environment
 *
 * Uses {@see https://mantle.alley.com/testing/test-framework.html} for testing.
 *
 * Note: Do not change the name of this file. PHPUnit will automatically fire
 * this file when run.
 *
 * @package Fieldmanager
 */

\Mantle\Testing\manager()
	->loaded( static function () {
		require_once __DIR__ . '/../../fieldmanager.php';
		require_once __DIR__ . '/Includes/FieldmanagerAssetsUnitTestCase.php';
		require_once __DIR__ . '/Includes/FieldmanagerOptionsMock.php';
	} )
	->install();

/**
 * Is the current version of WordPress at least ... ?
 *
 * @param  float $min_version Minimum version required, e.g. 3.9.
 * @return bool True if it is, false if it isn't.
 */
function _fm_phpunit_is_wp_at_least( $min_version ) {
	global $wp_version;
	return floatval( $wp_version ) >= $min_version;
}
