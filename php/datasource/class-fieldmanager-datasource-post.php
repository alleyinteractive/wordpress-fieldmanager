<?php
/**
 * Class file for Fieldmanager_Datasource_Post
 *
 * @package Fieldmanager
 */

/**
 * Datasource to populate autocomplete and option fields with WordPress Posts.
 */
class Fieldmanager_Datasource_Post extends Fieldmanager_Datasource {

	/**
	 * Supply a function which returns a list of posts; takes one argument,
	 * a possible fragment.
	 *
	 * @var null
	 */
	public $query_callback = null;

	/**
	 * Arguments to get_posts(), which uses WP's defaults, plus
	 * suppress_filters = False, which can be overriden by setting
	 * suppress_filters = True here.
	 *
	 * @see http://codex.wordpress.org/Template_Tags/get_posts
	 *
	 * @var array
	 */
	public $query_args = array();

	/**
	 * Allow Ajax. If set to false, Autocomplete will pre-load get_items() with no fragment,
	 * so False could cause performance problems.
	 *
	 * @var bool
	 */
	public $use_ajax = true;

	/**
	 * If not empty, set this post's ID as a value on the linked post. This is used to
	 * establish two-way relationships.
	 *
	 * @var string
	 */
	public $reciprocal = null;

	/**
	 * Display the post publish date in the typeahead menu.
	 *
	 * @var bool
	 */
	public $show_date = false;

	/**
	 * If $show_date is true, the format to use for displaying the date.
	 *
	 * @var string
	 */
	public $date_format = 'Y-m-d';

	/**
	 * Publish the child post when/if the parent is published.
	 *
	 * @var bool
	 */
	public $publish_with_parent = false;

	/**
	 * Save to post parent.
	 *
	 * @var bool
	 */
	public $save_to_post_parent = false;

	/**
	 * Only save to post parent.
	 *
	 * @var bool
	 */
	public $only_save_to_post_parent = false;

	/**
	 * Construct the object.
	 *
	 * @param array $options The datasource options.
	 */
	public function __construct( $options = array() ) {
		parent::__construct( $options );

		// Infer $save_to_post_parent if $only_save_to_post_parent.
		if ( $this->only_save_to_post_parent ) {
			$this->save_to_post_parent = true;
		}
	}

	/**
	 * Get a post title by post ID.
	 *
	 * @param int $value Post ID.
	 * @return string Post title.
	 */
	public function get_value( $value ) {
		$id = intval( $value );
		return $id ? get_the_title( $id ) : '';
	}

	/**
	 * Get posts which match this datasource, optionally filtered by
	 * a fragment, e.g. for Autocomplete.
	 *
	 * @param  string $fragment The query string.
	 * @return array The post_id => post_title for display or Ajax.
	 */
	public function get_items( $fragment = null ) {
		if ( is_callable( $this->query_callback ) ) {
			return call_user_func( $this->query_callback, $fragment );
		}
		$default_args = array(
			'numberposts'      => 10,
			'orderby'          => 'post_date',
			'order'            => 'DESC',
			'post_status'      => 'publish',
			'post_type'        => 'any',
			'suppress_filters' => false,
		);
		$post_args    = array_merge( $default_args, $this->query_args );
		$ret          = array();
		if ( $fragment ) {
			$post_id    = null;
			$exact_post = null;
			if ( preg_match( '/^https?\:/i', $fragment ) ) {
				$url       = esc_url( $fragment );
				$url_parts = parse_url( $url );

				if ( ! empty( $url_parts['query'] ) ) {
					$get_vars = array();
					parse_str( $url_parts['query'], $get_vars );
				}

				if ( ! empty( $get_vars['post'] ) ) {
					$post_id = intval( $get_vars['post'] );
				} elseif ( ! empty( $get_vars['p'] ) ) {
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
					'any' == $post_args['post_type'] ||
					$post_args['post_type'] == $exact_post->post_type ||
					in_array( $exact_post->post_type, $post_args['post_type'] )
				) ) {
					if ( $this->show_date ) {
						$date_pad = ' (' . date( $this->date_format, strtotime( $exact_post->post_date ) ) . ')';
					} else {
						$date_pad = '';
					}
					$ret[ $post_id ] = html_entity_decode( $exact_post->post_title ) . $date_pad;
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
			if ( $this->show_date ) {
				$date_pad = ' (' . date( $this->date_format, strtotime( $p->post_date ) ) . ')';
			} else {
				$date_pad = '';
			}
			$ret[ $p->ID ] = $p->post_title . $date_pad;
		}
		return $ret;
	}

	/**
	 * Get an action to register by hashing (non cryptographically for speed)
	 * the options that make this datasource unique.
	 *
	 * @return string Ajax action.
	 */
	public function get_ajax_action() {
		if ( ! empty( $this->ajax_action ) ) {
			return $this->ajax_action;
		}
		$unique_key  = json_encode( $this->query_args );
		$unique_key .= (string) $this->query_callback;
		return 'fm_datasource_post' . crc32( $unique_key );
	}

	/**
	 * Perform a LIKE search on post_title, since 's' in WP_Query is too fuzzy
	 * when trying to autocomplete a title.
	 *
	 * @param string   $where    The where clause.
	 * @param WP_Query $wp_query The reference to teh query object.
	 */
	public function title_like( $where, $wp_query ) {
		global $wpdb;
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like( $this->_fragment ) . '%' );
		return $where;
	}

	/**
	 * For post relationships, delete reciprocal post metadata prior to saving
	 * (presave will re-add).
	 *
	 * @param Fieldmanager_Field $field          The field.
	 * @param array              $values         New post values.
	 * @param array              $current_values Existing post values.
	 */
	public function presave_alter_values( Fieldmanager_Field $field, $values, $current_values ) {
		if ( 'post' == $field->data_type && ! empty( $this->reciprocal ) && ! empty( $current_values ) && is_array( $current_values ) ) {
			foreach ( $current_values as $reciprocal_post_id ) {
				delete_post_meta( $reciprocal_post_id, $this->reciprocal, $field->data_id );
			}
		}

		return $values;
	}

	/**
	 * Handle reciprocal postmeta and post parents.
	 *
	 * @param Fieldmanager_Field $field         The field.
	 * @param array              $value         New post value.
	 * @param array              $current_value Existing post value.
	 * @return string
	 */
	public function presave( Fieldmanager_Field $field, $value, $current_value ) {
		if ( empty( $value ) ) {
			return;
		}
		$value = intval( $value );

		if ( ! empty( $this->publish_with_parent ) || ! empty( $this->reciprocal ) ) {
			// There are no permissions in cron, but no changes are coming from a user either.
			if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
				$post_type_obj = get_post_type_object( get_post_type( $value ) );
				if ( empty( $post_type_obj->cap->edit_post ) || ! current_user_can( $post_type_obj->cap->edit_post, $value ) ) {
					/* translators: 1: post type object name, 2: post ID value being saved, 3: field name */
					wp_die( esc_html( sprintf( __( 'Tried to alter %1$s %2$d through field "%3$s", which user is not permitted to edit.', 'fieldmanager' ), $post_type_obj->name, $value, $field->name ) ) );
				}
			}
			$this->presave_status_transition( $field, $value );
			if ( $this->reciprocal ) {
				add_post_meta( $value, $this->reciprocal, $field->data_id );
			}
		}

		if ( $this->save_to_post_parent && 1 == $field->limit && 'post' == $field->data_type ) {
			if ( ! wp_is_post_revision( $field->data_id ) ) {
				Fieldmanager_Context_Post::safe_update_post(
					array(
						'ID'          => $field->data_id,
						'post_parent' => $value,
					)
				);
			}
			if ( $this->only_save_to_post_parent ) {
				return array();
			}
		}

		return $value;
	}

	/**
	 * Handle any actions based on the parent's status transition.
	 *
	 * @param Fieldmanager_Field $field The parent.
	 * @param int                $value The child post id.
	 */
	public function presave_status_transition( Fieldmanager_Field $field, $value ) {
		// if this child post is in a post (or quickedit) context on a published post, publish the child also.
		if ( $this->publish_with_parent && 'post' === $field->data_type && ! empty( $field->data_id ) && 'publish' === get_post_status( $field->data_id ) ) {
			// use wp_update_post so that post_name is generated if it's not been already.
			wp_update_post(
				array(
					'ID'          => $value,
					'post_status' => 'publish',
				)
			);
		}
	}

	/**
	 * Preload alter values for post parent
	 * The post datasource can store data outside FM's array.
	 * This is how we add it back into the array for editing.
	 *
	 * @param  Fieldmanager_Field $field  The field.
	 * @param  array              $values The loaded values.
	 * @return array $values Loaded up, if applicable.
	 */
	public function preload_alter_values( Fieldmanager_Field $field, $values ) {
		if ( $this->only_save_to_post_parent ) {
			$post_parent = wp_get_post_parent_id( $field->data_id );
			if ( $post_parent ) {
				return ( 1 == $field->limit && empty( $field->multiple ) ) ? $post_parent : array( $post_parent );
			}
		}
		return $values;
	}

	/**
	 * Get edit link for a post.
	 *
	 * @param int $value The current value.
	 * @return string HTML string.
	 */
	public function get_view_link( $value ) {
		return sprintf(
			' <a target="_new" class="fm-autocomplete-view-link %s" href="%s">%s</a>',
			empty( $value ) ? 'fm-hidden' : '',
			empty( $value ) ? '#' : esc_url( get_permalink( $value ) ),
			esc_html__( 'View', 'fieldmanager' )
		);
	}

	/**
	 * Get edit link for a post.
	 *
	 * @param int $value The current value.
	 * @return string HTML string.
	 */
	public function get_edit_link( $value ) {
		return sprintf(
			' <a target="_new" class="fm-autocomplete-edit-link %s" href="%s">%s</a>',
			empty( $value ) ? 'fm-hidden' : '',
			empty( $value ) ? '#' : esc_url( get_edit_post_link( $value ) ),
			esc_html__( 'Edit', 'fieldmanager' )
		);
	}

}

/**
 * Post URLs to IDs function, supports custom post types.
 * Borrowed and modified from url_to_postid() in wp-includes/rewrite.php.
 *
 * @author http://betterwp.net/
 *
 * @param  string $url The post URL.
 * @return int|boolean Post ID on success, false on failure
 */
function fm_url_to_post_id( $url ) {
	global $wp_rewrite;

	$url = apply_filters( 'url_to_postid', $url ); // See #532. prefix ok.

	// First, check to see if there is a 'p=N' or 'page_id=N' to match against.
	if ( preg_match( '#[?&](p|page_id|attachment_id)=(\d+)#', $url, $values ) ) {
		$id = absint( $values[2] );
		if ( $id ) {
			return $id;
		}
	}

	// Check to see if we are using rewrite rules.
	$rewrite = $wp_rewrite->wp_rewrite_rules();

	// Not using rewrite rules, and 'p=N' and 'page_id=N' methods failed, so we're out of options.
	if ( empty( $rewrite ) ) {
		return 0;
	}

	// Get rid of the #anchor.
	$url_split = explode( '#', $url );
	$url       = $url_split[0];

	// Get rid of URL ?query=string.
	$url_split = explode( '?', $url );
	$url       = $url_split[0];

	// Add 'www.' if it is absent and should be there.
	if (
		false !== strpos( home_url(), '://www.' )
		&& false === strpos( $url, '://www.' )
	) {
		$url = str_replace( '://', '://www.', $url );
	}

	// Strip 'www.' if it is present and shouldn't be.
	if ( false === strpos( home_url(), '://www.' ) ) {
		$url = str_replace( '://www.', '://', $url );
	}

	// Strip 'index.php/' if we're not using path info permalinks.
	if ( ! $wp_rewrite->using_index_permalinks() ) {
		$url = str_replace( 'index.php/', '', $url );
	}

	if ( false !== strpos( $url, home_url() ) ) {
		// Chop off http://domain.com.
		$url = str_replace( home_url(), '', $url );
	} else {
		// Chop off /path/to/blog.
		$home_path = wp_parse_url( home_url() );
		$home_path = isset( $home_path['path'] ) ? $home_path['path'] : '';
		$url       = str_replace( $home_path, '', $url );
	}

	// Trim leading and lagging slashes.
	$url = trim( $url, '/' );

	$request = $url;
	// Look for matches.
	$request_match = $request;
	foreach ( (array) $rewrite as $match => $query ) {
		// If the requesting file is the anchor of the match, prepend it
		// to the path info.
		if ( ! empty( $url ) && ( $url != $request ) && ( strpos( $match, $url ) === 0 ) ) {
			$request_match = $url . '/' . $request;
		}

		if ( preg_match( "!^$match!", $request_match, $matches ) ) {
			// Got a match.
			// Trim the query of everything up to the '?'.
			$query = preg_replace( '!^.+\?!', '', $query );

			// Substitute the substring matches into the query.
			$query = addslashes( WP_MatchesMapRegex::apply( $query, $matches ) );

			// Filter out non-public query vars.
			global $wp;
			parse_str( $query, $query_vars );
			$query = array();
			foreach ( (array) $query_vars as $key => $value ) {
				if ( in_array( $key, $wp->public_query_vars ) ) {
					$query[ $key ] = $value;
				}
			}

			// Taken from class-wp.php.
			foreach ( $GLOBALS['wp_post_types'] as $post_type => $t ) {
				if ( $t->query_var ) {
					$post_type_query_vars[ $t->query_var ] = $post_type;
				}
			}

			foreach ( $wp->public_query_vars as $wpvar ) {
				if ( isset( $wp->extra_query_vars[ $wpvar ] ) ) {
					$query[ $wpvar ] = $wp->extra_query_vars[ $wpvar ];
				} elseif ( isset( $_POST[ $wpvar ] ) ) { // WPCS: input var okay. CSRF okay.
					$query[ $wpvar ] = wp_unslash( $_POST[ $wpvar ] ); // WPCS: input var okay. CSRF okay. Sanitization okay.
				} elseif ( isset( $_GET[ $wpvar ] ) ) {  // WPCS: input var okay. CSRF okay.
					$query[ $wpvar ] = wp_unslash( $_GET[ $wpvar ] );  // WPCS: input var okay. CSRF okay. Sanitization okay.
				} elseif ( isset( $query_vars[ $wpvar ] ) ) {
					$query[ $wpvar ] = $query_vars[ $wpvar ];
				}

				if ( ! empty( $query[ $wpvar ] ) ) {
					if ( ! is_array( $query[ $wpvar ] ) ) {
						$query[ $wpvar ] = (string) $query[ $wpvar ];
					} else {
						foreach ( $query[ $wpvar ] as $vkey => $v ) {
							if ( ! is_object( $v ) ) {
								$query[ $wpvar ][ $vkey ] = (string) $v;
							}
						}
					}

					if ( isset( $post_type_query_vars[ $wpvar ] ) ) {
						$query['post_type'] = $post_type_query_vars[ $wpvar ];
						$query['name']      = $query[ $wpvar ];
					}
				}
			}

			// Do the query.
			$query = new WP_Query( $query );
			if ( ! empty( $query->posts ) && $query->is_singular ) {
				return $query->post->ID;
			} else {
				return 0;
			}
		}
	}
	return 0;
}
