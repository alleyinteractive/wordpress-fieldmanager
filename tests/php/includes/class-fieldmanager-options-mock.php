<?php
/**
 * Concrete class implementing Fieldmanager_Options to be used in lieu of
 * `\PHPUnit\Framework\TestCase::getMockForAbstractClass()`, which isn't
 * compatible with PHP 8. This class can be removed when Fieldmanager tests use
 * PHPUnit 9.3+, which supports PHP 8.
 *
 * @see https://github.com/sebastianbergmann/phpunit/pull/4374
 */
class Fieldmanager_Options_Mock extends Fieldmanager_Options {}
