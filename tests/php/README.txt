It's easy to run Fieldmanager's tests on your machine.

1. Install PHPUnit

   http://www.phpunit.de/manual/current/en/installation.html#installation.pear

2. Install the WordPress test toolkit somewhere else on your machine:

   svn co http://unit-test.svn.wordpress.org/trunk wp-unit-test

3. Copy (or link) this plugin to the plugins/ directory of
   wp-unit-tests/plugins.

4. Edit the config file to use a test database. No special configuration is
   required for Fieldmanager.

5. Run the tests, with WP_TESTS_DIR set to the root directory of wp-unit-tests
   that you created in step 2.
	
   WP_TESTS_DIR=/path/to/wp-unit-test phpunit