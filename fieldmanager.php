<?php
/**
 * Fieldmanager Base Plugin File.
 *
 * @package Fieldmanager
 * @version 1.2.1
 */

/*
Plugin Name: Fieldmanager
Plugin URI: https://github.com/alleyinteractive/wordpress-fieldmanager
Description: Add fields to WordPress programatically.
Author: Alley Interactive
Version: 1.2.1
Author URI: https://www.alleyinteractive.com/
*/

/**
 * Current version of Fieldmanager.
 */
define( 'FM_VERSION', '1.2.1' );

/**
 * Filesystem path to Fieldmanager.
 */
define( 'FM_BASE_DIR', dirname( __FILE__ ) );

/**
 * Default version number for static assets registered via Fieldmanager.
 */
define( 'FM_GLOBAL_ASSET_VERSION', 1 );

/**
 * Whether to display debugging information. Default is value of WP_DEBUG.
 */
if ( ! defined( 'FM_DEBUG' ) ) {
	define( 'FM_DEBUG', WP_DEBUG );
}

/**
 * Load a Fieldmanager class based on a class name.
 *
 * Understands Fieldmanager nomenclature to find the right file within the
 * plugin, but does not know how to load classes outside of that. If you build
 * your own field, you will have to include it or autoload it for yourself.
 *
 * @see fieldmanager_load_file() for more detail about possible return values.
 *
 * @param string $class Class name to load.
 * @return mixed The result of fieldmanager_load_file() or void if the class is
 *     not found.
 */
function fieldmanager_load_class( $class ) {
	if ( class_exists( $class ) || 0 !== strpos( $class, 'Fieldmanager' ) ) {
		return;
	}
	$class_id = strtolower( substr( $class, strrpos( $class, '_' ) + 1 ) );

	if ( 0 === strpos( $class, 'Fieldmanager_Context' ) ) {
		if ( 'context' === $class_id ) {
			return fieldmanager_load_file( 'context/class-fieldmanager-context.php' );
		}
		return fieldmanager_load_file( 'context/class-fieldmanager-context-' . $class_id . '.php' );
	}

	if ( 0 === strpos( $class, 'Fieldmanager_Datasource' ) ) {
		if ( 'datasource' === $class_id ) {
			return fieldmanager_load_file( 'datasource/class-fieldmanager-datasource.php' );
		}
		return fieldmanager_load_file( 'datasource/class-fieldmanager-datasource-' . $class_id . '.php' );
	}

	if ( 0 === strpos( $class, 'Fieldmanager_Util' ) ) {
		return fieldmanager_load_file( 'util/class-fieldmanager-util-' . $class_id . '.php' );
	}

	return fieldmanager_load_file( 'class-fieldmanager-' . $class_id . '.php', $class );
}


if ( function_exists( 'spl_autoload_register' ) ) {
	spl_autoload_register( 'fieldmanager_load_class' );
}

/**
 * Load a Fieldmanager file.
 *
 * @throws FM_Class_Not_Found_Exception The Class is not found.
 *
 * @param  string $file File to load.
 */
function fieldmanager_load_file( $file ) {
	$file = FM_BASE_DIR . '/php/' . $file;
	if ( ! file_exists( $file ) ) {
		throw new FM_Class_Not_Found_Exception( $file );
	}
	require_once( $file );
}

// Load utility classes with helper functions. These can't be autoloaded.
fieldmanager_load_file( 'util/class-fieldmanager-util-term-meta.php' );
fieldmanager_load_file( 'util/class-fieldmanager-util-validation.php' );

/**
 * Tell Fieldmanager that it has a base URL somewhere other than the plugins URL.
 *
 * @param string $path The URL to Fieldmanager, excluding "fieldmanager/", but
 *     including a trailing slash.
 */
function fieldmanager_set_baseurl( $path ) {
	_fieldmanager_registry( 'baseurl', trailingslashit( $path ) );
}

/**
 * Get the Fieldmanager base URL.
 *
 * @return string The URL pointing to the top Fieldmanager.
 */
function fieldmanager_get_baseurl() {
	$path_override = _fieldmanager_registry( 'baseurl' );
	if ( $path_override ) {
		return $path_override;
	}
	return plugin_dir_url( __FILE__ );
}

/**
 * Get the path to a field template.
 *
 * @param string $tpl_slug The name of a template file inside the template
 *     directory, excluding ".php".
 * @param string $tpl_dir The template directory.
 * @return string The template path, or the path to "textfield.php" if the
 *     requested template is not found.
 */
function fieldmanager_get_template( $tpl_slug, $tpl_dir = '' ) {
	$template_directory = plugin_dir_path( __FILE__ ) . 'templates';
	if ( ! empty( $tpl_dir ) && file_exists( $tpl_dir ) ) {
		$template_directory = $tpl_dir;
	}
	if ( ! file_exists( trailingslashit( $template_directory ) . $tpl_slug . '.php' ) ) {
		$tpl_slug = 'textfield';
	}
	return trailingslashit( $template_directory ) . $tpl_slug . '.php';
}

/**
 * Enqueue a script, optionally localizing data to it.
 *
 * @see Fieldmanager_Util_Assets::add_script().
 *
 * @param string      $handle      Script handle.
 * @param string|bool $path        Optional. The path to the file inside $plugin_dir.
 *                                 Default false.
 * @param array       $deps        Optional. Script dependencies. Default empty array.
 * @param string|bool $ver         Optional. Script version. Default none.
 * @param bool        $in_footer   Optional. Whether to render the script in the footer.
 *                                 Default false.
 * @param string      $data_object Optional. The $object_name in wp_localize_script().
 *                                 Default none.
 * @param array       $data        Optional. The $l10n in wp_localize_script(). Default empty
 *                                 array.
 * @param string      $plugin_dir  The base URL to the directory with the script.
 *                                 Default none.
 * @param bool        $admin       Deprecated.
 */
function fm_add_script( $handle, $path = false, $deps = array(), $ver = false, $in_footer = false, $data_object = '', $data = array(), $plugin_dir = '', $admin = true ) {
	Fieldmanager_Util_Assets::instance()->add_script( compact( 'handle', 'path', 'deps', 'ver', 'in_footer', 'data_object', 'data', 'plugin_dir' ) );
}

/**
 * Register and enqueue a style.
 *
 * @see Fieldmanager_Util_Assets::add_style().
 *
 * @param string      $handle Stylesheet name.
 * @param string|bool $path   Optional. Path to the file inside of the Fieldmanager
 *                            base URL. Default false.
 * @param array       $deps   Optional. Stylesheet dependencies. Default empty array.
 * @param string|bool $ver    Optional. Stylesheet version. Default none.
 * @param string      $media  Optional. Media for this stylesheet. Default 'all'.
 * @param bool        $admin  Deprecated.
 */
function fm_add_style( $handle, $path = false, $deps = array(), $ver = false, $media = 'all', $admin = true ) {
	Fieldmanager_Util_Assets::instance()->add_style( compact( 'handle', 'path', 'deps', 'ver', 'media' ) );
}

/**
 * Get or set values from a simple, static registry.
 *
 * Keeps the globals out.
 *
 * @param  string $var The variable name to set.
 * @param  mixed  $val The value to store for $var. Default null.
 * @return mixed The stored value of $var if $val is null, or false if $val is
 *               null and $var was not set in the registry, or void if $val is
 *               being set.
 */
function _fieldmanager_registry( $var, $val = null ) {
	static $registry;
	if ( ! is_array( $registry ) ) {
		$registry = array();
	}
	if ( null === $val ) {
		return isset( $registry[ $var ] ) ? $registry[ $var ] : false;
	}
	$registry[ $var ] = $val;
}

/**
 * Get the context for triggers and pattern matching.
 *
 * This function is crucial for performance. It prevents the unnecessary
 * initialization of FM classes, and the unnecessary loading of CSS and
 * JavaScript.
 *
 * @see fm_calculate_context() for detail about the returned array values.
 *
 * @param bool $recalculate Optional. If true, FM will recalculate the current
 *                          context. This is necessary for testing and perhaps
 *                          other programmatic purposes.
 * @return array Contextual information for the current request.
 */
function fm_get_context( $recalculate = false ) {
	static $calculated_context;

	if ( ! $recalculate && $calculated_context ) {
		return $calculated_context;
	} else {
		$calculated_context = fm_calculate_context();
		return $calculated_context;
	}
}

/**
 * Calculate contextual information for the current request.
 *
 * You can't use this function to determine whether or not a context "form" will
 * be displayed, since it can be used anywhere. We would love to use
 * get_current_screen(), but it's not available in some POST actions, and
 * generally not available early enough in the init process.
 *
 * This is a function to watch closely as WordPress changes, since it relies on
 * paths and variables.
 *
 * @return array {
 *     Array of context information.
 *
 *     @type  string|null A Fieldmanager context of "post", "quickedit", "term",
 *                        "submenu", or "user", or null if one isn't found.
 *     @type  string|null A "type" dependent on the context. For "post" and
 *                        "quickedit", the post type. For "term", the taxonomy.
 *                        For "submenu", the group name. For all others, null.
 * }
 */
function fm_calculate_context() {
	$calculated_context = array( null, null );

	// Safe to use at any point in the load process, and better than URL matching.
	if ( is_admin() && ! empty( $_SERVER['PHP_SELF'] ) ) {  // WPCS: input var okay.
		$php_self = sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) );  // WPCS: input var okay.
		$script = substr( $php_self, strrpos( $php_self, '/' ) + 1 );

		$get_post_type = '';
		if ( ! empty( $_GET['post_type'] ) ) {  // WPCS: input var okay.
			$get_post_type = sanitize_text_field( wp_unslash( $_GET['post_type'] ) ); // WPCS: input var okay.
		}

		$get_page = '';
		if ( ! empty( $_GET['page'] ) ) {  // WPCS: input var okay.
			$get_page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // WPCS: input var okay.
		}

		$post_action = '';
		if ( ! empty( $_POST['action'] ) ) {  // WPCS: input var okay. CSRF ok.
			$post_action = sanitize_text_field( wp_unslash( $_POST['action'] ) ); // WPCS: input var okay. CSRF ok.
		}

		/*
		 * Calculate a submenu context.
		 *
		 * For submenus of the default WordPress menus, the submenu's parent
		 * slug should match the requested script. For submenus of custom menu
		 * pages, where "admin.php" is the requested script but not the parent
		 * slug, the submenu's slug should match the GET request.
		 *
		 * @see fm_register_submenu_page() for detail about $submenu array values.
		 */
		if ( ! empty( $get_page ) ) {
			$submenus = _fieldmanager_registry( 'submenus' );

			if ( ! empty( $get_post_type ) ) {
				if ( post_type_exists( $get_post_type ) ) {
					$script .= "?post_type={$get_post_type}";
				}
			}

			if ( $submenus ) {
				foreach ( $submenus as $submenu ) {
					if ( $script === $submenu[0] || ( 'admin.php' === $script && $get_page === $submenu[4] ) ) {
						$calculated_context = array( 'submenu', $get_page );
					}
				}
			}
		}

		if ( empty( $calculated_context[0] ) ) {
			switch ( $script ) {
				// Context = "post".
				case 'post.php':
					if ( ! empty( $post_action ) && ( 'editpost' === $post_action || 'newpost' === $post_action ) ) {
						$calculated_context = array( 'post', sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) ); // WPCS: input var okay. CSRF ok.
					} elseif ( ! empty( $_GET['post'] ) ) { // WPCS: input var okay.
						$calculated_context = array( 'post', get_post_type( intval( $_GET['post'] ) ) ); // WPCS: input var okay.
					}
					break;
				case 'post-new.php':
					$calculated_context = array( 'post', ! empty( $get_post_type ) ? $get_post_type : 'post' );
					break;
				// Context = "user".
				case 'profile.php':
				case 'user-edit.php':
					$calculated_context = array( 'user', null );
					break;
				// Context = "quickedit".
				case 'edit.php':
					$calculated_context = array( 'quickedit', ! empty( $get_post_type ) ? $get_post_type : 'post' );
					break;
				case 'admin-ajax.php':
					// Passed in via an Ajax form.
					if ( ! empty( $_POST['fm_context'] ) ) { // WPCS: input var okay. CSRF ok.
						$subcontext = ! empty( $_POST['fm_subcontext'] ) ? sanitize_text_field( wp_unslash( $_POST['fm_subcontext'] ) ) : null; // WPCS: input var okay. CSRF ok.
						$calculated_context = array( sanitize_text_field( wp_unslash( $_POST['fm_context'] ) ), $subcontext ); // WPCS: input var okay. CSRF ok.
					} elseif ( ! empty( $_POST['screen'] ) && ! empty( $post_action ) ) { // WPCS: input var okay. CSRF ok.
						if ( 'edit-post' === $_POST['screen'] && 'inline-save' === $post_action ) { // WPCS: input var okay. CSRF ok.
							$calculated_context = array( 'quickedit', sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) ); // WPCS: input var okay. CSRF ok.
						} elseif ( 'add-tag' === $post_action && ! empty( $_POST['taxonomy'] ) ) { // WPCS: input var okay. CSRF ok.
							// Context = "term".
							$calculated_context = array( 'term', sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) ); // WPCS: input var okay. CSRF ok.
						}
					} elseif ( ! empty( $_GET['action'] ) && 'fm_quickedit_render' === $_GET['action'] ) { // WPCS: input var okay.
						// Context = "quickedit".
						$calculated_context = array( 'quickedit', sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ); // WPCS: input var okay.
					}
					break;
				// Context = "term".
				case 'edit-tags.php':
				case 'term.php': // As of 4.5-alpha; see https://core.trac.wordpress.org/changeset/36308
					if ( ! empty( $_POST['taxonomy'] ) ) { // WPCS: input var okay. CSRF ok.
						$calculated_context = array( 'term', sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) ); // WPCS: input var okay. CSRF ok.
					} elseif ( ! empty( $_GET['taxonomy'] ) ) { // WPCS: input var okay.
						$calculated_context = array( 'term', sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) ); // WPCS: input var okay.
					}
					break;
			}
		}
	}

	/**
	 * Filters the Fieldmanager context calculated for the current request.
	 *
	 * @param array $calculated_context Array of context and 'type' information.
	 */
	return apply_filters( 'fm_calculated_context', $calculated_context );
}

/**
 * Check whether a context is active.
 *
 * @see fm_get_context() for detail about the array values this function tries
 *     to match.
 *
 * @param  string       $context The Fieldmanager context to check for.
 * @param  string|array $type    Type or types to check for. Default null.
 * @return bool True if $context is "form". If $type is null, true if $context
 *              matches the first value of fm_get_context(). If $type is a
 *              string or array, true if the second value of fm_get_context()
 *              matches the string or is in the array and the first value
 *              matches $context. False otherwise.
 */
function fm_match_context( $context, $type = null ) {
	if ( 'form' === $context ) {
		// Nothing to check, since forms can be anywhere.
		return true;
	}

	$calculated_context = fm_get_context();
	if ( $calculated_context[0] === $context ) {
		if ( null !== $type ) {
			if ( is_array( $type ) ) {
				return in_array( $calculated_context[1], $type, true );
			}
			return ( $calculated_context[1] === $type );
		}
		return true;
	}
	return false;
}

/**
 * Fire an action for the current Fieldmanager context, if it exists.
 *
 * @see fm_calculate_context() for detail about the values that determine the
 *     name of the action. Two actions are defined, but only one at most fires.
 */
function fm_trigger_context_action() {
	$calculated_context = fm_get_context();
	if ( empty( $calculated_context[0] ) ) {
		return;
	}

	list( $context, $type ) = $calculated_context;

	if ( $type ) {
		/**
		 * Fires when a specific Fieldmanager context and type load.
		 *
		 * The dynamic portions of the hook name, $context and $type, refer to
		 * the values returned by fm_calculate_context(). For example, the Edit
		 * screen for the Page post type would fire "fm_post_page".
		 *
		 * @param string $type The context subtype, e.g. the post type, taxonomy
		 *                     name, submenu option name.
		 */
		do_action( "fm_{$context}_{$type}", $type );
	}

	/**
	 * Fires when any Fieldmanager context loads.
	 *
	 * The dynamic portion of the hook name, $context, refers to the first
	 * value returned by fm_calculate_context(). For example, the Edit User
	 * screen would fire "fm_user".
	 *
	 * @param string|null $type The context subtype, e.g. the post type,
	 *                          taxonomy name, submenu option name. null if this
	 *                          context does not have a subtype.
	 */
	do_action( "fm_{$context}", $type );
}
add_action( 'init', 'fm_trigger_context_action', 99 );

/**
 * Add data about a submenu page to the Fieldmanager registry under a slug.
 *
 * @see Fieldmanager_Context_Submenu for detail about $parent_slug, $page_title,
 *     $menu_title, $capability, and $menu_slug.
 *
 * @throws FM_Duplicate_Submenu_Name_Exception The Duplicate submenu name exception.
 *
 * @param string $group_name A slug to register the submenu page under.
 * @param string $parent_slug Parent menu slug name or admin page file name.
 * @param string $page_title Page title.
 * @param string $menu_title Menu title. Falls back to $page_title if not set. Default null.
 * @param string $capability Capability required to access the page. Default "manage_options".
 * @param string $menu_slug Unique slug name for this submenu. Falls back to
 *     $group_name if not set. Default null.
 */
function fm_register_submenu_page( $group_name, $parent_slug, $page_title, $menu_title = null, $capability = 'manage_options', $menu_slug = null ) {
	$submenus = _fieldmanager_registry( 'submenus' );
	if ( ! $submenus ) {
		$submenus = array();
	}
	if ( isset( $submenus[ $group_name ] ) ) {
		/* translators: %s: group name */
		throw new FM_Duplicate_Submenu_Name_Exception( sprintf( esc_html__( '%s is already in use as a submenu name', 'fieldmanager' ), $group_name ) );
	}

	if ( ! $menu_title ) {
		$menu_title = $page_title;
	}

	/**
	 * These data will be used to add a Fieldmanager_Context_Submenu instance to
	 * the Fieldmanager registry if this submenu page is active.
	 *
	 * @see Fieldmanager_Field::activate_submenu_page().
	 */
	$submenus[ $group_name ] = array( $parent_slug, $page_title, $menu_title, $capability, $menu_slug ?: $group_name, '_fm_submenu_render' );

	_fieldmanager_registry( 'submenus', $submenus );
}

/**
 * Render a submenu page registered through the Fieldmanager registry.
 *
 * @see _fm_add_submenus().
 *
 * @throws FM_Submenu_Not_Initialized_Exception The submenu is not initialized exception.
 */
function _fm_submenu_render() {
	$context = _fieldmanager_registry( 'active_submenu' );
	if ( ! is_object( $context ) ) {
		throw new FM_Submenu_Not_Initialized_Exception( esc_html__( 'The Fieldmanger context for this submenu was not initialized', 'fieldmanager' ) );
	}
	$context->render_submenu_page();
}

/**
 * Register submenu pages from the Fieldmanager registry.
 */
function _fm_add_submenus() {
	$submenus = _fieldmanager_registry( 'submenus' );
	if ( ! is_array( $submenus ) ) {
		return;
	}
	foreach ( $submenus as $s ) {
		call_user_func_array( 'add_submenu_page', $s );
	}
}
add_action( 'admin_menu', '_fm_add_submenus', 15 );

/**
 * Sanitize multi-line text.
 *
 * @param string $value Unsanitized text.
 * @return string Text with each line of $value passed through sanitize_text_field().
 */
function fm_sanitize_textarea( $value ) {
	return implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $value ) ) );
}

/**
 * Stripslashes_deep for submenu data.
 */
add_filter( 'fm_submenu_presave_data', 'stripslashes_deep' );

/**
 * Exception class for Fieldmanager's fatal errors.
 *
 * Used mostly to differentiate in unit tests.
 *
 * @package Fieldmanager
 */
class FM_Exception extends Exception { }

/**
 * Exception class for classes that could not be loaded.
 *
 * @package Fieldmanager
 */
class FM_Class_Not_Found_Exception extends Exception { }

/**
 * Exception class for unitialized submenus.
 *
 * @package Fieldmanager
 */
class FM_Submenu_Not_Initialized_Exception extends Exception { }

/**
 * Exception class for duplicate submenu names.
 *
 * @package Fieldmanager
 */
class FM_Duplicate_Submenu_Name_Exception extends Exception { }

/**
 * Exception class for Fieldmanager's developer errors.
 *
 * Used mostly to differentiate in unit tests. This exception is meant to help
 * developers write correct Fieldmanager implementations.
 *
 * @package Fieldmanager
 */
class FM_Developer_Exception extends Exception { }

/**
 * Exception class for Fieldmanager's validation errors.
 *
 * Used mostly to differentiate in unit tests. Validation errors in WordPress
 * are not really recoverable.
 *
 * @package Fieldmanager
 */
class FM_Validation_Exception extends Exception { }
