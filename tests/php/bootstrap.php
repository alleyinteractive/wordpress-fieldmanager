<?php
/**
 * Bootstrap the testing environment
 *
 * Uses wordpress tests (http://github.com/nb/wordpress-tests/) which uses
 * PHPUnit.
 *
 * Note: Do note change the name of this file. PHPUnit will automatically fire
 * this file when run.
 *
 * @package Fieldmanager
 */

$_tests_dir = getenv('WP_TESTS_DIR');
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../../fieldmanager.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

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

// Load custom `UnitTestCase` classes
require_once( __DIR__ . '/includes/class-fieldmanager-assets-unit-test-case.php' );
