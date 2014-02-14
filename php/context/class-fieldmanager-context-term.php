<?php
/**
 * @package Fieldmanager_Context
 */

/**
 * Use fieldmanager to create meta boxes on
 * @package Fieldmanager_Datasource
 */
class Fieldmanager_Context_Term extends Fieldmanager_Context {
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
	 * @var Fieldmanager_Group
	 * Base field
	 */
	public $fm = '';

	/**
	 * Add a context to a fieldmanager
	 * @param string|string[] $taxonomies
	 * @param boolean $show_on_add Whether or not to show the fields on the add term form
	 * @param boolean $show_on_edit Whether or not to show the fields on the edit term form
	 * @param Fieldmanager_Field $fm
	 */
	public function __construct( $title, $taxonomies, $show_on_add = true, $show_on_edit = true, $parent = '', $fm = null ) {
		// Populate the list of taxonomies for which to add this meta box with the given settings
		if ( !is_array( $taxonomies ) ) $taxonomies = array( $taxonomies );

		// Set the class variables
		$this->title = $title;
		$this->taxonomies = $taxonomies;
		$this->show_on_add = $show_on_add;
		$this->show_on_edit = $show_on_edit;
		$this->parent = $parent;
		$this->fm = $fm;

		// Iterate through the taxonomies and add the fields to the requested forms
		// Also add handlers for saving the fields and which forms to validate (if enabled)
		foreach ( $taxonomies as $taxonomy ) {
			if ( $this->show_on_add ) {
				add_action( $taxonomy . '_add_form_fields', array( $this, 'add_term_fields' ), 10, 1 );
				add_action( 'created_term', array( $this, 'save_term_fields'), 10, 3 );
			}

			if ( $this->show_on_edit ) {
				add_action( $taxonomy . '_edit_form_fields', array( $this, 'edit_term_fields' ), 10, 2 );
				add_action( 'edited_term', array( $this, 'save_term_fields'), 10, 3 );
			}

			// Also handle removing data when a term is deleted
			add_action( 'delete_term', array( $this, 'delete_term_fields'), 10, 4 );
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
		if ( ! empty( $this->parent ) )
			return;

		// Create the HTML template for output
		$html_template = '<div class="form-field">%s%s%s</div>';

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
		if ( ! empty( $this->parent ) && $this->parent != $term->parent )
			return;

		// Create the HTML template for output
		$html_template = '<tr class="form-field"><th scope="row" valign="top">%s</th><td>%s%s</td></tr>';

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
		if ( in_array( $this->fm->name, $this->reserved_fields ) )
			$this->fm->_invalid_definition( 'The field name ' . $this->fm->name . ' is reserved for WordPress on the term form.' );

		// Check if there are any current values to retrieve
		if ( isset( $term->term_id ) ) {
			$term_meta = Fieldmanager_Util_Term_Meta();
			$values = $term_meta->get_term_meta( $term->term_id, $taxonomy, $this->fm->name, true );
		}
		$values = empty( $values ) ? null : $values;

		// Set the data type and ID
		$this->fm->data_type = 'term';
		$this->fm->data_id = is_object( $term ) ? $term->term_id : null;

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

		// Create the markup and return it
		return sprintf(
			$html_template,
			$label,
			wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce', true, false ),
			$this->fm->element_markup( $values )
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
		if ( ! in_array( $taxonomy, $this->taxonomies ) )
			return;

		// Make sure the current user can save this post
		$tax_obj = get_taxonomy( $taxonomy );
		if( !current_user_can( $tax_obj->cap->manage_terms ) ) {
			$this->fm->_unauthorized_access( 'User cannot edit this term' );
			return;
		}

		// Make sure that our nonce field arrived intact
		if( ! wp_verify_nonce( $_POST['fieldmanager-' . $this->fm->name . '-nonce'], 'fieldmanager-save-' . $this->fm->name ) ) {
			$this->fm->_unauthorized_access( 'Nonce validation failed' );
		}

		// Save the data
		$value = isset( $_POST[ $this->fm->name ] ) ? $_POST[ $this->fm->name ] : "";
		$this->save_to_term_meta( $term_id, $taxonomy, $value );
	}

	/**
	 * Helper to save an array of data to term meta
	 * @param int $term_id
	 * @param array $data
	 * @return void
	 */
	public function save_to_term_meta( $term_id, $taxonomy, $data ) {
		// Set the data ID and type
		$this->fm->data_id = $term_id;
		$this->fm->data_type = 'term';

		// Get an instance of the term meta class
		$term_meta = Fieldmanager_Util_Term_Meta();

		// Get the current data and run presave
		$current = $term_meta->get_term_meta( $this->fm->data_id, $taxonomy, $this->fm->name, true );
		$data = $this->fm->presave_all( $data, $current );

		// Unless we are skipping the save, store the new value in term meta
		if ( ! $this->fm->skip_save )
			$term_meta->update_term_meta( $term_id, $taxonomy, $this->fm->name, $data );
	}

	/**
	 * Saves custom fields for the sport taxonomy
	 *
	 * @access public
	 * @param int $term_id
	 * @param int $tt_id
	 * @param string $taxonomy
	 * @param WP_term $deleted_term
	 * @return void
	 */
	public function delete_term_fields( $term_id, $tt_id, $taxonomy, $deleted_term ) {
		// Get an instance of the term meta class
		$term_meta = Fieldmanager_Util_Term_Meta();

		// Delete any instance of this field for the term that was deleted
		$term_meta->delete_term_meta( $term_id, $taxonomy, $this->fm->name );
	}
}
