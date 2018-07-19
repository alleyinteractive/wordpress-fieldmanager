<?php
/**
 * This file contains all logic related to the REST API.
 *
 * @package Fieldmanager
 */

/**
 * Fire an action for the current Fieldmanager context for the REST API.
 *
 * This is separate from fm_trigger_context_action since rest_pre_dispatch fires on parse_request
 * and is too late to be part of the existing context hooks.
 *
 * @since 1.3.0
 *
 * @param mixed           $result  Response to replace the requested version with. Can be anything
 *                                 a normal endpoint can return, or null to not hijack the request.
 * @param WP_REST_Server  $server  Server instance.
 * @param WP_REST_Request $request Request used to generate the response.
 */
function fm_trigger_rest_context_action( $result, $server, $request ) {
	// Get post type and taxonomy endpoints available for the REST API.
	$post_type_endpoints = fm_get_registered_object_rest_base( 'post_types' );
	$taxonomy_endpoints  = fm_get_registered_object_rest_base( 'taxonomies' );

	// Get the route for the request for comparison.
	$route = $request->get_route();

	// If the route is empty, we cannot continue.
	if ( empty( $route ) ) {
		return;
	}

	// Store matches to use with the context action.
	$matches = array();

	// Use regexes to find the right context.
	if (
		! empty( $post_type_endpoints )
		&& preg_match( '#/wp/v2/(' . implode( '|', array_keys( $post_type_endpoints ) ) . ')(/?)(.*?)/#i', $route, $matches )
	) {
		$context = 'post';
		$type    = $post_type_endpoints[ $matches[1] ];
	} elseif (
		! empty( $taxonomy_endpoints )
		&& preg_match( '#/wp/v2/(' . implode( '|', array_keys( $taxonomy_endpoints ) ) . ')(/?)(.*?)/#i', $route, $matches )
	) {
		$context = 'term';
		$type    = $taxonomy_endpoints[ $matches[1] ];
	} elseif ( preg_match( '#/wp/v2/users(/?)(.*?)/#i', $route ) ) {
		$context = 'user';
		$type    = null;
	} elseif ( preg_match( '#/' . FM_REST_API_NAMESPACE . '/submenu-settings/(.*)#i', $route, $matches ) ) {
		$context = 'submenu';
		$type    = $matches[1];
	}

	if ( $type ) {
		/** This action is documented in fm_trigger_context_action() */
		do_action( "fm_rest_api_{$context}_{$type}", $type );
	}

	/** This action is documented in fm_trigger_context_action() */
	do_action( "fm_rest_api_{$context}", $type );

	return $result;
}
add_filter( 'rest_pre_dispatch', 'fm_trigger_rest_context_action', 10, 3 );

/**
 * Gets the REST API base for all registered WordPress objects of a certain type.
 * Currently works for post types and taxonomies.
 *
 * @since 1.3.0
 *
 * @param string $type Either 'post_types' or 'taxonomies'.
 * @return array $rest_bases A list of all the rest bases for the object type.
 */
function fm_get_registered_object_rest_base( $type ) {
	$rest_bases = array();

	// Create the WordPress function name.
	$function_name = 'get_' . $type;

	// Do some basic error checking in case this function is used elsewhere.
	if ( ! function_exists( $function_name ) || ! in_array( $type, array( 'post_types', 'taxonomies' ), true ) ) {
		return $rest_bases;
	}

	// Get the objects.
	$objects = call_user_func( $function_name, array( 'show_in_rest' => true ), 'objects' );

	// Ensure there are some available for the rest API.
	if ( empty( $objects ) ) {
		return $rest_bases;
	}

	// Extract the rest base for each.
	foreach ( $objects as $object ) {
		$rest_base = ( empty( $object->rest_base ) ? $object->name : $object->rest_base );
		$rest_bases[ $rest_base ] = $object->name;
	}

	return $rest_bases;
}

/**
 * Add the REST API meta field.
 */
function fm_add_rest_api_meta_field() {
	// Post types.
	$post_type_endpoints = fm_get_registered_object_rest_base( 'post_types' );
	register_rest_field(
		array_values( $post_type_endpoints ),
		'fm-meta',
		array(
			'get_callback'    => 'fm_add_rest_api_meta_field_get_callback',
			'update_callback' => 'fm_add_rest_api_meta_field_update_callback',
		)
	);

	// Taxonomies.
	$taxonomy_endpoints = fm_get_registered_object_rest_base( 'taxonomies' );
	register_rest_field(
		array_values( $taxonomy_endpoints ),
		'fm-meta',
		array(
			'get_callback'    => 'fm_add_rest_api_meta_field_get_callback',
			'update_callback' => 'fm_add_rest_api_meta_field_update_callback',
		)
	);

	// The User context.
	register_rest_field(
		'user',
		'fm-meta',
		array(
			'get_callback'    => 'fm_add_rest_api_meta_field_get_callback',
			'update_callback' => 'fm_add_rest_api_meta_field_update_callback',
		)
	);
}
add_filter( 'rest_api_init', 'fm_add_rest_api_meta_field' );

/**
 * Handles getting field data for the REST API.
 *
 * @since 1.3.0
 *
 * @param  array           $object      The REST API object.
 * @param  string          $field_name  The REST API field name.
 * @param  WP_REST_Request $request     The full request object from the REST API.
 * @param  string          $object_type The REST API object type.
 * @return mixed           $data        The field data.
 */
function fm_add_rest_api_meta_field_get_callback( $object, $field_name, $request, $object_type ) {
	/**
	 * Filters all post, term, and user context data passed to the REST API.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed                $data        The current data to tbe retrieved.
	 * @param array                $object      The REST API object.
	 * @param string               $field_name  The REST API field name.
	 * @param WP_REST_Request      $request     The full request object from the REST API.
	 * @param string               $object_type The REST API object type
	 * @param Fieldmanager_Context $fm          The current FM context object.
	 */
	return apply_filters( 'fm_rest_api_get_meta', array(), $object, $field_name, $request, $object_type );
}

/**
 * Handles updating field data from the REST API.
 *
 * @since 1.3.0
 *
 * @param mixed           $data        The value to be updated for the field from the request.
 * @param object          $object      The REST API object.
 * @param string          $field_name  The REST API field name.
 * @param WP_REST_Request $request     The full request object from the REST API.
 * @param string          $object_type The REST API object type.
 */
function fm_add_rest_api_meta_field_update_callback( $data, $object, $field_name, $request, $object_type ) {
	/**
	 * Filters all post, term, and user context data ingested by the REST API.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed                $data        The current data to be updated.
	 * @param array                $object      The REST API object.
	 * @param string               $field_name  The REST API field name.
	 * @param WP_REST_Request      $request     The full request object from the REST API.
	 * @param string               $object_type The REST API object type
	 */
	$result = apply_filters( 'fm_rest_api_update_meta', $data, $object, $field_name, $request, $object_type );

	// The result of the update.
	return $result;
}
