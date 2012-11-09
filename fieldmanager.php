<?php
/**
 * @package Fieldmanager
 * @version 0.1
 */
/*
Plugin Name: Fieldmanager
Plugin URI: http://github.com/netaustin/fieldmanager
Description: Add fields to content types programatically.
Author: Austin Smith
Version: 0.1
Author URI: http://www.alleyinteractive.com/
*/

require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-field.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-group.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-checkbox.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-textfield.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-textarea.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-grid.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-options.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-post.php' );

/**
 * Add CSS and JS to admin area, hooked into admin_enqueue_scripts.
 */
function fieldmanager_enqueue_scripts() {
	wp_enqueue_script( 'fieldmanager_script', fieldmanager_get_baseurl() . 'js/fieldmanager.js' );
	wp_enqueue_style( 'fieldmanager_style', fieldmanager_get_baseurl() . 'css/fieldmanager.css' );
}
add_action( 'admin_enqueue_scripts', 'fieldmanager_enqueue_scripts' );

/**
 * Tell fieldmanager that it lives somewhere other than wp-content/plugins
 * @param string $path the full URL to fieldmanager, not including fieldmanager/, but including trailing slash.
 * @return void
 */
function fieldmanager_set_baseurl( $path ) {
	_fieldmanager_registry( 'baseurl', $path );
}

/**
 * Get the base URL for this plugin.
 * @return string URL pointing to Fieldmanager top directory.
 */
function fieldmanager_get_baseurl() {
	$path_override = _fieldmanager_registry( 'baseurl' );
	if ( $path_override ) {
		return $path_override;
	}
	return plugin_dir_url( __FILE__ );
}

function fm_add_script( $handle, $path, $deps = array(), $ver = false, $in_footer = false, $data_object = "", $data = array() ) {
	$add_script = function() use ( $handle, $path, $deps, $ver, $in_footer, $data_object, $data ) { 
		wp_enqueue_script( $handle, fieldmanager_get_baseurl() . $path, $deps, $ver );
		if ( !empty( $data_object ) && !empty( $data ) ) wp_localize_script( $handle, $data_object, $data );
	};
	if ( is_admin() ) {
		add_action( 'admin_enqueue_scripts', $add_script );
	} else {
		add_action( 'wp_enqueue_scripts', $add_script );
	}
}

function fm_add_style( $handle, $path, $deps = array(), $ver = false, $media = 'all' ) {
	$add_script = function() use ( $handle, $path, $deps, $ver, $media ) {
		wp_register_style( $handle, fieldmanager_get_baseurl() . $path, $deps, $ver, $media );
        wp_enqueue_style( $handle );
	};
	if ( is_admin() ) {
		add_action( 'admin_enqueue_scripts', $add_script );
	} else {
		add_action( 'wp_enqueue_scripts', $add_script );
	}
}

/**
 * A simple static registry for Fieldmanager to keep globals out.
 * @param string $var what variable to set.
 * @param mixed $val what value to store for $var.
 * @return mixed if only $var is set, return stored $val. If both are set, set $val and return void.
 */
function _fieldmanager_registry( $var, $val = NULL ) {
	static $registry;
	if ( !is_array( $registry ) ) $registry = array();
	if ( $val === NULL ) {
		return isset( $registry[$var] ) ? $registry[$var] : False;
	}
	$registry[$var] = $val;
}

class FM_Exception extends Exception { }