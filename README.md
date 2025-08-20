# Fieldmanager

Fieldmanager is a comprehensive toolkit for building forms, metaboxes, and custom admin screens for WordPress.

## Using Fieldmanager in your WordPress Project

Fieldmanager is a powerful library which can make the development of sophisticated features a breeze. To get started, simply [download](#downloads-and-versioning) and install this plugin into your plugins directory and activate it on the plugins screen. To learn how to use Fieldmanager's API, visit the project's official website at [Fieldmanager.org](http://fieldmanager.org). There is also [a demo plugin](https://github.com/alleyinteractive/fieldmanager-demos) which illustrates a lot of what you can do with Fieldmanager.

## Requirements

* WordPress: 5.8+
* PHP: 7.4+

## Downloads and Versioning.

You can view [Fieldmanager's official releases here](https://github.com/alleyinteractive/wordpress-fieldmanager/releases).

The `main` branch on GitHub is the "bleeding edge" release. As of 1.0, Fieldmanager will maintain a typical release cycle, with alpha, beta, and RC releases, and we hope to move through "minor" versions pretty quickly. While we encourage everyone to develop with and test on early releases and help us find the bugs, stable releases are recommended for production.

## Contributing to Development

Development of Fieldmanager happens on [GitHub](http://github.com/alleyinteractive/wordpress-fieldmanager). Bugs with Fieldmanager should be addressed in the GitHub issue queue, and enhancements or bug fixes should be submitted as pull requests, which are always welcome.

## Generating Documentation

[Fieldmanager's API documentation](https://api.fieldmanager.org) is built using [Doctum](https://github.com/code-lts/doctum) and a GitHub Action. Docs are stored in the `api-docs` branch.

To build the docs manually, see the Doctum README for instructions on downloading the phar file, then you can generate the entire documentation tree by checking out the `api-docs` branch and running:

```bash
doctum.phar update doctum.php
```
