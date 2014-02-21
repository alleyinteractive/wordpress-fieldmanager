<?php
/**
 * @package Fieldmanager_Datasource
 */

/**
 * Data source for WordPress Terms, for autocomplete and option types.
 * @package Fieldmanager_Datasource
 */
class Fieldmanager_Datasource_Term extends Fieldmanager_Datasource {

	/**
	 * @var string|array
	 * Taxonomy name or array of taxonomy names
	 */
	public $taxonomy = null;

	/**
	 * @var array
	 * Helper for taxonomy-based option sets; arguments to find terms
	 */
	public $taxonomy_args = array();

	/**
	 * @var boolean
	 * Sort taxonomy hierarchically and indent child categories with dashes?
	 */
	public $taxonomy_hierarchical = false;

	/**
	 * @var int
	 * How far to descend into taxonomy hierarchy (0 for no limit)
	 */
	public $taxonomy_hierarchical_depth = 0;

	/**
	 * @var boolean
	 * Pass $append = true to wp_set_object_terms?
	 */
	public $append_taxonomy = False;

	/**
	 * @var string
	 * If true, additionally save taxonomy terms to WP's terms tables.
	 */
	public $taxonomy_save_to_terms = True;

	/**
	 * @var string
	 * If true, only save this field to the taxonomy tables, and do not serialize in the FM array.
	 */
	public $only_save_to_taxonomy = False;

	/**
	 * @var boolean
	 * If true, store the term_taxonomy_id instead of the term_id
	 */
	public $store_term_taxonomy_id = False;

	/**
	 * @var boolean
	 * If true, group taxonomies
	 */
	public $grouped = False;

	/**
	 * @var boolean
	 * Build this datasource using AJAX
	 */
	public $use_ajax = True;

	/**
	 * Constructor
	 */
	public function __construct( $options = array() ) {
		global $wp_taxonomies;

		// default to showing empty tags, which generally makes more sense for the types of fields
		// that fieldmanager supports
		if ( !isset( $options['taxonomy_args']['hide_empty'] ) ) {
			$options['taxonomy_args']['hide_empty'] = False;
		}

		parent::__construct( $options );
		if ( $this->only_save_to_taxonomy ) $this->taxonomy_save_to_terms = True;

		// make post_tag and category sortable via term_order, if they're set as taxonomies, and if
		// we're not using Fieldmanager storage
		if ( $this->only_save_to_taxonomy && in_array( 'post_tag', $this->get_taxonomies() ) ) {
			$wp_taxonomies['post_tag']->sort = True;
		}
		if ( $this->only_save_to_taxonomy && in_array( 'category', $this->get_taxonomies() ) ) {
			$wp_taxonomies['category']->sort = True;
		}
	}

	/**
	 * Get taxonomies; normalizes $this->taxonomy to an array
	 * @return array of taxonomies
	 */
	public function get_taxonomies() {
		return is_array( $this->taxonomy ) ? $this->taxonomy : array( $this->taxonomy );
	}

	/**
	 * Get an action to register by hashing (non cryptographically for speed)
	 * the options that make this datasource unique.
	 * @return string ajax action
	 */
	public function get_ajax_action() {
		if ( !empty( $this->ajax_action ) ) return $this->ajax_action;
		$unique_key = json_encode( $this->taxonomy_args );
		$unique_key .= json_encode( $this->get_taxonomies() );
		$unique_key .= (string) $this->taxonomy_hierarchical;
		$unique_key .= (string) $this->taxonomy_hierarchical_depth;
		return 'fm_datasource_term_' . crc32( $unique_key );
	}

	/**
	 * Unique among FM types, the taxonomy datasource can store data outside FM's array.
	 * This is how we add it back into the array for editing.
	 * @param Fieldmanager_Field $field
	 * @param array $values
	 * @return array $values loaded up, if applicable.
	 */
	public function preload_alter_values( Fieldmanager_Field $field, $values ) {
		if ( $this->only_save_to_taxonomy ) {
			$taxonomies = $this->get_taxonomies();
			$terms = wp_get_object_terms( $field->data_id, $taxonomies[0], array( 'orderby' => 'term_order' ) );

			if ( count( $terms ) > 0 ) {
				if ( $field->limit == 1 && empty( $field->multiple ) ) {
					return $terms[0]->term_id;
				} else {
					$ret = array();
					foreach ( $terms as $term ) {
						$ret[] = $term->term_id;
					}
					return $ret;
				}
			}
		}
		return $values;
	}

	/**
	 * Presave hook to set taxonomy data
	 * @param int[] $values
	 * @param int[] $current_values
	 * @return int[] $values
	 */
	public function presave_alter_values( Fieldmanager_Field $field, $values, $current_values ) {
		if ( !is_array( $values ) ) {
			$values = array( $values );
		}

		// maybe we can create terms here.
		if ( get_class( $field ) == 'Fieldmanager_Autocomplete' && !$field->exact_match && isset( $this->taxonomy ) ) {
			foreach( $values as $i => $value ) {
				 // could be a mix of valid term IDs and new terms.
				if ( is_numeric( $value ) ) continue;

				// the JS adds a '-' to the front if it's not a found term to prevent problems with new numeric terms.
				$value = sanitize_text_field( substr( $value, 1 ) );

				// an affordance for our friends at WordPress.com
				$term_by = function_exists( 'wpcom_vip_get_term_by' ) ? 'wpcom_vip_get_term_by' : 'get_term_by';
				$term = call_user_func( $term_by, 'name', $value, $this->taxonomy );

				if ( !$term ) {
					$term = wp_insert_term( $value, $this->taxonomy );
					if ( is_wp_error( $term ) ) {
						unset( $value );
						continue;
					}
					$term = (object) $term;
				}
				$values[$i] = $term->term_id;
			}
		}

		// If this is a taxonomy-based field, must also save the value(s) as an object term
		if ( $this->taxonomy_save_to_terms && isset( $this->taxonomy ) && !empty( $values ) ) {
			$tax_values = array();
			foreach ( $values as $value ) {
				if ( !empty( $value ) ) {
					if( is_numeric( $value ) )
						$tax_values[] = $value;
					else if( is_array( $value ) )
						$tax_values = $value;
				}
			}
			$this->save_taxonomy( $tax_values, $field->data_id );
		}
		if ( $this->only_save_to_taxonomy ) return array();
		return $values;
	}

	/**
	 * Sanitize a value
	 */
	public function presave( Fieldmanager_Field $field, $value, $current_value ) {
		return intval( $value );
	}

	/**
	 * Save taxonomy data
	 * @param mixed[] $tax_values
	 * @return void
	 */
	public function save_taxonomy( $tax_values, $data_id ) {

		$tax_values = array_map( 'intval', $tax_values );
		$tax_values = array_unique( $tax_values );
		$taxonomies = $this->get_taxonomies();

		// Store the each term for this post. Handle grouped fields differently since multiple taxonomies are present.
		if ( count( $taxonomies ) > 1 ) {
			// Build the taxonomy insert data
			$taxonomies_to_save = array();
			foreach ( $tax_values as $term_id ) {
				$term = $this->get_term( $term_id );
				if ( empty( $taxonomies_to_save[ $term->taxonomy ] ) ) $taxonomies_to_save[ $term->taxonomy ] = array();
				$taxonomies_to_save[ $term->taxonomy ][] = $term_id;
			}
			foreach ( $taxonomies_to_save as $taxonomy => $terms ) {
				wp_set_object_terms( $data_id, $terms, $taxonomy, $this->append_taxonomy );
			}
		} else {
			wp_set_object_terms( $data_id, $tax_values, $taxonomies[0], $this->append_taxonomy );
		}
	}

	/**
	 * Get taxonomy data per $this->taxonomy_args
	 * @param $value The value(s) currently set for this field
	 * @return array[] data entries for options
	 */
	public function get_items( $fragment = Null ) {

		// If taxonomy_hierarchical is set, assemble recursive term list, then bail out.
		if ( $this->taxonomy_hierarchical ) {
			$tax_args = $this->taxonomy_args;
			$tax_args['parent'] = 0;
			$parent_terms = get_terms( $this->get_taxonomies(), $tax_args );
			return $this->build_hierarchical_term_data( $parent_terms, $this->taxonomy_args, 0, $fragment );
		}

		$tax_args = $this->taxonomy_args;
		if ( !empty( $fragment ) ) $tax_args['search'] = $fragment;
		$terms = get_terms( $this->get_taxonomies(), $tax_args );

		// If the taxonomy list was an array and group display is set, ensure all terms are grouped by taxonomy
		// Use the order of the taxonomy array list for sorting the groups to make this controllable for developers
		// Order of the terms within the groups is already controllable via $taxonomy_args
		// Skip this entirely if there is only one taxonomy even if group display is set as it would be unnecessary
		if ( count( $this->get_taxonomies() ) > 1 && $this->grouped && $this->allow_optgroups ) {
			// Group the data
			$term_groups = array();
			foreach ( $this->get_taxonomies() as $tax ) {
				$term_groups[$tax] = array();
			}
			foreach ( $terms as $term ) {
				$term_groups[$term->taxonomy][ $term->term_id ] = $term->name;
			}
			return $term_groups;
		}

		// Put the taxonomy data into the proper data structure to be used for display
		foreach ( $terms as $term ) {
			// Store the label for the taxonomy as the group since it will be used for display
			$key = $this->store_term_taxonomy_id ? $term->term_taxonomy_id : $term->term_id;
			$stack[ $key ] = $term->name;
		}
		return apply_filters( 'fm_datasource_term_get_items', $stack, $terms, $this, $fragment );
	}

	/**
	 * Helper to support recursive building of a hierarchical taxonomy list.
	 * @param array $parent_terms
	 * @param array $tax_args as used in top-level get_terms() call.
	 * @param int $depth current recursive depth level.
	 * @param string $fragment optional matching pattern
	 * @return array of terms or false if no children found.
	 */
	protected function build_hierarchical_term_data( $parent_terms, $tax_args, $depth, $stack = array(), $pattern = '' ) {

		// Walk through each term passed, add it (at current depth) to the data stack.
		foreach ( $parent_terms as $term ) {
			$taxonomy_data = get_taxonomy( $term->taxonomy );
			$prefix = '';

			// Prefix term based on depth. For $depth = 0, prefix will remain empty.
			for ( $i = 0; $i < $depth; $i++ ) {
				$prefix .= '--';
			}

			$key = $this->store_term_taxonomy_id ? $term->term_taxonomy_id : $term->term_id;
			$stack[ $key ] = $prefix . ' ' . $term->name;

			// Find child terms of this. If any, recurse on this function.
			$tax_args['parent'] = $term->term_id;
			if ( !empty( $pattern ) ) $tax_args['search'] = $fragment;
			$child_terms = get_terms( $this->get_taxonomies(), $tax_args );
			if ( $this->taxonomy_hierarchical_depth == 0 || $depth + 1 < $this->taxonomy_hierarchical_depth ) {
				if ( !empty( $child_terms ) ) {
					$stack = $this->build_hierarchical_term_data( $child_terms, $this->taxonomy_args, $depth + 1, $stack );
				}
			}
		}
		return $stack;
	}

	/**
	 * Translate term id to title, e.g. for autocomplete
	 * @param mixed $value
	 * @return string
	 */
	public function get_value( $value ) {
		$id = intval( $value );
		if ( ! $id )
			return null;

		$term = $this->get_term( $id );
		$value = is_object( $term ) ? $term->name : '';
		return apply_filters( 'fm_datasource_term_get_value', $value, $term, $this );
	}

	/**
	 * Get term by ID only, potentially using multiple taxonomies
	 * @param int $term_id
	 * @return object|null
	 */
	private function get_term( $term_id ) {
		if ( $this->store_term_taxonomy_id ) {
			global $wpdb;
			return $wpdb->get_row( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.term_taxonomy_id = %d LIMIT 1", $term_id ) );
		} else {
			$terms = get_terms( $this->get_taxonomies(), array( 'hide_empty' => false, 'include' => array( $term_id ), 'number' => 1 ) );
			return !empty( $terms[0] ) ? $terms[0] : Null;
		}
	}

	/**
	 * Get link to view a term
	 * @param int $value term id
	 * @return string
	 */
	public function get_view_link( $value ) {
		return sprintf(
			' <a target="_new" class="fm-autocomplete-view-link %s" href="%s">%s</a>',
			empty( $value ) ? 'fm-hidden' : '',
			empty( $value ) ? '#' : get_term_link( $this->get_term( $value ) ),
			__( 'View' )
		);
	}

	/**
	 * Get link to edit a term
	 * @param int $value term id
	 * @return string
	 */
	public function get_edit_link( $value ) {
		return edit_term_link( __( 'Edit' ), '', '', $this->get_term( $value ), False );
	}

}