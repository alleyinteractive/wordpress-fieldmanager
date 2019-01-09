<?php
/**
 * Class file for Fieldmanager_Util_Assets
 *
 * @package Fieldmanager
 */

/**
 * Asset management.
 */
class Fieldmanager_Util_Assets {

	/**
	 * Singleton instance
	 *
	 * @var Fieldmanager_Util_Assets
	 */
	private static $instance;

	/**
	 * Array of scripts to enqueue during *_enqueue_scripts
	 *
	 * @var array
	 */
	protected $scripts = array();

	/**
	 * Array of scripts to enqueue during *_enqueue_styles
	 *
	 * @var array
	 */
	protected $styles = array();

	/**
	 * Ensure that the enqueue method only gets hooked once.
	 *
	 * @var bool
	 */
	public $hooked = false;

	/**
	 * Don't do anything, needs to be initialized via instance() method.
	 */
	private function __construct() {}

	/**
	 * Get the singleton instance.
	 *
	 * @return Fieldmanager_Util_Assets
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Fieldmanager_Util_Assets();
		}
		return self::$instance;
	}

	/**
	 * Enqueue all assets during the correct action.
	 */
	public function enqueue_assets() {
		/**
		 * Filters the script argument arrays for Fieldmanager_Util_Assets::enqueue_script().
		 *
		 * @param array $scripts Arrays of script arguments. @see Fieldmanager_Util_Assets::add_script().
		 */
		$enqueue_scripts = apply_filters( 'fm_enqueue_scripts', array_values( $this->scripts ) );
		foreach ( $enqueue_scripts as $args ) {
			$this->enqueue_script( $args );
		}
		$this->scripts = array();

		/**
		 * Filters the stylesheet argument arrays for Fieldmanager_Util_Assets::enqueue_style().
		 *
		 * @param array $styles Arrays of stylesheet arguments. @see Fieldmanager_Util_Assets::add_style().
		 */
		$enqueue_styles = apply_filters( 'fm_enqueue_styles', array_values( $this->styles ) );
		foreach ( $enqueue_styles as $args ) {
			$this->enqueue_style( $args );
		}
		$this->styles = array();
	}

	/**
	 * Enqueue or output a script.
	 *
	 * Checks if the *_enqueue_scripts action has already fired and if so, outputs
	 * the script immediately. If not, the handle gets added to an array to
	 * enqueue later.
	 *
	 * @param array $args Script arguments. @see Fieldmanager_Util_Assets::add_script().
	 */
	protected function pre_enqueue_script( $args ) {
		if ( did_action( 'admin_enqueue_scripts' ) || did_action( 'wp_enqueue_scripts' ) ) {
			$this->enqueue_script( $args );
		} else {
			$this->scripts[ $args['handle'] ] = $args;
			$this->hook_enqueue();
		}
	}

	/**
	 * Enqueue a script.
	 *
	 * @param array $args Script arguments. @see Fieldmanager_Util_Assets::add_script().
	 */
	protected function enqueue_script( $args ) {
		// Register the script and localize data if applicable.
		wp_enqueue_script( $args['handle'], $args['path'], $args['deps'], $args['ver'], $args['in_footer'] );
		if ( ! empty( $args['data_object'] ) && ! empty( $args['data'] ) ) {
			wp_localize_script( $args['handle'], $args['data_object'], $args['data'] );
		}
	}

	/**
	 * Enqueue or output a style.
	 *
	 * Checks if the *_enqueue_scripts action has already fired and if so, outputs
	 * the style immediately. If not, the handle gets added to an array to
	 * enqueue later.
	 *
	 * @param array $args Stylesheet arguments. @see Fieldmanager_Util_Assets::add_style().
	 */
	protected function pre_enqueue_style( $args ) {
		if ( did_action( 'admin_enqueue_scripts' ) || did_action( 'wp_enqueue_scripts' ) ) {
			$this->enqueue_style( $args );
		} else {
			$this->styles[ $args['handle'] ] = $args;
			$this->hook_enqueue();
		}
	}

	/**
	 * Enqueue a style.
	 *
	 * @param array $args Stylesheet arguments. @see Fieldmanager_Util_Assets::add_style().
	 */
	protected function enqueue_style( $args ) {
		// Register the style.
		wp_enqueue_style( $args['handle'], $args['path'], $args['deps'], $args['ver'], $args['media'] );
	}

	/**
	 * Hook into admin_enqueue_scripts and wp_enqueue_scripts if we haven't already.
	 */
	protected function hook_enqueue() {
		if ( ! $this->hooked ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			$this->hooked = true;
		}
	}

	/**
	 * Enqueue a script, optionally localizing data to it.
	 *
	 * @see wp_enqueue_script() for detail about $handle, $deps, $ver, and $in_footer.
	 * @see wp_localize_script() for detail about $data_object and $data.
	 * @see FM_GLOBAL_ASSET_VERSION for detail about the fallback value of $ver.
	 * @see fieldmanager_get_baseurl() for detail about the fallback value of $plugin_dir.
	 *
	 * @param array $args {
	 *     Script arguments.
	 *
	 *     @type string $handle Script handle.
	 *     @type string|bool $path Optional. The path to the file inside $plugin_dir.
	 *                             If absent, the script will only be enqueued
	 *                             and not registered. Default false.
	 *     @type array $deps Optional. Script dependencies. Default empty array.
	 *     @type string|bool $ver Optional. Script version. Default false.
	 *     @type bool $in_footer Optional. Whether to render the script in the
	 *                           footer. Default false.
	 *     @type string $data_object Optional. The $object_name in
	 *                               wp_localize_script(). Default none.
	 *     @type array $data Optional. The $l10n in wp_localize_script().
	 *                       Default empty array.
	 *     @type string $plugin_dir The base URL to the directory with the
	 *                              script. Default none.
	 * }
	 */
	public function add_script( $args ) {
		if ( ! is_admin() ) {
			return;
		}

		$args = wp_parse_args(
			$args,
			array(
				'path'        => false,
				'deps'        => array(),
				'ver'         => false,
				'in_footer'   => false,
				'data_object' => '',
				'data'        => array(),
				'plugin_dir'  => '',
			)
		);

		// Bail if we don't have a handle and a path.
		if ( ! isset( $args['handle'] ) ) {
			return;
		}

		if ( $args['path'] ) {
			// Set the default version.
			if ( ! $args['ver'] ) {
				$args['ver'] = FM_GLOBAL_ASSET_VERSION;
			}

			// Set the default directory.
			if ( '' == $args['plugin_dir'] ) {
				$args['plugin_dir'] = fieldmanager_get_baseurl(); // Allow overrides for child plugins.
			}
			$args['path'] = $args['plugin_dir'] . $args['path'];
		}

		// Enqueue or output the script.
		$this->pre_enqueue_script( $args );
	}

	/**
	 * Register and enqueue a style.
	 *
	 * @see wp_enqueue_script() for detail about $handle, $path, $deps, $ver, and $media.
	 * @see FM_GLOBAL_ASSET_VERSION for detail about the fallback value of $ver.
	 * @see fieldmanager_get_baseurl() for detail about base URL.
	 *
	 * @param array $args {
	 *     Stylesheet arguments.
	 *
	 *     @type string $handle Stylesheet name.
	 *     @type string $path Optional. Path to the file inside of the Fieldmanager
	 *                        base URL. If absent, the style will only be enqueued
	 *                        and not registered. Default false.
	 *     @type array $deps Optional. Stylesheet dependencies. Default empty array.
	 *     @type string|bool Optional. Stylesheet version. Default none.
	 *     @type string $media Optional. Media for this stylesheet. Default 'all'.
	 *     @type string $plugin_dir The base URL for the directory with the style.
	 *                              Default none.
	 * }
	 */
	public function add_style( $args ) {
		if ( ! is_admin() ) {
			return;
		}

		$args = wp_parse_args(
			$args,
			array(
				'path'       => false,
				'deps'       => array(),
				'ver'        => false,
				'media'      => 'all',
				'plugin_dir' => '',
			)
		);

		// Bail if we don't have a handle and a path.
		if ( ! isset( $args['handle'] ) ) {
			return;
		}

		if ( $args['path'] ) {
			// Set the default version.
			if ( ! $args['ver'] ) {
				$args['ver'] = FM_GLOBAL_ASSET_VERSION;
			}

			// Set the default directory.
			if ( '' == $args['plugin_dir'] ) {
				$args['plugin_dir'] = fieldmanager_get_baseurl(); // Allow overrides for child plugins.
			}
			$args['path'] = $args['plugin_dir'] . $args['path'];
		}

		// Enqueue or output the style.
		$this->pre_enqueue_style( $args );
	}
}
