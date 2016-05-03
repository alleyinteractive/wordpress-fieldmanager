<?php

/**
 * Use fieldmanager to create arbitrary pages in the WordPress admin and save
 * data primarily to options.
 *
 * @package Fieldmanager_Context
 */
class Fieldmanager_Context_Submenu extends Fieldmanager_Context_Storable {

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
	 * The "success" message displayed after options are saved. Defaults to
	 * "Options updated".
	 *
	 * @var string|null
	 */
	public $updated_message = null;

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
		$this->updated_message = __( 'Options updated', 'fieldmanager' );
		$this->uniqid = $this->fm->get_element_id() . '_form';
		if ( ! $already_registered )  {
			add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );
		}
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
		$values = get_option( $this->fm->name, null );
		?>
		<div class="wrap">
			<?php if ( ! empty( $_GET['msg'] ) && 'success' == $_GET['msg'] ) : ?>
				<div class="updated success"><p><?php echo esc_html( $this->updated_message ); ?></p></div>
			<?php endif ?>

			<h1><?php echo esc_html( $this->page_title ) ?></h1>

			<form method="POST" id="<?php echo esc_attr( $this->uniqid ) ?>">
				<div class="fm-submenu-form-wrapper">
					<input type="hidden" name="fm-options-action" value="<?php echo sanitize_title( $this->fm->name ) ?>" />
					<?php $this->render_field( array( 'data' => $values ) ); ?>
				</div>
				<?php submit_button( $this->submit_button_label, 'primary', 'fm-submit' ) ?>
			</form>
		</div>
		<?php

		// Check if any validation is required
		$fm_validation = Fieldmanager_Util_Validation( $this->uniqid, 'submenu' );
		$fm_validation->add_field( $this->fm );
	}

	/**
	 * Save a submenu page
	 * @return void
	 */
	public function handle_submenu_save() {
		if ( empty( $_GET['page'] ) || $_GET['page'] != $this->menu_slug ) {
			return;
		}

		// Make sure that our nonce field arrived intact
		if ( ! $this->is_valid_nonce() ) {
			return;
		}

		if ( ! current_user_can( $this->capability ) ) {
			$this->fm->_unauthorized_access( __( 'Current user cannot edit this page', 'fieldmanager' ) );
			return;
		}

		if ( $this->save_submenu_data() ) {
			wp_redirect( esc_url_raw( add_query_arg( array( 'msg' => 'success' ), $this->url() ) ) );
			exit;
		}
	}

	public function save_submenu_data( $data = null ) {
		$this->fm->data_id = $this->fm->name;
		$this->fm->data_type = 'options';
		$current = get_option( $this->fm->name, null );
		$data = $this->prepare_data( $current, $data );
		$data = apply_filters( 'fm_submenu_presave_data', $data, $this );
		if ( $this->fm->skip_save ) {
			return true;
		}

		if ( isset( $current ) ) {
			update_option( $this->fm->name, $data );
		} else {
			add_option( $this->fm->name, $data, '', $this->wp_option_autoload ? 'yes' : 'no' );
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
	 */
	protected function get_data( $data_id, $option_name, $single = false ) {
		return get_option( $option_name, null );
	}

	/**
	 * Add option.
	 *
	 * @see add_option().
	 */
	protected function add_data( $data_id, $option_name, $option_value, $unique = false ) {
		return add_option( $option_name, $option_value, '', $this->wp_option_autoload ? 'yes' : 'no' );
	}

	/**
	 * Update option.
	 *
	 * @see update_option().
	 */
	protected function update_data( $data_id, $option_name, $option_value, $option_prev_value = '' ) {
		return update_option( $option_name, $option_value );
	}

	/**
	 * Delete option.
	 *
	 * @see delete_option().
	 */
	protected function delete_data( $data_id, $option_name, $option_value = '' ) {
		return delete_option( $option_name, $option_value );
	}
}
