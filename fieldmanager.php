<?php
/**
 * Fieldmanager Base Plugin File
 * @package Fieldmanager
 * @version 1.0-alpha
 */
/*
Plugin Name: Fieldmanager
Plugin URI: https://github.com/netaustin/wordpress-fieldmanager
Description: Add fields to content types programatically.
Author: Austin Smith
Version: 0.1
Author URI: http://www.alleyinteractive.com/
*/

define( 'FM_VERSION', '1.0-alpha' );
define( 'FM_BASE_DIR', dirname( __FILE__ ) );
if ( !defined( 'FM_DEBUG' ) ) define( 'FM_DEBUG', WP_DEBUG );

/**
 * Loads a class based on classname. Understands Fieldmanager nomenclature to find the right file.
 * Does not know how to load classes outside of Fieldmanager's plugin, so if you build your own field,
 * you will have to include it or autoload it for yourself.
 *
 * @uses spl_autoload_register
 * @uses fieldmanager_load_file
 * @param string $class
 */
function fieldmanager_load_class( $class ) {
	if ( class_exists( $class ) || strpos( $class, 'Fieldmanager' ) !== 0 ) return;
	$class_id = strtolower( substr( $class, strrpos( $class, '_' ) + 1 ) );

	if ( strpos( $class, 'Fieldmanager_Context' ) === 0 ) {
		if ( $class_id == 'context' ) return fieldmanager_load_file( 'context/class-fieldmanager-context.php' );	
		return fieldmanager_load_file( 'context/class-fieldmanager-context-' . $class_id . '.php' );
	}

	if ( strpos( $class, 'Fieldmanager_Datasource' ) === 0 ) {
		if ( $class_id == 'datasource' ) return fieldmanager_load_file( 'datasource/class-fieldmanager-datasource.php' );
		return fieldmanager_load_file( 'datasource/class-fieldmanager-datasource-' . $class_id . '.php' );
	}
	return fieldmanager_load_file( 'class-fieldmanager-' . $class_id . '.php', $class );
}

/**
 * Loads a Fieldmanager file
 * @throws Fieldmanager_Class_Undefined
 * @param string $file
 */
function fieldmanager_load_file( $file ) {
	$file = FM_BASE_DIR . '/php/' . $file;
	if ( !file_exists( $file ) ) throw new FM_Class_Not_Found_Exception( $file );
	require_once( $file );
}

if ( function_exists( 'spl_autoload_register' ) ) {
	spl_autoload_register( 'fieldmanager_load_class' );
}


// Utility classes with helper functions
fieldmanager_load_file( 'util/class-fieldmanager-util-term-meta.php' );
fieldmanager_load_file( 'util/class-fieldmanager-util-validation.php' );


define( 'FM_GLOBAL_ASSET_VERSION', 1 );

/**
 * Add CSS and JS to admin area, hooked into admin_enqueue_scripts.
 */
function fieldmanager_enqueue_scripts() {
	wp_enqueue_script( 'fieldmanager_script', fieldmanager_get_baseurl() . 'js/fieldmanager.js', array( 'jquery' ), '1.0.2' );
	wp_enqueue_style( 'fieldmanager_style', fieldmanager_get_baseurl() . 'css/fieldmanager.css', array(), '1.0.0' );
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
 * Get the context for triggers and pattern matching.
 *
 * This function is crucial for performance. It prevents the unnecessary initialization of FM classes,
 * and the unnecessary loading of CSS and Javascript.
 * 
 * You can't use this function to determine whether or not a context 'Form' will be displayed, since
 * it can be used anywhere. We would love to use get_current_screen(), but it's not available in
 * some POST actions, and generally not available early enough in the init process.
 *
 * This is a function to watch closely as WordPress changes, since it relies on paths and variables.
 *
 * @return string[] [$context, $type]
 */
function fm_get_context() {
	static $calculated_context;

	if ( $calculated_context ) return $calculated_context;

	if ( is_admin() ) { // safe to use at any point in the load process, and better than URL matching.

		$script = substr( $_SERVER['PHP_SELF'], strrpos( $_SERVER['PHP_SELF'], '/' ) + 1 );

		// context = submenu
		if ( !empty( $_GET['page'] ) ) {
			$submenus = _fieldmanager_registry( 'submenus' );
			if ( $submenus ) {
				foreach ( $submenus as $submenu ) {
					if ( $script == $submenu[0] ) {
						$calculated_context = array( 'submenu', sanitize_text_field( $_GET['page'] ) );
						return $calculated_context;
					}
				}
			}
		}

		switch ( $script ) {
			// context = post
			case 'post.php':
				if ( !empty( $_POST['action'] ) && ( 'editpost' === $_POST['action'] || 'newpost' === $_POST['action'] ) ) {
					$calculated_context = array( 'post', sanitize_text_field( $_POST['post_type'] ) );
				} elseif ( !empty( $_GET['post'] ) ) {
					$calculated_context = array( 'post', get_post_type( intval( $_GET['post'] ) ) );
				}
				break;
			case 'post-new.php':
				$calculated_context = array( 'post', !empty( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : 'post' );
				break;
			// context = user
			case 'profile.php':
			case 'user-edit.php':
				$calculated_context = array( 'user', null );
				break;
			// context = quickedit
			case 'edit.php':
				$calculated_context = array( 'quickedit', !empty( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : 'post' );
				break;
			case 'admin-ajax.php':
				// passed in via an ajax form
				if ( !empty( $_POST['fm_context'] ) ) {
					$subcontext = !empty( $_POST['fm_subcontext'] ) ? sanitize_text_field( $_POST['fm_subcontext'] ) : null;
					$calculated_context = array( sanitize_text_field( $_POST['fm_context'] ), $subcontext );
				} elseif ( !empty( $_POST['screen'] ) && !empty( $_POST['action'] ) ) {
					if ( 'edit-post' === $_POST['screen'] && 'inline-save' === $_POST['action'] ) {
						$calculated_context = array( 'quickedit', sanitize_text_field( $_POST['post_type'] ) );
					// context = term
					} elseif ( 'add-tag' === $_POST['action'] && !empty( $_POST['taxonomy'] ) ) {
						$calculated_context = array( 'term', sanitize_text_field( $_POST['taxonomy'] ) );
					}
				// context = quickedit
				} elseif ( !empty( $_GET['action'] ) && 'fm_quickedit_render' === $_GET['action'] ) {
					$calculated_context = array( 'quickedit', sanitize_text_field( $_GET['post_type'] ) );	
				}
				break;
			// context = term
			case 'edit-tags.php':
				if ( !empty( $_POST['taxonomy'] ) ) {
					$calculated_context = array( 'term', sanitize_text_field( $_POST['taxonomy'] ) );
				} elseif ( !empty( $_GET['taxonomy'] ) ) {
					$calculated_context = array( 'term', sanitize_text_field( $_GET['taxonomy'] ) );
				}
				break;
		}
	}

	if ( empty( $calculated_context ) ) {
		$calculated_context = array( null, null );
	}
	return $calculated_context;
}

/**
 * Check to see if a given context is active.
 * @see fm_get_context()
 * @param string $context one of 'post', 'quickedit', 'submenu', 'term', 'form', or 'user'.
 * @param string|string[] $type Optional. For 'post' and 'quickedit', pass the post type. For 'term' it will be
 *   the taxonomy. For 'submenu' it will be the page name. For all others it will be null. You can pass an array
 *   of types to match several types.
 * @return string[] - a two-element array, like [context, type].
 */
function fm_match_context( $context, $type = null ) {
	if ( $context == 'form' ) return true; // nothing to check, since forms can be anywhere.
	$calculated_context = fm_get_context();
	if ( $context == $calculated_context[0] ) {
		if ( $type !== null ) {
			if ( is_array( $type ) ) {
				return in_array( $calculated_context[1], $type );
			}
			return ( $type == $calculated_context[1] );
		}
		return true;
	}
	return false;
}

/**
 * Trigger the action for a given context
 * @uses fm_get_context()
 */
function fm_trigger_context_action() {
	$calculated_context = fm_get_context();
	$action = 'fm_' . $calculated_context[0];
	if ( $calculated_context[1] ) $action .= '_' . $calculated_context[1];
	do_action( $action );
}

add_action( 'init', 'fm_trigger_context_action', 99 );

/**
 * Register a submenu page
 * @throws FM_Duplicate_Submenu_Name
 */
function fm_register_submenu_page( $group_name, $parent_slug, $page_title, $menu_title = Null, $capability = 'manage_options', $menu_slug = Null ) {
	$submenus = _fieldmanager_registry( 'submenus' );
	if ( !$submenus ) $submenus = array();
	if ( isset( $submenus[ $group_name ] ) ) {
		throw new FM_Duplicate_Submenu_Name_Exception( $group_name . ' is already in use as a submenu name' );
	}

	if ( !$menu_title ) $menu_title = $page_title;

	// will be replaced by a Fieldmanager_Context_Submenu if this submenu page is active.
	$submenus[ $group_name ] = array( $parent_slug, $page_title, $menu_title, $capability, $menu_slug ?: $group_name, '_fm_submenu_render' );

	_fieldmanager_registry( 'submenus', $submenus );
}

/**
 * Render a registered submenu page
 */
function _fm_submenu_render() {
	$context = _fieldmanager_registry( 'active_submenu' );
	if ( !is_object( $context ) ) {
		throw new FM_Submenu_Not_Initialized_Exception( 'The Fieldmanger context for this submenu was not initialized' );
	}
	$context->render_submenu_page();
}

/**
 * Hook into admin_menu to register submenu pages
 */
function _fm_add_submenus() {
	$submenus = _fieldmanager_registry( 'submenus' );
	if ( !is_array( $submenus ) ) return;
	foreach ( $submenus as $s ) {
		call_user_func_array( 'add_submenu_page', $s );
	}
}
add_action( 'admin_menu', '_fm_add_submenus' );

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
 * Exception Class for classes that could not be loaded
 */
class FM_Class_Not_Found_Exception extends Exception { }

/**
 * Exception class for unitialized submenus
 */
class FM_Submenu_Not_Initialized_Exception extends Exception { }

/**
 * Exception Class for duplicate submenus
 */
class FM_Duplicate_Submenu_Name_Exception extends Exception { }

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

