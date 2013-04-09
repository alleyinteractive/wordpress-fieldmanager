<?php

class Fieldmanager_Datasource_Post extends Fieldmanager_Datasource {

	public $query_callback = Null;

	public $query_args = array();

	public $use_ajax = True;

	public $reciprocal = Null;

	public function __construct( $options = array() ) {
		parent::__construct( $options );
	}

	public function get_value( $value ) {
		$id = intval( $value );
		return $id ? get_the_title( $id ) : '';
	}

	public function get_items( $fragment = Null ) {
		if ( is_callable( $this->query_callback ) ) {
			return call_user_func( $this->query_callback, $fragment );
		}
		$default_args = array(
			'numberposts' => 10,
			'orderby' => 'post_date',
			'order' => 'DESC',
			'post_status' => 'publish',
			'post_type' => 'any',
			'suppress_filters' => False,
		);
		$post_args = array_merge( $default_args, $this->query_args );
		$ret = array();
		if ( $fragment ) {
			$post_id = $exact_post = Null;
			if ( preg_match( '/^https?\:/i', $fragment ) ) {
				$url = esc_url( $fragment );
				$url_parts = parse_url( $url );
				$get_vars = array();
				parse_str( $url_parts['query'], $get_vars );
				if ( !empty( $get_vars['post'] )  ) {
					$post_id = intval( $get_vars['post'] );
				} elseif ( !empty( $get_vars['p'] ) ) {
					$post_id = intval( $get_vars['p'] );
				} else {
					$post_id = fm_url_to_post_id( $fragment );
				}
			} elseif ( is_numeric( $fragment ) ) {
				$post_id = intval( $fragment );
			}
			if ( $post_id ) {
				$exact_post = get_post( $post_id );
				if ( $exact_post && (
					$post_args['post_type'] == 'any' ||
					$post_args['post_type'] == $exact_post->post_type ||
					in_array( $exact_post->post_type, $post_args['post_type'] )
				) ) {
					$ret[ $post_id ] = html_entity_decode( $exact_post->post_title );
				}
			}
			$this->_fragment = $fragment;
			add_filter( 'posts_where', array( $this, 'title_like' ), 10, 2 );
		}
		$posts = get_posts( $post_args );
		if ( $fragment ) {
			remove_filter( 'posts_where', array( $this, 'title_like' ), 10, 2 );
		}
		foreach ( $posts as $p ) {
			$ret[$p->ID] = $p->post_title;
		}
		return $ret;
	}

	/**
	 * Perform a LIKE search on post_title, since 's' in WP_Query is too fuzzy when trying to autocomplete a title
	 */
	public function title_like( $where, &$wp_query ) {
		global $wpdb;
		$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( like_escape( $this->_fragment ) ) . '%\'';
		return $where;
	}

	/**
	 * For post relationships, delete reciprocal post metadata prior to saving (presave will re-add)
	 * @param array $values new post values
	 * @param array $current_values existing post values
	 */
	public function presave_alter_values( Fieldmanager_Field $field, $values, $current_values ) {
		if ( $field->data_type != 'post' || !$this->reciprocal ) return $values;
		foreach ( $current_values as $reciprocal_post_id ) {
			delete_post_meta( $reciprocal_post_id, $this->reciprocal, $field->data_id );
		}
		return $values;
	}

	public function presave( Fieldmanager_Field $field, $value, $current_value ) {
		$value = intval( $value );
		if( !current_user_can( 'edit_post', $value ) ) {
			$this->_unauthorized_access( 'Tried to refer to post ' . $value . ' which user cannot edit.' );	
		}
		if ( $this->reciprocal ) {
			add_post_meta( $value, $this->reciprocal, $field->data_id );
		}
		return $value;
	}

	public function get_view_link( $value ) {
		return sprintf(
			' <a target="_new" class="fm-autocomplete-view-link %s" href="%s">View</a>',
			empty( $value ) ? 'fm-hidden' : '',
			empty( $value ) ? '#' : get_permalink( $value )
		);
	}

	public function get_edit_link( $value ) {
		return sprintf(
			' <a target="_new" class="fm-autocomplete-edit-link %s" href="%s">Edit</a>',
			empty( $value ) ? 'fm-hidden' : '',
			empty( $value ) ? '#' : get_edit_post_link( $value )
		);
	}

}

/**
 * Post URLs to IDs function, supports custom post types
 * borrowed and modified from url_to_postid() in wp-includes/rewrite.php
 * @author http://betterwp.net/
 * @param string $url
 * @return int|boolean post ID on success, false on failure
 */
function fm_url_to_post_id( $url ) {
	global $wp_rewrite;

	$url = apply_filters('url_to_postid', $url);

	// First, check to see if there is a 'p=N' or 'page_id=N' to match against
	if ( preg_match('#[?&](p|page_id|attachment_id)=(\d+)#', $url, $values ) ) {
		$id = absint($values[2]);
		if ( $id )
			return $id;
	}

	// Check to see if we are using rewrite rules
	$rewrite = $wp_rewrite->wp_rewrite_rules();

	// Not using rewrite rules, and 'p=N' and 'page_id=N' methods failed, so we're out of options
	if ( empty( $rewrite ) )
		return 0;

	// Get rid of the #anchor
	$url_split = explode( '#', $url );
	$url = $url_split[0];

	// Get rid of URL ?query=string
	$url_split = explode('?', $url);
	$url = $url_split[0];

	// Add 'www.' if it is absent and should be there
	if ( false !== strpos(home_url(), '://www.') && false === strpos($url, '://www.') )
		$url = str_replace('://', '://www.', $url);

	// Strip 'www.' if it is present and shouldn't be
	if ( false === strpos(home_url(), '://www.') )
		$url = str_replace('://www.', '://', $url);

	// Strip 'index.php/' if we're not using path info permalinks
	if ( !$wp_rewrite->using_index_permalinks() )
		$url = str_replace('index.php/', '', $url);

	if ( false !== strpos($url, home_url()) ) {
		// Chop off http://domain.com
		$url = str_replace(home_url(), '', $url);
	} else {
		// Chop off /path/to/blog
		$home_path = parse_url(home_url());
		$home_path = isset( $home_path['path'] ) ? $home_path['path'] : '' ;
		$url = str_replace($home_path, '', $url);
	}

	// Trim leading and lagging slashes
	$url = trim($url, '/');

	$request = $url;
	// Look for matches.
	$request_match = $request;
	foreach ( (array)$rewrite as $match => $query) {
		// If the requesting file is the anchor of the match, prepend it
		// to the path info.
		if ( !empty($url) && ($url != $request) && (strpos($match, $url) === 0) )
			$request_match = $url . '/' . $request;

		if ( preg_match("!^$match!", $request_match, $matches) ) {
			// Got a match.
			// Trim the query of everything up to the '?'.
			$query = preg_replace("!^.+\?!", '', $query);

			// Substitute the substring matches into the query.
			$query = addslashes(WP_MatchesMapRegex::apply($query, $matches));

			// Filter out non-public query vars
			global $wp;
			parse_str($query, $query_vars);
			$query = array();
			foreach ( (array) $query_vars as $key => $value ) {
				if ( in_array($key, $wp->public_query_vars) )
					$query[$key] = $value;
			}

		// Taken from class-wp.php
		foreach ( $GLOBALS['wp_post_types'] as $post_type => $t )
			if ( $t->query_var )
				$post_type_query_vars[$t->query_var] = $post_type;

		foreach ( $wp->public_query_vars as $wpvar ) {
			if ( isset( $wp->extra_query_vars[$wpvar] ) )
				$query[$wpvar] = $wp->extra_query_vars[$wpvar];
			elseif ( isset( $_POST[$wpvar] ) )
				$query[$wpvar] = $_POST[$wpvar];
			elseif ( isset( $_GET[$wpvar] ) )
				$query[$wpvar] = $_GET[$wpvar];
			elseif ( isset( $query_vars[$wpvar] ) )
				$query[$wpvar] = $query_vars[$wpvar];

			if ( !empty( $query[$wpvar] ) ) {
				if ( ! is_array( $query[$wpvar] ) ) {
					$query[$wpvar] = (string) $query[$wpvar];
				} else {
					foreach ( $query[$wpvar] as $vkey => $v ) {
						if ( !is_object( $v ) ) {
							$query[$wpvar][$vkey] = (string) $v;
						}
					}
				}

				if ( isset($post_type_query_vars[$wpvar] ) ) {
					$query['post_type'] = $post_type_query_vars[$wpvar];
					$query['name'] = $query[$wpvar];
				}
			}
		}

			// Do the query
			$query = new WP_Query($query);
			if ( !empty($query->posts) && $query->is_singular )
				return $query->post->ID;
			else
				return 0;
		}
	}
	return 0;
}