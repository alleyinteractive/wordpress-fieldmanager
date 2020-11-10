<?php
/**
 * Class file for Fieldmanager_Context_Submenu
 *
 * @package Fieldmanager
 */

/**
 * Use fieldmanager to create arbitrary pages in the WordPress admin and save
 * data primarily to options.
 */
class Fieldmanager_Context_Submenu extends Fieldmanager_Context_Storable {

	/**
	 * Parent of this submenu page.
	 *
	 * @var string
	 */
	public $parent_slug;

	/**
	 * Title of the page.
	 *
	 * @var string
	 */
	public $page_title;

	/**
	 * Menu title.
	 *
	 * @var string
	 */
	public $menu_title;

	/**
	 * Capability required.
	 *
	 * @var string
	 */
	public $capability;

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	public $menu_slug;

	/**
	 * Only used for options pages.
	 *
	 * @var string
	 */
	public $submit_button_label = null;

	/**
	 * The "success" message displayed after options are saved. Defaults to
	 * "Options updated".
	 *
	 * @var string
	 */
	public $updated_message = null;

	/**
	 * For submenu pages, set autoload to true or false.
	 *
	 * @var string
	 */
	public $wp_option_autoload = false;

	/**
	 * Create a submenu page out of a field.
	 *
	 * @param string             $parent_slug        Parent slug.
	 * @param string             $page_title         Page title.
	 * @param string             $menu_title         Menu title.
	 * @param string             $capability         Page capability.
	 * @param string             $menu_slug          Menu slug.
	 * @param Fieldmanager_Field $fm                 Base field.
	 * @param bool               $already_registered Page already registered.
	 */
	public function __construct( $parent_slug, $page_title, $menu_title = null, $capability = 'manage_options', $menu_slug = null, $fm = null, $already_registered = false ) {
		$this->fm              = $fm;
		$this->menu_slug       = $menu_slug ?: $this->fm->name;
		$this->menu_title      = $menu_title ?: $page_title;
		$this->parent_slug     = $parent_slug;
		$this->page_title      = $page_title;
		$this->capability      = $capability;
		$this->updated_message = __( 'Options updated', 'fieldmanager' );
		$this->uniqid          = $this->fm->get_element_id() . '_form';
		if ( ! $already_registered ) {
			add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );
		}
		add_action( 'admin_init', array( $this, 'handle_submenu_save' ) );
	}

	/**
	 * Register a submenu page with WordPress.
	 */
	public function register_submenu_page() {
		add_submenu_page( $this->parent_slug, $this->page_title, $this->menu_title, $this->capability, $this->menu_slug, array( $this, 'render_submenu_page' ) );
	}

	/**
	 * Helper to attach element_markup() to add_meta_box(). Prints markup for options page.
	 */
	public function render_submenu_page() {
		$values = get_option( $this->fm->name, null );
		?>
		<div class="wrap">
			<?php
			if ( ! empty( $_GET['msg'] ) && 'success' == $_GET['msg'] ) : // WPCS: input var okay.
				?>
				<div class="updated success"><p><?php echo esc_html( $this->updated_message ); ?></p></div>
			<?php endif; ?>

			<h1><?php echo esc_html( $this->page_title ); ?></h1>

			<form method="POST" id="<?php echo esc_attr( $this->uniqid ); ?>">
				<div class="fm-submenu-form-wrapper">
					<input type="hidden" name="fm-options-action" value="<?php echo esc_attr( sanitize_title( $this->fm->name ) ); ?>" />
					<?php
					$this->render_field(
						array(
							'data' => $values,
						)
					);
					?>
				</div>
				<?php submit_button( $this->submit_button_label, 'primary', 'fm-submit' ); ?>
			</form>
		</div>
		<?php

		// Check if any validation is required.
		$fm_validation = fieldmanager_util_validation( $this->uniqid, 'submenu' );
		$fm_validation->add_field( $this->fm );
	}

	/**
	 * Save a submenu page
	 */
	public function handle_submenu_save() {
		if ( empty( $_GET['page'] ) || $_GET['page'] != $this->menu_slug ) { // WPCS: input var okay.
			return;
		}

		// Make sure that our nonce field arrived intact.
		if ( ! $this->is_valid_nonce() ) {
			return;
		}

		if ( ! current_user_can( $this->capability ) ) {
			$this->fm->_unauthorized_access( __( 'Current user cannot edit this page', 'fieldmanager' ) );
			return;
		}

		if ( $this->save_submenu_data() ) {
			wp_safe_redirect( esc_url_raw( add_query_arg( 'msg', 'success', $this->url() ) ) );
			exit;
		}
	}

	/**
	 * Save the submenu data.
	 *
	 * @param  array $data The new data.
	 * @return bool True.
	 */
	public function save_submenu_data( $data = null ) {
		$this->fm->data_id   = $this->fm->name;
		$this->fm->data_type = 'options';
		$current             = get_option( $this->fm->name, null );
		$data                = $this->prepare_data( $current, $data );
		$data                = apply_filters( 'fm_submenu_presave_data', $data, $this );
		if ( $this->fm->skip_save ) {
			return true;
		}

		if ( isset( $current ) ) {
			update_option( $this->fm->name, $data, $this->wp_option_autoload );
		} else {
			add_option( $this->fm->name, $data, '', $this->wp_option_autoload );
		}

		return true;
	}

	/**
	 * Get the URL for this context's admin page. Mainly pulled from
	 * menu_page_url().
	 *
	 * @return string
	 */
	public function url() {
		if ( $this->parent_slug && ! isset( $GLOBALS['_parent_pages'][ $this->parent_slug ] ) ) {
			return admin_url( add_query_arg( 'page', $this->menu_slug, $this->parent_slug ) );
		} else {
			return admin_url( 'admin.php?page=' . $this->menu_slug );
		}
	}

	/**
	 * Get option.
	 *
	 * @see get_option().
	 *
	 * @param  int    $data_id     Deprecated.
	 * @param  string $option_name The option name.
	 * @param  string $single      Deprecated.
	 * @return bool The current data or false.
	 */
	protected function get_data( $data_id, $option_name, $single = false ) {
		return get_option( $option_name, null );
	}

	/**
	 * Add option.
	 *
	 * @see add_option().
	 *
	 * @param  int    $data_id      Deprecated.
	 * @param  string $option_name  The option name.
	 * @param  string $option_value The option value.
	 * @param  bool   $unique       Deprecated.
	 * @return bool Option added successfully.
	 */
	protected function add_data( $data_id, $option_name, $option_value, $unique = false ) {
		return add_option( $option_name, $option_value, '', $this->wp_option_autoload );
	}

	/**
	 * Update option.
	 *
	 * @see update_option().
	 *
	 * @param  int    $data_id           Deprecated.
	 * @param  string $option_name       The option name.
	 * @param  string $option_value      The option value.
	 * @param  string $option_prev_value Deprecated.
	 * @return bool Option updated successfully.
	 */
	protected function update_data( $data_id, $option_name, $option_value, $option_prev_value = '' ) {
		$option_value = $this->sanitize_scalar_value( $option_value );
		return update_option( $option_name, $option_value, $this->wp_option_autoload );
	}

	/**
	 * Delete option.
	 *
	 * @see delete_option().
	 *
	 * @param  int    $data_id      Deprecated.
	 * @param  string $option_name  The option name.
	 * @param  string $option_value The option value.
	 * @return bool Option deleted successfully.
	 */
	protected function delete_data( $data_id, $option_name, $option_value = '' ) {
		return delete_option( $option_name, $option_value );
	}
}
