It's easy to run Fieldmanager's tests on your machine.

1. Install PHPUnit
   http://www.phpunit.de/manual/current/en/installation.html#installation.pear

2. Install the WordPress test toolkit (don't add it to Git)
   cd tests/php
   svn co http://unit-test.svn.wordpress.org/trunk wp-unit-test

3. Copy the sample config in the test toolkit
   cp wp-unit-test/wp-tests-config-sample.php wp-unit-test/wp-tests-config.php

4. Edit the new config file to use a test database. No special configuration is
   required for Fieldmanager.

5. cd tests/php (from Fieldmanager's root directory) and run the tests:
   phpunit
