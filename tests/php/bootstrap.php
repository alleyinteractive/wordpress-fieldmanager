<?php
/**
 * Bootstrap the testing environment
 * Uses wordpress tests (http://github.com/nb/wordpress-tests/) which uses PHPUnit
 * @package wordpress-plugin-tests
 *
 * Usage: change the below array to any plugin(s) you want activated during the tests
 *        value should be the path to the plugin relative to /wp-content/
 *
 * Note: Do note change the name of this file. PHPUnit will automatically fire this file when run.
 *
 */

/**
 * Determine where the WP test suite lives.
 *
 * Support for:
 * 1. `WP_DEVELOP_DIR` environment variable, which points to a checkout
 *   of the develop.svn.wordpress.org repository (this is recommended)
 * 2. `WP_TESTS_DIR` environment variable, which points to a checkout
 * 3. `WP_ROOT_DIR` environment variable, which points to a checkout
 * 4. Plugin installed inside of WordPress.org developer checkout
 * 5. Tests checked out to /tmp
 */
if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$_tests_dir = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} elseif ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	$_tests_dir = getenv( 'WP_TESTS_DIR' );
} elseif ( false !== getenv( 'WP_ROOT_DIR' ) ) {
	$_tests_dir = getenv( 'WP_ROOT_DIR' ) . '/tests/phpunit';
} elseif ( file_exists( '../../../../tests/phpunit/includes/bootstrap.php' ) ) {
	$_tests_dir = '../../../../tests/phpunit';
} elseif ( file_exists( '/tmp/wordpress-tests-lib/includes/bootstrap.php' ) ) {
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

// REST API helper classes.
require_once( __DIR__ . '/includes/wp-api/class-wp-test-spy-rest-server.php' );
require_once( __DIR__ . '/includes/wp-api/class-fieldmanager-rest-controller.php' );
