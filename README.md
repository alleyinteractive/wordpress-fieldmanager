# Fieldmanager

Fieldmanager is a comprehensive toolkit for building forms, metaboxes, and custom admin screens for WordPress.

[![Build Status](https://travis-ci.org/alleyinteractive/wordpress-fieldmanager.svg?branch=master)](https://travis-ci.org/alleyinteractive/wordpress-fieldmanager)

## Using Fieldmanager in your WordPress Project

Fieldmanager is a powerful library which can make the development of sophisticated features a breeze. To get started, simply [download](#downloads-and-versioning) and install this plugin into your plugins directory and activate it on the plugins screen. To learn how to use Fieldmanager's API, visit the project's official website at [Fieldmanager.org](http://fieldmanager.org). There is also [a demo plugin](https://github.com/alleyinteractive/fieldmanager-demos) which illustrates a lot of what you can do with Fieldmanager.

## Downloads and Versioning.

You can view [Fieldmanager's official releases here](https://github.com/alleyinteractive/wordpress-fieldmanager/releases).

The `master` branch on GitHub is the "bleeding edge" release. As of 1.0, Fieldmanager will maintain a typical release cycle, with alpha, beta, and RC releases, and we hope to move through "minor" versions pretty quickly. While we encourage everyone to develop with and test on early releases and help us find the bugs, stable releases are recommended for production.

## Contributing to Development

Development of Fieldmanager happens on [Github](http://github.com/alleyinteractive/wordpress-fieldmanager). Bugs with Fieldmanager should be addressed in the Github issue queue, and enhancements or bug fixes should be submitted as pull requests, which are always welcome.

## Generating Documentation

To build Fieldmanager's API documentation, the latest version of which is available at [api.fieldmanager.org](http://api.fieldmanager.org), you need [apigen](http://apigen.org/) installed. Once you've got that, you can generate the entire documentation tree as follows:

```bash
apigen -c apigen.neon
```

## Running QUnit tests

Fieldmanager uses QUnit for JavaScript unit tests. To quickly check the status of the tests, open `/tests/js/index.html` in your browser. You can also run the tests from the command line: Install the dependencies with `npm install`, then use `grunt qunit:latest` to run the tests against WordPress trunk or `grunt qunit:recent` to run the tests against trunk and the last two major releases.
