<?php
/**
 * Use fieldmanager to store meta data for taxonomy terms.
 *
 * This is deprecated as of WordPress 4.4, which introduces Term Meta into core.
 * If you were using this feature prior to 4.4, it will continue to operate
 * until probably WordPress 4.8, however it is in your best interest to migrate
 * your data ASAP, as core's term meta is significantly more efficient.
 *
 * @deprecated 1.0.0-beta.3
 *
 * @package Fieldmanager_Util
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
		add_action( 'split_shared_term', array( $this, 'split_shared_term' ), 10, 4 );
	}

	/**
	 * Create the custom content type
	 *
	 * @return void
	 */
	public function create_content_type() {
		register_post_type( $this->post_type, array(
			'rewrite' => false,
			'label'   => __( 'Fieldmanager Term Metadata', 'fieldmanager' ),
		) );
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
	 * Get metadata matching the specified key for the given term ID/taxonomy
	 * pair.
	 *
	 * @param int $term_id Term ID.
	 * @param string $taxonomy Taxonomy name that $term_id is part of.
	 * @param string $meta_key Metadata name.
	 * @param boolean $single Optional. Get a single result or multiple.
	 * @return string|array @see get_post_meta().
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
	 * Add metadata to a term ID/taxonomy pair.
	 *
	 * @param int $term_id Term ID.
	 * @param string $taxonomy Taxonomy name that $term_id is part of.
	 * @param string $meta_key Metadata name.
	 * @param mixed $meta_value The value of the custom field which should be
	 *                          added. If an array is given, it will be
	 *                          serialized into a string.
	 * @param boolean $unique Optional. Whether or not you want the key to stay
	 *                        unique. When set to true, the custom field will
	 *                        not be added if the given key already exists among
	 *                        custom fields of the specified post.
	 * @return boolean|integer @see add_post_meta().
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
	 * Update metadata for a term ID/taxonomy pair.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with
	 * the same key and post ID.
	 *
	 * If the meta field for the term does not exist, it will be added.
	 *
	 * @param int $term_id Term ID.
	 * @param string $taxonomy Taxonomy name that $term_id is part of.
	 * @param string $meta_key Metadata name.
	 * @param mixed $meta_value The new value of the custom field. A passed
	 *                          array will be serialized into a string.
	 * @param mixed $meta_prev_value Optional. The old value of the custom field
	 *                               you wish to change. This is to
	 *                               differentiate between several fields with
	 *                               the same key. If omitted, and there are
	 *                               multiple rows for this post and meta key,
	 *                               all meta values will be updated.
	 * @return mixed @see update_post_meta().
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
	 * Remove metadata matching criteria from a term ID/taxonomy pair.
	 *
	 * You can match based on the key, or key and value. Removing based on key
	 * and value, will keep from removing duplicate metadata with the same key.
	 * It also allows removing all metadata matching key, if needed.
	 *
	 * @param int $term_id Term ID.
	 * @param string $taxonomy Taxonomy name that $term_id is part of.
	 * @param string $meta_key Metadata name.
	 * @param mixed $meta_value Optional. The value of the field you will
	 *                          delete. This is used to differentiate between
	 *                          several fields with the same key. If left blank,
	 *                          all fields with the given key will be deleted.
	 * @return boolean False for failure. True for success.
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
				'post_type' => $this->post_type,
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

	/**
	 * Update term meta when a shared term gets split (as of WordPress 4.2).
	 *
	 * @param  int $old_term_id The pre-split (previously shared) term ID.
	 * @param  int $new_term_id The post-split term ID.
	 * @param  int $term_taxonomy_id The term_taxonomy_id for this term. Note
	 *                               that this doesn't change when a shared term
	 *                               is split (since it's already unique).
	 * @param  string $taxonomy The taxonomy of the *split* term.
	 */
	public function split_shared_term( $old_term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
		if ( false != ( $post_id = $this->get_term_meta_post_id( $old_term_id, $taxonomy ) ) ) {
			wp_update_post( array(
				'ID' => $post_id,
				'post_name' => 	$this->post_slug( $new_term_id, $taxonomy ),
				'post_title' => $this->post_slug( $new_term_id, $taxonomy ),
			) );
			$this->delete_term_meta_post_id_cache( $old_term_id, $taxonomy );
		}
	}
}

/**
 * Singleton helper for Fieldmanager_Util_Term_Meta
 *
 * @deprecated 1.0.0-beta.3
 *
 * @return object
 */
function Fieldmanager_Util_Term_Meta() {
	return Fieldmanager_Util_Term_Meta::instance();
}
Fieldmanager_Util_Term_Meta();


/**
 * Shortcut helper for Fieldmanager_Util_Term_Meta::get_term_meta().
 *
 * @deprecated 1.0.0-beta.3
 *
 * @see Fieldmanager_Util_Term_Meta::get_term_meta()
 */
function fm_get_term_meta( $term_id, $taxonomy, $meta_key = '', $single = false ) {
	return Fieldmanager_Util_Term_Meta()->get_term_meta( $term_id, $taxonomy, $meta_key, $single );
}

/**
 * Shortcut helper for Fieldmanager_Util_Term_Meta::add_term_meta().
 *
 * @deprecated 1.0.0-beta.3
 *
 * @see Fieldmanager_Util_Term_Meta::add_term_meta()
 */
function fm_add_term_meta( $term_id, $taxonomy, $meta_key, $meta_value, $unique = false ) {
	return Fieldmanager_Util_Term_Meta()->add_term_meta( $term_id, $taxonomy, $meta_key, $meta_value, $unique );
}

/**
 * Shortcut helper for Fieldmanager_Util_Term_Meta::update_term_meta().
 *
 * @deprecated 1.0.0-beta.3
 *
 * @see Fieldmanager_Util_Term_Meta::update_term_meta()
 */
function fm_update_term_meta( $term_id, $taxonomy, $meta_key, $meta_value, $meta_prev_value = '' ) {
	return Fieldmanager_Util_Term_Meta()->update_term_meta( $term_id, $taxonomy, $meta_key, $meta_value, $meta_prev_value );
}

/**
 * Shortcut helper for Fieldmanager_Util_Term_Meta::delete_term_meta().
 *
 * @deprecated 1.0.0-beta.3
 *
 * @see Fieldmanager_Util_Term_Meta::delete_term_meta()
 */
function fm_delete_term_meta( $term_id, $taxonomy, $meta_key, $meta_value = '' ) {
	return Fieldmanager_Util_Term_Meta()->delete_term_meta( $term_id, $taxonomy, $meta_key, $meta_value );
}

