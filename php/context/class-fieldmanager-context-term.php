<?php

/**
 * Use fieldmanager to create meta boxes on the new/edit term screens and save
 * data primarily to term meta.
 *
 * @package Fieldmanager_Context
 */
class Fieldmanager_Context_Term extends Fieldmanager_Context_Storable {
	/**
	 * @var string
	 * Title of field to display in standard term form field location
	 */
	public $title = '';

	/**
	 * @var string[]
	 * What taxonomies to render these fields
	 */
	public $taxonomies = array();

	/**
	 * @var boolean
	 * Whether or not to show the fields on the term add form
	 */
	public $show_on_add = true;

	/**
	 * @var boolean
	 * Whether or not to show the fields on the term edit form
	 */
	public $show_on_edit = true;

	/**
	 * @var int
	 * Only show this field on child terms of this parent
	 */
	public $parent = '';

	/**
	 * @var array
	 * Field names reserved for WordPress on the term add/edit forms
	 */
	public $reserved_fields = array( 'name', 'slug', 'description' );

	/**
	 * Use FM term meta or WordPres core term meta. The default is a bit
	 * confusing: technically, it's to use core's term meta, but if the class is
	 * instantiated using the now-deprecated separated arguments, this gets set
	 * to true for backwards-compatibility purposes.
	 *
	 * This should be false whenever possible to instead use core's built-in
	 * term meta (introduced in WordPress 4.4).
	 *
	 * @var boolean
	 */
	public $use_fm_meta = false;

	/**
	 * Base field
	 *
	 * @var Fieldmanager_Field
	 */
	public $fm = '';

	/**
	 * The current taxonomy. Used when saving data.
	 * @var string
	 */
	private $current_taxonomy;


	/**
	 * Instantiate this context. You can either pass an array of all args
	 * (preferred), or pass them individually (deprecated).
	 *
	 * @param array|string $args {
	 *     Array of arguments.
	 *
	 *     If a string (deprecated), this will be used as the $title.
	 *
	 *     @type string $title The context/meta box title.
	 *     @type string|array $taxonomies The taxonomy/taxonomies to which to
	 *                                    add this field.
	 *     @type bool $show_on_add Optional. Should this field show on the "Add
	 *                             Term" screen? Defaults to yes (true).
	 *     @type bool $show_on_edit Optional. Should this field show on the
	 *                              "Edit Term" screen? Defaults to yes (true).
	 *     @type int $parent Optional. Should this field only show if its parent
	 *                       matches this term ID?
	 *     @type bool $use_fm_meta Optional. Should this context store its data
	 *                             using FM term meta (true, deprecated) or
	 *                             WordPress core term meta (false). Defaults to
	 *                             false.
	 *     @type Fieldmanager_Field $field Optional. The field to which to
	 *                                     attach this context.
	 * }
	 * @param string|array $taxonomies Optional. Deprecated. Required if $args
	 *                                 is a string.
	 * @param boolean $show_on_add Optional. Deprecated.
	 * @param boolean $show_on_edit Optional. Deprecated.
	 * @param string $parent Optional. Deprecated.
	 * @param Fieldmanager_Field $fm Optional. Deprecated.
	 */
	public function __construct( $args, $taxonomies = array(), $show_on_add = true, $show_on_edit = true, $parent = '', $fm = null ) {
		if ( is_array( $args ) ) {
			$args = wp_parse_args( $args, array(
				'show_on_add'  => true,
				'show_on_edit' => true,
				'parent'       => '',
				'use_fm_meta'  => false,
				'field'        => null,
			) );
			if ( ! isset( $args['title'], $args['taxonomies'] ) ) {
				throw new FM_Developer_Exception( esc_html__( '"title" and "taxonomies" are required values for Fieldmanager_Context_Term', 'fieldmanager' ) );
			}

			$this->title        = $args['title'];
			$this->taxonomies   = (array) $args['taxonomies'];
			$this->show_on_add  = $args['show_on_add'];
			$this->show_on_edit = $args['show_on_edit'];
			$this->parent       = $args['parent'];
			$this->use_fm_meta  = $args['use_fm_meta'];
			$this->fm           = $args['field'];
		} elseif ( empty( $taxonomies ) ) {
			throw new FM_Developer_Exception( esc_html__( '"title" and "taxonomies" are required values for Fieldmanager_Context_Term', 'fieldmanager' ) );
		} else {
			// Instantiating Fieldmanager_Context_Term using individual
			// arguments is deprecated as of Fieldmanager-1.0.0-beta.3; you
			// should pass an array of arguments instead.

			// Set the class variables
			$this->title        = $args;
			$this->taxonomies   = (array) $taxonomies;
			$this->show_on_add  = $show_on_add;
			$this->show_on_edit = $show_on_edit;
			$this->parent       = $parent;
			$this->use_fm_meta  = true;
			$this->fm           = $fm;
		}

		// Iterate through the taxonomies and add the fields to the requested forms
		// Also add handlers for saving the fields and which forms to validate (if enabled)
		foreach ( $this->taxonomies as $taxonomy ) {
			if ( $this->show_on_add ) {
				add_action( $taxonomy . '_add_form_fields', array( $this, 'add_term_fields' ), 10, 1 );
				add_action( 'created_term', array( $this, 'save_term_fields'), 10, 3 );
			}

			if ( $this->show_on_edit ) {
				add_action( $taxonomy . '_edit_form_fields', array( $this, 'edit_term_fields' ), 10, 2 );
				add_action( 'edited_term', array( $this, 'save_term_fields'), 10, 3 );
			}

			if ( $this->use_fm_meta ) {
				// Handle removing FM term meta when a term is deleted
				add_action( 'delete_term', array( $this, 'delete_term_fields'), 10, 4 );
			}
		}
	}

	/**
	 * Creates the HTML template for wrapping fields on the add term form and prints the field
	 * @access public
	 * @param string $taxonomy
	 * @return void
	 */
	public function add_term_fields( $taxonomy ) {
		// If the parent is set, do nothing because we don't know what the parent term is yet
		if ( ! empty( $this->parent ) ) {
			return;
		}

		// Create the HTML template for output
		$html_template = '<div class="form-field">%s%s</div>';

		// Check if any validation is required
		$fm_validation = Fieldmanager_Util_Validation( 'addtag', 'term' );
		$fm_validation->add_field( $this->fm );

		// Display the field
		echo $this->term_fields( $html_template, $taxonomy );
	}

	/**
	 * Creates the HTML template for wrapping fields on the edit term form and prints the field
	 * @access public
	 * @param WP_Term $tag
	 * @param string $taxonomy
	 * @return void
	 */
	public function edit_term_fields( $term, $taxonomy ) {
		// Check if this term's parent matches the specified term if it is set
		if ( ! empty( $this->parent ) && $this->parent != $term->parent ) {
			return;
		}

		// Create the HTML template for output
		$html_template = '<tr class="form-field"><th scope="row" valign="top">%s</th><td>%s</td></tr>';

		// Check if any validation is required
		$fm_validation = Fieldmanager_Util_Validation( 'edittag', 'term' );
		$fm_validation->add_field( $this->fm );

		// Display the field
		echo $this->term_fields( $html_template, $taxonomy, $term );
	}

	/**
	 * Generates HTML for the term field
	 * @access public
	 * @param string $html_template THe HTML used to wrap the field
	 * @param string $taxonomy
	 * @param WP_Term $term
	 * @return string The element markup
	 */
	public function term_fields( $html_template, $taxonomy, $term = null ) {
		// Make sure the user hasn't specified a field name we can't use
		if ( in_array( $this->fm->name, $this->reserved_fields ) ) {
			$this->fm->_invalid_definition( sprintf( __( 'The field name "%s" is reserved for WordPress on the term form.', 'fieldmanager' ), $this->fm->name ) );
		}

		// Set the data type and ID
		$this->fm->data_type = 'term';
		$this->fm->data_id = is_object( $term ) ? $term->term_id : null;
		$this->current_taxonomy = $taxonomy;

		// Create the display label if one is set
		if ( ! empty( $this->title ) ) {
			$label = sprintf(
				'<label for="%s">%s</label>',
				esc_attr( $this->fm->name ),
				esc_html( $this->title )
			);
		} else {
			$label = '';
		}

		$field = $this->render_field( array( 'echo' => false ) );

		// Create the markup and return it
		return sprintf(
			$html_template,
			$label,
			$field
		);
	}

	/**
	 * Saves custom term fields
	 * @access public
	 * @param int $term_id
	 * @param int $tt_id
	 * @param string $taxonomy
	 * @return void
	 */
	public function save_term_fields( $term_id, $tt_id, $taxonomy ) {
		// Make sure this field is attached to the taxonomy being saved and this is the appropriate action
		if ( ! in_array( $taxonomy, $this->taxonomies ) ) {
			return;
		}

		// Make sure that our nonce field arrived intact
		if ( ! $this->is_valid_nonce() ) {
			return;
		}

		// Make sure the current user can save this post
		$tax_obj = get_taxonomy( $taxonomy );
		if ( ! current_user_can( $tax_obj->cap->manage_terms ) ) {
			$this->fm->_unauthorized_access( __( 'User cannot edit this term', 'fieldmanager' ) );
			return;
		}

		// Save the data
		$this->save_to_term_meta( $term_id, $taxonomy );
	}

	/**
	 * Helper to save an array of data to term meta
	 * @param int $term_id
	 * @param array $data
	 * @return void
	 */
	public function save_to_term_meta( $term_id, $taxonomy, $data = null ) {
		// Set the data ID and type
		$this->fm->data_id = $term_id;
		$this->fm->data_type = 'term';
		$this->current_taxonomy = $taxonomy;

		$this->save( $data );
	}

	/**
	 * Saves custom fields for the sport taxonomy.
	 *
	 * @deprecated Fieldmanager-1.0.0-beta.3 This is not necessary if you're
	 *                                       using core's term meta.
	 *
	 * @access public
	 *
	 * @param int $term_id
	 * @param int $tt_id
	 * @param string $taxonomy
	 * @param WP_term $deleted_term
	 */
	public function delete_term_fields( $term_id, $tt_id, $taxonomy, $deleted_term ) {
		// Get an instance of the term meta class
		$term_meta = Fieldmanager_Util_Term_Meta();

		// Delete any instance of this field for the term that was deleted
		$term_meta->delete_term_meta( $term_id, $taxonomy, $this->fm->name );
	}

	/**
	 * Callback to get term meta for the given term ID and current taxonomy.
	 *
	 * @see get_term_meta().
	 * @see Fieldmanager_Util_Term_Meta::get_term_meta() (Deprecated).
	 */
	protected function get_data( $term_id, $meta_key, $single = false ) {
		if ( $this->use_fm_meta ) {
			return fm_get_term_meta( $term_id, $this->current_taxonomy, $meta_key, $single );
		} else {
			return get_term_meta( $term_id, $meta_key, $single );
		}
	}

	/**
	 * Callback to add term meta for the given term ID and current taxonomy.
	 *
	 * @see add_term_meta().
	 * @see Fieldmanager_Util_Term_Meta::add_term_meta() (Deprecated).
	 */
	protected function add_data( $term_id, $meta_key, $meta_value, $unique = false ) {
		if ( $this->use_fm_meta ) {
			return fm_add_term_meta( $term_id, $this->current_taxonomy, $meta_key, $meta_value, $unique );
		} else {
			return add_term_meta( $term_id, $meta_key, $meta_value, $unique );
		}
	}

	/**
	 * Callback to update term meta for the given term ID and current taxonomy.
	 *
	 * @see update_term_meta().
	 * @see Fieldmanager_Util_Term_Meta::update_term_meta() (Deprecated).
	 */
	protected function update_data( $term_id, $meta_key, $meta_value, $meta_prev_value = '' ) {
		if ( $this->use_fm_meta ) {
			return fm_update_term_meta( $term_id, $this->current_taxonomy, $meta_key, $meta_value, $meta_prev_value );
		} else {
			return update_term_meta( $term_id, $meta_key, $meta_value, $meta_prev_value );
		}
	}

	/**
	 * Callback to delete term meta for the given term ID and current taxonomy.
	 *
	 * @see delete_term_meta().
	 * @see Fieldmanager_Util_Term_Meta::delete_term_meta() (Deprecated).
	 */
	protected function delete_data( $term_id, $meta_key, $meta_value = '' ) {
		if ( $this->use_fm_meta ) {
			return fm_delete_term_meta( $term_id, $this->current_taxonomy, $meta_key, $meta_value );
		} else {
			return delete_term_meta( $term_id, $meta_key, $meta_value );
		}
	}

}
