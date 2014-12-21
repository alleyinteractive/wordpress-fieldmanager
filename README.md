# Fieldmanager <img align="right" src="https://travis-ci.org/alleyinteractive/wordpress-fieldmanager.png?branch=master" alt="Travis CI Build Status" />

Fieldmanager is a comprehensive toolkit for building forms, metaboxes, and custom admin screens for WordPress.

## Using Fieldmanager in your WordPress Project

Fieldmanager is a powerful library which can make the development of sophisticated features a breeze. To get started, simply clone this repository into your plugins directory and activate it on the plugins screen. To learn how to use Fieldmanager's API, visit the project's official website at [Fieldmanager.org](http://fieldmanager.org). There is also [a demo plugin](https://github.com/alleyinteractive/fieldmanager-demos) which illustrates a lot of what you can do with Fieldmanager.

## Contributing to Development

Development of Fieldmanager happens on [Github](http://github.com/alleyinteractive/wordpress-fieldmanager). Bugs with Fieldmanager should be addressed in the Github issue queue, and enhancements or bug fixes should be submitted as pull requests, which are always welcome.

## Generating Documentation

To build Fieldmanager's API documentation, the latest version of which is available at [api.fieldmanager.org](http://api.fieldmanager.org), you need [apigen](http://apigen.org/) installed. Once you've got that, you can generate the entire documentation tree as follows:

```bash
apigen -c apigen.neon
```
