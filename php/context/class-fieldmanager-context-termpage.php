<?php
/**
 * @package Fieldmanager_Context
 */

/**
 * Use fieldmanager to create meta boxes on a subpage from a term
 */
class Fieldmanager_Context_Termpage extends Fieldmanager_Context_Term {

	/**
	 * @var string|null $submit_button_label
	 */
	protected $submit_button_label = null;

	/**
	 * Add a context to a fieldmanager
	 * @param string|string[] $taxonomies
	 * @param boolean $show_on_add Whether or not to show the fields on the add term form
	 * @param boolean $show_on_edit Whether or not to show the fields on the edit term form
	 * @param Fieldmanager_Field $fm
	 */
	public function __construct( $title, $taxonomies, $parent = '', $fm = null ) {
		// Populate the list of taxonomies for which to add this meta box with the given settings
		if ( !is_array( $taxonomies ) ) $taxonomies = array( $taxonomies );

		// Set the class variables
		$this->title = $title;
		$this->taxonomies = $taxonomies;
		$this->show_on_add = false;
		$this->show_on_edit = false;
		$this->parent = $parent;
		$this->fm = $fm;

		// Iterate through the taxonomies and add the fields to the requested forms
		// Also add handlers for saving the fields and which forms to validate (if enabled)
		foreach ( $taxonomies as $taxonomy ) {
			add_action( $taxonomy . '_edit_form_fields', array( $this, 'show_term_subpage_link' ), 10, 2 );

			// Also handle removing data when a term is deleted
			add_action( 'delete_term', array( $this, 'delete_term_fields'), 10, 4 );
		}

		add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );

		add_action( 'admin_init', array( $this, 'handle_submenu_save' ) );
	}

	/**
	 * Register a submenu page with WordPress
	 * @return void
	 */
	public function register_submenu_page() {
		foreach ( $this->taxonomies as $taxonomy ) {
			$tax_obj = get_taxonomy( $taxonomy );
			add_submenu_page( 'options.php', $this->title, $this->title, $tax_obj->cap->manage_terms, $this->get_menu_page_slug( $taxonomy ), array( $this, 'render_submenu_page' ) );
		}
	}

	/**
	 * Get submenu page slug for a taxonomy for this context.
	 *
	 * @param string $taxonomy
	 * @return string
	 */
	public function get_menu_page_slug( $taxonomy ) {
		return "{$taxonomy}_submenu_{$this->fm->name}";
	}

	/**
	 * Get the taxonomy from a submenu page slug.
	 *
	 * @param string $slug
	 * @return string
	 */
	public function get_taxonomy_from_slug( $slug ) {
		return preg_replace( "/^([\w-_]+)_submenu_{$this->fm->name}$/", '$1', $slug );
	}

	/**
	 * Get the URL to edit the fields for a term.
	 *
	 * @param int $term_id
	 * @param string $taxonomy
	 * @return string
	 */
	public function url( $term_id, $taxonomy ) {
		return admin_url( "options.php?page={$this->get_menu_page_slug( $taxonomy )}&term_id={$term_id}" );
	}

	/**
	 * Helper to attach element_markup() to add_meta_box(). Prints markup for options page.
	 * @return void.
	 */
	public function render_submenu_page() {
		if ( empty( $_GET['term_id'] ) || empty( $_GET['page'] ) ) {
			return;
		}

		$term_id = absint( $_GET['term_id'] );
		$taxonomy = $this->get_taxonomy_from_slug( sanitize_title( $_GET['page'] ) );
		$term = get_term( $term_id, $taxonomy );
		?>
		<div class="wrap">
			<?php if ( ! empty( $_GET['msg'] ) && 'success' == $_GET['msg'] ) : ?>
				<div class="updated success"><p><?php esc_html_e( 'Options updated', 'fieldmanager' ); ?></p></div>
			<?php endif ?>

			<h2><?php echo esc_html( $this->title ) ?>: <?php echo esc_html( $term->name ); ?></h2>

			<p>
				<a href="<?php echo esc_url( get_edit_term_link( $term_id, $taxonomy ) ); ?>">
					<?php echo esc_html( sprintf( __( 'Back to Edit %s', 'wwd' ), $term->name ) ); ?>
				</a>
			</p>

			<form method="POST" id="<?php echo esc_attr( $this->uniqid ) ?>">
				<input type="hidden" name="fm-term-subpage" value="<?php echo sanitize_title( $this->get_menu_page_slug( $taxonomy ) ) ?>" />
				<input type="hidden" name="term_id" value="<?php echo absint( $term_id ); ?>" />

				<table class="form-table">
					<tbody>
						<?php $this->edit_term_fields( $term, $taxonomy ); ?>
					</tbody>
				</table>

				<?php submit_button( $this->submit_button_label, 'submit', 'fm-submit' ) ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save a submenu page
	 */
	public function handle_submenu_save() {
		if ( empty( $_POST['term_id'] ) || empty( $_POST['fm-term-subpage'] ) ) {
			return;
		}

		// Make sure that our nonce field arrived intact
		if ( ! $this->is_valid_nonce() ) {
			return;
		}

		$term_id = absint( $_POST['term_id'] );
		$taxonomy = $this->get_taxonomy_from_slug( sanitize_title( $_POST['fm-term-subpage'] ) );

		$this->save_term_fields( $term_id, null, $taxonomy );

		wp_redirect( esc_url_raw( add_query_arg( array( 'msg' => 'success' ), $this->url( $term_id, $taxonomy ) ) ) );
		exit;
	}

	/**
	 * Add the link to the submenu page in the edit term form.
	 *
	 * @param WP_Term $term
	 * @param string $taxonomy
	 */
	public function show_term_subpage_link( $term, $taxonomy ) {
		$label = empty( $this->title ) ? __( 'Fields', 'wwd' ) : $this->title;

		$edit_url = $this->url( $term->term_id, $taxonomy );

		?>
		<tr class="form-field">
			<th scope="row" valign="top"></th>
			<td>
				<a href="<?php echo esc_url( $edit_url ); ?>">
					<?php echo esc_html( sprintf( __( 'Edit %s', 'wwd' ), $label ) ); ?>
				</a>
			</td>
		</tr>
		<?php
	}
}
