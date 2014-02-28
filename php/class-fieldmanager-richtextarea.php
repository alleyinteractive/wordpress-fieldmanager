<?php
/**
 * @package Fieldmanager
 */

/**
 * Use WordPress's TinyMCE control in Fieldmanager.
 * With a hat tip to _WP_Editors, and a glare at its 'final' keyword.
 * @package Fieldmanager
 */
class Fieldmanager_RichTextArea extends Fieldmanager_Field {

	/**
	 * @var string
	 * Override field_class
	 */
	public $field_class = 'richtext';

	/**
	 * @var boolean
	 * If true (default), apply default WordPress TinyMCE filters
	 */
	public $apply_mce_filters = true;

	/**
	 * @var array
	 * Options to pass to TinyMCE init
	 */
	public $init_options = array();

	/**
	 * @var string
	 * Static variable so we only load tinymce once
	 */
	public static $has_registered_tinymce = false;

	/**
	 * @var string
	 * Static variable so we only load footer scripts once
	 */
	public static $has_added_footer_scripts = false;

	/**
	 * @var boolean
	 * Static variable so we only calculate the tinymce version once
	 */
	public static $tinymce_major_version;

	/**
	 * Construct default attributes; 50x10 textarea
	 * @param array $options
	 * @todo dectect locale and apply correct language file
	 */
	public function __construct( $label, $options = array() ) {
		if ( ! isset( self::$tinymce_major_version ) ) {
			global $tinymce_version;
			self::$tinymce_major_version = intval( substr( $tinymce_version, 0, 1 ) );
		}

		if ( !self::$has_registered_tinymce ) {
			if ( 4 > self::$tinymce_major_version ) {
				wp_enqueue_script( 'tiny_mce.js', includes_url( 'js/tinymce/tiny_mce.js' ) );
				wp_enqueue_script( 'wp-langs-en.js', includes_url( 'js/tinymce/langs/wp-langs-en.js' ) );
			} else {
				wp_enqueue_script( 'tiny_mce.js', includes_url( 'js/tinymce/tinymce.js' ) );
				wp_enqueue_style( 'editor-buttons' );
			}

			self::$has_registered_tinymce = True;
			// add tinyMCEPreInit early enough in the process that it's available before TinyMCE inits.
			// WP Core may overwrite tinyMCEPreInit, but that's fine with us.
			add_action( 'admin_print_scripts', function() {
				printf( '<script>
if ( "undefined" === typeof tinyMCEPreInit ) tinyMCEPreInit = { base: "%s", suffix: "" };
</script>',
					includes_url( 'js/tinymce' )
				);
			} );

			if ( 4 > self::$tinymce_major_version ) {
				// Mark the language files as loaded; prevents a baffling JS error.
				add_action( 'admin_head', function() {
					printf(
						'<script type="text/javascript">
	tinyMCE.ScriptLoader.markDone( "%1$sjs/tinymce/langs/en.js" );
	tinyMCE.ScriptLoader.markDone( "%1$sjs/tinymce/themes/advanced/langs/en.js" );
	</script>',
						includes_url()
					);
				} );
			}
		}
		$this->attributes = array(
			'cols' => '50',
			'rows' => '10'
		);
		$this->sanitize = function( $value ) {
			return wpautop( wp_filter_post_kses( $value ) ); // run through wpautop first to preserve breaks.
		};
		// Unlike WP Core, we init TinyMCE on demand, and preserve its natural ability to move
		// about the DOM—richtext.js takes care of initializing our options, which are stored
		// per-field, not globally.
		fm_add_script( 'fm_richtext', 'js/richtext.js', array( 'jquery' ), '1.0.2' );
		parent::__construct( $label, $options );
	}


	/**
	 * Enqueue JS for TinyMCE control.
	 * We push out all TinyMCE scripts, because we might have many controls on the page
	 * and would otherwise have to compare them. Our default editor ships with all these
	 * controls, anyways.
	 */
	public static function enqueue_scripts() {
		wp_enqueue_script( 'wplink' );
		wp_enqueue_script( 'wpeditor' );
		wp_enqueue_script( 'wpdialogs-popup' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( 'wp-fullscreen' );
		add_action( 'admin_print_scripts', function() {
			$post = get_post();
			$args = array();
			if ( $post->ID ) {
				$args['post'] = $post->ID;
			}
			wp_enqueue_media( $args ); // generally on post pages this will not have an impact.
		} );
	}

	/**
	 * Cribbed from _WP_Editors, render the necessary HTML at the bottom of the page to show
	 * the link and fullscreen edit popups
	 * @return void
	 */
	public function editor_js() {
		if ( ! class_exists( '_WP_Editors' ) ) {
			require( ABSPATH . WPINC . '/class-wp-editor.php' );

			_WP_Editors::wp_link_dialog();
			_WP_Editors::wp_fullscreen_html();
		}
	}

	/**
	 * Get default TinyMCE options; can be overriden per field type with $this->init_options
	 * @return array of options to pass to TinyMCE in JS.
	 */
	public function get_mce_options() {
		$editor_id = $this->get_element_id();
		$buttons = array(
			implode( ',', apply_filters( 'mce_buttons', array( 'bold', 'italic', 'strikethrough', 'bullist', 'numlist', 'blockquote', 'justifyleft', 'justifycenter', 'justifyright', 'add_media', 'link', 'unlink', 'wp_more', 'spellchecker', 'fullscreen', 'wp_adv' ), $editor_id ) ),
			implode( ',', apply_filters( 'mce_buttons_2', array( 'formatselect', 'underline', 'justifyfull', 'forecolor', 'pastetext', 'pasteword', 'removeformat', 'charmap', 'outdent', 'indent', 'undo', 'redo', 'wp_help', 'code' ), $editor_id ) ),
			implode( ',', apply_filters( 'mce_buttons_3', array(), $editor_id ) ),
			implode( ',', apply_filters( 'mce_buttons_4', array(), $editor_id ) ),
		);

		$plugins = array( 'tabfocus', 'paste', 'media', 'fullscreen', 'wordpress', 'wpeditimage', 'wpgallery', 'wplink', 'wpdialogs' );
		$options = array(
			'mode' => 'exact',
			'language' => 'en',
			'editor_css' => '/wp-includes/css/editor.css',
			'content_css' => apply_filters( 'mce_css', fieldmanager_get_baseurl() . 'css/fieldmanager-richtext-content.css' ),
			'height' => '250',
			'width' => '100%',
			'convert_urls' => false,
		);

		if ( 4 > self::$tinymce_major_version ) {
			$options = array_merge( $options, array(
				'theme' => 'advanced',
				'skin' => 'wp_theme',
				'theme_advanced_buttons1' => $buttons[0],
				'theme_advanced_buttons2' => $buttons[1],
				'theme_advanced_buttons3' => $buttons[2],
				'theme_advanced_buttons4' => $buttons[3],
				'theme_advanced_toolbar_align' => 'left',
				'theme_advanced_statusbar_location' => 'bottom',
				'theme_advanced_resizing' => true,
				'theme_advanced_resize_horizontal' => false,
				'theme_advanced_toolbar_location' => 'top',
				'dialog_type' => 'modal',
			) );
			$plugins[] = 'inlinepopups';
			$plugins[] = 'spellchecker';
		} else {
			$options = array_merge( $options, array(
				'theme' => 'modern',
				'menubar' => false,
				'toolbar1' => $buttons[0],
				'toolbar2' => $buttons[1],
				'toolbar3' => $buttons[2],
				'toolbar4' => $buttons[3],
			) );
			$plugins[] = 'link';
		}

		$options['plugins'] = implode( ',', array_unique( apply_filters('tiny_mce_plugins', $plugins ) ) );
		$options = array_merge( $options, $this->init_options );
		if ( $this->apply_mce_filters ) {
			$options['content_css'] = apply_filters( 'mce_css', $options['content_css'] );
			$options = apply_filters( 'tiny_mce_before_init', $options, $editor_id );
		}
		if ( isset( $options['style_formats'] ) && !is_array( $options['style_formats'] ) ) {
			$options['style_formats'] = json_decode( $options['style_formats'] );
		}

		unset( $options['elements'] );
		return $options;
	}

	/**
	 * Render the form element, which is a textarea by default.
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = '' ) {
		if ( !self::$has_added_footer_scripts ) {
			if ( is_admin() ) {
				add_action( 'admin_print_footer_scripts', array( __CLASS__, 'editor_js'), 50 );
				add_action( 'admin_footer', array( __CLASS__, 'enqueue_scripts'), 1 );
			} else {
				add_action( 'wp_print_footer_scripts', array( __CLASS__, 'editor_js'), 50 );
				add_action( 'wp_footer', array( __CLASS__, 'enqueue_scripts'), 1 );
			}
			self::$has_added_footer_scripts = True;
		}
		return sprintf(
			'<textarea class="fm-element fm-richtext" name="%1$s" id="%2$s" %3$s data-mce-options="%5$s" />%4$s</textarea>',
			$this->get_form_name(),
			$this->get_element_id(),
			$this->get_element_attributes(),
			$value,
			htmlspecialchars( json_encode( $this->get_mce_options() ), ENT_QUOTES )
		);
	}

}