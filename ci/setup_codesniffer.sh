#!/bin/bash

# Install CodeSniffer for WordPress Coding Standards checks.
git clone https://github.com/squizlabs/PHP_CodeSniffer.git /tmp/wordpress/php-codesniffer

# Install WordPress Coding Standards.
git clone https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards.git /tmp/wordpress/wordpress-coding-standards

# Set install path for WordPress Coding Standards
# @link https://github.com/squizlabs/PHP_CodeSniffer/blob/4237c2fc98cc838730b76ee9cee316f99286a2a7/CodeSniffer.php#L1941
/tmp/wordpress/php-codesniffer/scripts/phpcs --config-set installed_paths /tmp/wordpress/wordpress-coding-standards

# After CodeSniffer install you should refresh your path.
phpenv rehash