<?php
/**
 * Fieldmanager Base Plugin File
 * @package Fieldmanager
 * @version 0.1
 */
/*
Plugin Name: Fieldmanager
Plugin URI: https://github.com/netaustin/wordpress-fieldmanager
Description: Add fields to content types programatically.
Author: Austin Smith
Version: 0.1
Author URI: http://www.alleyinteractive.com/
*/

require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-field.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-group.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-textfield.php' );

require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-autocomplete.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-checkbox.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-datepicker.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-draggablepost.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-hidden.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-grid.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-link.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-media.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-options.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-richtextarea.php' );
require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-textarea.php' );

require_once( dirname( __FILE__ ) . '/php/datasource/class-fieldmanager-datasource.php' );
require_once( dirname( __FILE__ ) . '/php/datasource/class-fieldmanager-datasource-post.php' );
require_once( dirname( __FILE__ ) . '/php/datasource/class-fieldmanager-datasource-term.php' );
require_once( dirname( __FILE__ ) . '/php/datasource/class-fieldmanager-datasource-user.php' );

require_once( dirname( __FILE__ ) . '/php/util/class-fieldmanager-util-term-meta.php' );
require_once( dirname( __FILE__ ) . '/php/util/class-fieldmanager-util-validation.php' );

require_once( dirname( __FILE__ ) . '/php/context/class-fieldmanager-context.php' );
require_once( dirname( __FILE__ ) . '/php/context/class-fieldmanager-context-user.php' );
require_once( dirname( __FILE__ ) . '/php/context/class-fieldmanager-context-page.php' );
require_once( dirname( __FILE__ ) . '/php/context/class-fieldmanager-context-post.php' );
require_once( dirname( __FILE__ ) . '/php/context/class-fieldmanager-context-submenu.php' );
require_once( dirname( __FILE__ ) . '/php/context/class-fieldmanager-context-term.php' );

define( 'FM_GLOBAL_ASSET_VERSION', 1 );

/**
 * Add CSS and JS to admin area, hooked into admin_enqueue_scripts.
 */
function fieldmanager_enqueue_scripts() {
	wp_enqueue_script( 'fieldmanager_script', fieldmanager_get_baseurl() . 'js/fieldmanager.js' );
	wp_enqueue_style( 'fieldmanager_style', fieldmanager_get_baseurl() . 'css/fieldmanager.css' );
	wp_enqueue_script( 'jquery-ui-sortable' );
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

function fieldmanager_get_template( $tpl_slug ) {
	return plugin_dir_path( __FILE__ ) . 'templates/' . $tpl_slug . '.php';
}

/**
 * Wrapper to enqueue_scripts which uses a closure
 * @param string $handle
 * @param string $path
 * @param string[] $deps
 * @param boolean $ver
 * @param boolean $in_footer
 * @param string $data_object
 * @param array $data
 * @param string $plugin_dir
 * @param boolean $admin
 * @return void
 */
function fm_add_script( $handle, $path, $deps = array(), $ver = false, $in_footer = false, $data_object = "", $data = array(), $plugin_dir = "", $admin = true ) {
	if ( !is_admin() ) return;
	if ( !$ver ) $ver = FM_GLOBAL_ASSET_VERSION;
	if ( $plugin_dir == "" ) $plugin_dir = fieldmanager_get_baseurl(); // allow overrides for child plugins
	$add_script = function() use ( $handle, $path, $deps, $ver, $in_footer, $data_object, $data, $plugin_dir ) {
		wp_enqueue_script( $handle, $plugin_dir . $path, $deps, $ver );
		if ( !empty( $data_object ) && !empty( $data ) ) wp_localize_script( $handle, $data_object, $data );
	};
	
	add_action( 'admin_enqueue_scripts', $add_script );
	add_action( 'wp_enqueue_scripts', $add_script );
}

/**
 * Wrapper to enqueue_style which uses a closure
 * @param string $handle
 * @param string $path
 * @param string[] $deps
 * @param boolean $ver
 * @param string $media
 * @param boolean $admin
 * @return void
 */
function fm_add_style( $handle, $path, $deps = array(), $ver = false, $media = 'all', $admin = true ) {
	if( !is_admin() ) return;
	if ( !$ver ) $ver = FM_GLOBAL_ASSET_VERSION;
	$add_script = function() use ( $handle, $path, $deps, $ver, $media ) {
		wp_register_style( $handle, fieldmanager_get_baseurl() . $path, $deps, $ver, $media );
        wp_enqueue_style( $handle );
	};
	
	add_action( 'admin_enqueue_scripts', $add_script );
	add_action( 'wp_enqueue_scripts', $add_script );
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

/**
 * Wrapper for get_post_meta which handles JSON quietly.
 * @param int $post_id
 * @param string $var
 * @param boolean $single
 */
function fm_get_post_meta( $post_id, $var, $single = True ) {
	$data = get_post_meta( $post_id, $var, $single );
	return json_decode( $data, TRUE );
}

/**
 * Cheap way to tell if we're looking at a post edit page.
 * It's a good idea to use this at the beginning of implementations which use metaboxes
 * to avoid initializing your Fieldmanager chain on every page load.
 * @return boolean are we editing or creating a post?
 */
function fm_is_post_edit_screen() {
	return stripos( $_SERVER['PHP_SELF'], '/post.php' ) !== FALSE || stripos( $_SERVER['PHP_SELF'], '/post-new.php' ) !== FALSE;
}

/**
 * Sanitize multi-line text
 * @param string $value unsanitized text
 * @return string text with each line individually passed through sanitize_text_field.
 */
function fm_sanitize_textarea( $value ) {
	return implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $value ) ) );
}

/**
 * Exception class for this plugin's fatal errors; mostly to differentiate in unit tests.
 * @package Fieldmanager
 */
class FM_Exception extends Exception { }

/**
 * Exception class for this plugin's developer errors; mostly to differentiate in unit tests.
 * These exceptions are meant to help developers write correct Fieldmanager implementations.
 * @package Fieldmanager
 */
class FM_Developer_Exception extends Exception { }

/**
 * Exception class for this plugin's validation errors; mostly to differentiate in unit tests.
 * Validation errors in WordPress are not really recoverable.
 * @package Fieldmanager
 */
class FM_Validation_Exception extends Exception { }

