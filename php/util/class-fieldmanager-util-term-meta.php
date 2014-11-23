<?php
/**
 * @package Fieldmanager_Util
 */

/**
 * Use fieldmanager to store meta data for taxonomy terms
 */
class Fieldmanager_Util_Term_Meta {

	/**
	 * Instance of the class
	 *
	 * @var Term_Meta
	 * @access private
	 */
	private static $instance;

	/**
	 * Post type name
	 *
	 * @var string
	 * @access private
	 */
	private $post_type = 'fm-term-meta';

	/**
	 * Singleton helper
	 *
	 * @return object The singleton instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Fieldmanager_Util_Term_Meta;
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Sets up the class
	 *
	 * @access public
	 * @return void
	 */
	public function setup() {
		add_action( 'init', array( $this, 'create_content_type' ) );
		add_action( 'delete_term', array( $this, 'collect_garbage' ), 10, 3 );
	}

	/**
	 * Create the custom content type
	 *
	 * @return void
	 */
	public function create_content_type() {
		$args = array(
			'public' => false,
			'publicly_queryable' => false,
			'exclude_from_search' => false,
			'query_var' => $this->post_type,
			'rewrite' => false,
			'show_ui' => false,
			'capability_type' => 'post',
			'hierarchical' => true,
			'has_archive' => false
		);
		register_post_type( $this->post_type, $args );
	}

	/**
	 * Get the slug (post_name and post_title) for the fm_term_meta post.
	 *
	 * @param  int $term_id
	 * @param  string $taxonomy
	 * @return string
	 */
	protected function post_slug( $term_id, $taxonomy ) {
		return $this->post_type . '-' . $term_id . '-' . $taxonomy;
	}

	/**
	 * Handles getting metadata for taxonomy terms
	 * @param int $term_id
	 * @param string $taxonomy
	 * @param string $meta_key
	 * @param string $meta_value optional
	 * @return bool
	 */
	public function get_term_meta( $term_id, $taxonomy, $meta_key = '', $single = false ) {

		// Check if this term has a post to store meta data
		$term_meta_post_id = $this->get_term_meta_post_id( $term_id, $taxonomy );
		if ( false === $term_meta_post_id ) {

			// If not, exit. There is no meta data for this term at all.
			// Mimic the normal return behavior of get_post_meta
			return $single ? '' : array();
		}

		// Get the meta data
		return get_post_meta( $term_meta_post_id, $meta_key, $single );

	}

	/**
	 * Handles adding metadata for taxonomy terms
	 * @param int $term_id
	 * @param string $taxonomy
	 * @param string $meta_key
	 * @param string $meta_value
	 * @param bool $unique optional
	 * @return bool
	 */
	public function add_term_meta( $term_id, $taxonomy, $meta_key, $meta_value, $unique = false ) {

		// Check if this term already has a post to store meta data
		$term_meta_post_id = $this->get_term_meta_post_id( $term_id, $taxonomy );
		if ( false === $term_meta_post_id ) {

			// If not, create the post to store the metadata
			$term_meta_post_id = $this->add_term_meta_post( $term_id, $taxonomy );

			// Check for errors
			if ( false === $term_meta_post_id ) {
				return false;
			}
		}

		// Add this key/value pair as post meta data
		return add_post_meta( $term_meta_post_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Handles updating metadata for taxonomy terms
	 * @param int $term_id
	 * @param string $taxonomy
	 * @param string $meta_key
	 * @param string $meta_value optional
	 * @return bool
	 */
	public function update_term_meta( $term_id, $taxonomy, $meta_key, $meta_value, $meta_prev_value='' ) {

		// Check if this term already has a post to store meta data
		$term_meta_post_id = $this->get_term_meta_post_id( $term_id, $taxonomy );
		if ( false === $term_meta_post_id ) {

			// If not, create the post to store the metadata
			$term_meta_post_id = $this->add_term_meta_post( $term_id, $taxonomy );

			// Check for errors
			if ( false === $term_meta_post_id ) {
				return false;
			}
		}

		// Add this key/value pair as post meta data
		return update_post_meta( $term_meta_post_id, $meta_key, $meta_value, $meta_prev_value );
	}

	/**
	 * Handles deleting metadata for taxonomy terms
	 * @param int $term_id
	 * @param string $taxonomy
	 * @param string $meta_key
	 * @param string $meta_value
	 * @return bool
	 */
	public function delete_term_meta( $term_id, $taxonomy, $meta_key, $meta_value='' ) {

		// Get the post used for this term
		$term_meta_post_id = $this->get_term_meta_post_id( $term_id, $taxonomy );

		// If no post exist, there is nothing further to do here. This is not necessarily an error.
		if ( false === $term_meta_post_id ) {
			return false;
		}

		// Remove the meta data
		$result = delete_post_meta( $term_meta_post_id, $meta_key, $meta_value );

		// Check if this term has any metadata at all
		$post_terms = get_post_meta( $term_meta_post_id );
		if ( empty( $post_terms ) ) {
			// If not, remove the post to store the metadata to free up space in wp_posts
			wp_delete_post( $term_meta_post_id, true );
			$this->delete_term_meta_post_id_cache( $term_id, $taxonomy );
		}

		return $result;
	}

	/**
	 * Handles checking if post exists and returning its ID to store taxonomy term meta data.
	 * @param int $term_id
	 * @param string $taxonomy
	 * @return int|false post id or false
	 */
	public function get_term_meta_post_id( $term_id, $taxonomy ) {
		$cache_key = $this->get_term_meta_post_id_cache_key( $term_id, $taxonomy );

		if ( false === ( $term_meta_post_id = wp_cache_get( $cache_key ) ) ) {
			// Check if a post exists for this term
			$query = new WP_Query( array(
				'name' => $this->post_slug( $term_id, $taxonomy ),
				'post_type' => $this->post_type
			) );

			// Return the post ID if it exists, otherwise false
			if ( $query->have_posts() ) {
				$query->next_post();
				$term_meta_post_id = $query->post->ID;
			} else {
				$term_meta_post_id = 'none';
			}

			wp_cache_set( $cache_key, $term_meta_post_id );
		}

		return 'none' === $term_meta_post_id ? false : $term_meta_post_id;
	}

	/**
	 * Generates a standardized cache key for the term meta post id.
	 * @param int $term_id
	 * @param string $taxonomy
	 * @return string cache key
	 */
	public function get_term_meta_post_id_cache_key( $term_id, $taxonomy ) {
		return "fm_tm_{$term_id}_{$taxonomy}";
	}

	/**
	 * Clears the cache for a term meta post id. @uses wp_cache_delete
	 * @param int $term_id
	 * @param string $taxonomy
	 * @return void
	 */
	public function delete_term_meta_post_id_cache( $term_id, $taxonomy ) {
		wp_cache_delete( $this->get_term_meta_post_id_cache_key( $term_id, $taxonomy ) );
	}

	/**
	 * Handles adding a post to store taxonomy term meta data
	 * @param int $term_id
	 * @return bool
	 */
	public function add_term_meta_post( $term_id, $taxonomy ) {
		$this->delete_term_meta_post_id_cache( $term_id, $taxonomy );

		// Add the skeleton post to store meta data for this taxonomy term
		$result = wp_insert_post(
			array(
				'post_name' => 	$this->post_slug( $term_id, $taxonomy ),
				'post_title' => $this->post_slug( $term_id, $taxonomy ),
				'post_type' => $this->post_type,
				'post_status' => 'publish'
			)
		);

		// Check the result
		if ( $result != 0 ) {
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * When a term is deleted, delete its ghost post and related meta.
	 *
	 * @param int $term_id
	 * @param int $tt_id
	 * @param string $taxonomy
	 */
	public function collect_garbage( $term_id, $tt_id, $taxonomy ) {
		if ( $post = get_page_by_path( $this->post_slug( $term_id, $taxonomy ), OBJECT, $this->post_type ) ) {
			wp_delete_post( $post->ID, true );
		}
	}
}

/**
 * Singleton helper for Fieldmanager_Util_Term_Meta
 *
 * @return object
 */
function Fieldmanager_Util_Term_Meta() {
	return Fieldmanager_Util_Term_Meta::instance();
}
Fieldmanager_Util_Term_Meta();


/*
 * Helper Functions to simplify the process
 */

function fm_get_term_meta( $term_id, $taxonomy, $meta_key = '', $single = false ) {
	return Fieldmanager_Util_Term_Meta()->get_term_meta( $term_id, $taxonomy, $meta_key, $single );
}

function fm_add_term_meta( $term_id, $taxonomy, $meta_key, $meta_value, $unique = false ) {
	return Fieldmanager_Util_Term_Meta()->add_term_meta( $term_id, $taxonomy, $meta_key, $meta_value, $unique );
}

function fm_update_term_meta( $term_id, $taxonomy, $meta_key, $meta_value, $meta_prev_value = '' ) {
	return Fieldmanager_Util_Term_Meta()->update_term_meta( $term_id, $taxonomy, $meta_key, $meta_value, $meta_prev_value );
}

function fm_delete_term_meta( $term_id, $taxonomy, $meta_key, $meta_value = '' ) {
	return Fieldmanager_Util_Term_Meta()->delete_term_meta( $term_id, $taxonomy, $meta_key, $meta_value );
}

