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
		return get_the_title( $id );
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
		);
		$post_args = array_merge( $default_args, $this->query_args );
		if ( $fragment ) {
			$post_args['suppress_filters'] = False;
			$this->_fragment = $fragment;
			add_filter( 'posts_where', array( $this, 'title_like' ), 10, 2 );
		}
		$posts = get_posts( $post_args );
		if ( $fragment ) {
			remove_filter( 'posts_where', array( $this, 'title_like' ), 10, 2 );
		}
		$ret = array();
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
	public function presave_alter( Fieldmanager_Field $field, $values, $current_values ) {
		if ( $field->data_type != 'post' || !$this->reciprocal ) return;
		foreach ( $current_values as $reciprocal_post_id ) {
			delete_post_meta( $reciprocal_post_id, $this->reciprocal, $field->data_id );
		}
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