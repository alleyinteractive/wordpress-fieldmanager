<?php
/**
 * @package Fieldmanager_Context
 */

/**
 * Use fieldmanager to create meta boxes on
 * @package Fieldmanager_Context
 */
class Fieldmanager_Context_Submenu extends Fieldmanager_Context {

	/**
	 * @var string
	 * Parent of this submenu page
	 */
	public $parent_slug;

	/**
	 * @var string
	 * Title of the page
	 */
	public $page_title;

	/**
	 * @var string
	 * Menu title
	 */
	public $menu_title;

	/**
	 * @var string
	 * Capability required
	 */
	public $capability;

	/**
	 * @var string
	 * Menu slug
	 */
	public $menu_slug;

	/**
	 * @var string|Null
	 * Only used for options pages
	 */
	public $submit_button_label = Null;

	/**
	 * @var string
	 * For submenu pages, set autoload to true or false
	 */
	public $wp_option_autoload = False;

	/**
	 * Create a submenu page out of a field
	 * @param string $parent_slug
	 * @param string $page_title
	 * @param string $menu_title
	 * @param string $capability
	 * @param string $menu_slug
	 * @param Fieldmanager_Field $fm
	 */
	public function __construct( $parent_slug, $page_title, $menu_title = Null, $capability = 'manage_options', $menu_slug = Null, $fm = Null, $already_registered = False ) {
		$this->fm = $fm;
		$this->menu_slug = $menu_slug ?: $this->fm->name;
		$this->menu_title = $menu_title ?: $page_title;
		$this->parent_slug = $parent_slug;
		$this->page_title = $page_title;
		$this->capability = $capability;
		$this->uniqid = $this->fm->get_element_id() . '_form';
		if ( !$already_registered ) add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );
		add_action( 'admin_init', array( $this, 'handle_submenu_save' ) );
	}

	/**
	 * Register a submenu page with WordPress
	 * @return void
	 */
	public function register_submenu_page() {
		add_submenu_page( $this->parent_slug, $this->page_title, $this->menu_title, $this->capability, $this->menu_slug, array( $this, 'render_submenu_page' ) );
	}

	/**
	 * Helper to attach element_markup() to add_meta_box(). Prints markup for options page.
	 * @return void.
	 */
	public function render_submenu_page() {
		$values = get_option( $this->fm->name, Null );
		echo '<div class="wrap">';
		screen_icon();
		printf( '<h2>%s</h2>', $this->page_title );
		echo '<form method="POST" id="' . esc_attr( $this->uniqid ) . '">';
		echo '<div class="fm-submenu-form-wrapper">';
		printf( '<input type="hidden" name="fm-options-action" value="%s" />', sanitize_title( $this->fm->name ) );
		wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce' );
		echo $this->fm->element_markup( $values );
		echo '</div>';
		printf( '<input type="submit" name="fm-submit" class="button-primary" value="%s" />', esc_attr( $this->submit_button_label ) ?: __( 'Save Options' ) );
		echo '</form>';
		echo '</div>';

		// Check if any validation is required
		$fm_validation = Fieldmanager_Util_Validation( $this->uniqid, 'submenu' );
		$fm_validation->add_field( $this->fm );
	}

	/**
	 * Save a submenu page
	 * @return void
	 */
	public function handle_submenu_save() {
		if ( ! empty( $_POST ) && ! empty( $_GET['page'] ) && $_GET['page'] == $this->menu_slug && current_user_can( $this->capability ) ) {
			// Make sure that our nonce field arrived intact
			if( !wp_verify_nonce( $_POST['fieldmanager-' . $this->fm->name . '-nonce'], 'fieldmanager-save-' . $this->fm->name ) ) {
				$this->fm->_unauthorized_access( 'Nonce validation failed' );
			}
			$this->fm->data_id = $this->fm->name;
			$this->fm->data_type = 'options';
			$current = get_option( $this->fm->name, null );
			$value = isset( $_POST[ $this->fm->name ] ) ? $_POST[ $this->fm->name ] : "";
			$data = $this->fm->presave_all( $value, $current );
			$data = apply_filters( 'fm_submenu_presave_data', $data, $this );
			if ( isset( $current ) ) {
				update_option( $this->fm->name, $data );
			} else {
				add_option( $this->fm->name, $data, '', $this->wp_option_autoload ? 'yes' : 'no' );
			}
		}
	}

}
