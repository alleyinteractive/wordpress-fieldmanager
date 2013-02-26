<?php
/**
 * @package Fieldmanager
 */

/**
 * Textarea field
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
	public $apply_mce_filters = True;

	/**
	 * @var array
	 * Options to pass to TinyMCE init
	 */
	public $init_options = array();

	/**
	 * @var string
	 * Static variable so we only load tinymce once
	 */
	public static $has_registered_tinymce = False;

	/**
	 * Construct default attributes; 50x10 textarea
	 * @param array $options
	 */
	public function __construct( $options = array() ) {
		if ( !self::$has_registered_tinymce ) {
			wp_enqueue_script( 'tiny_mce.js', includes_url( 'js/tinymce/tiny_mce.js' ) );
			wp_enqueue_script( 'wp-langs-en.js', includes_url( 'js/tinymce/langs/wp-langs-en.js' ) );
			self::$has_registered_tinymce = True;
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
		$this->attributes = array(
			'cols' => '50',
			'rows' => '10'
		);
		$this->sanitize = function( $value ) {
			return wp_kses_post( $value );
		};
		fm_add_script( 'fm_richtext', 'js/richtext.js' );
		parent::__construct( $options );
	}

	public function get_mce_options() {
		$editor_id = $this->get_element_id();
		$buttons = array(
			apply_filters( 'mce_buttons', array( 'bold', 'italic', 'strikethrough', 'bullist', 'numlist', 'blockquote', 'justifyleft', 'justifycenter', 'justifyright', 'link', 'unlink', 'wp_more', 'spellchecker', 'fullscreen', 'wp_adv' ), $editor_id ),
			apply_filters( 'mce_buttons_2', array( 'formatselect', 'underline', 'justifyfull', 'forecolor', 'pastetext', 'pasteword', 'removeformat', 'charmap', 'outdent', 'indent', 'undo', 'redo', 'wp_help' ), $editor_id ),
			apply_filters( 'mce_buttons_3', array(), $editor_id ),
			apply_filters( 'mce_buttons_4', array(), $editor_id ),
		);
		$options = array(
			'mode' => "exact",
			'theme' => "advanced",
			'language' => 'en',
			'skin' => "wp_theme",
			'editor_css' => "/wp-includes/css/editor.css", 
			'theme_advanced_toolbar_align' => "left",
			'theme_advanced_statusbar_location' => "bottom",
			'theme_advanced_resizing' => true,
			'theme_advanced_resize_horizontal' => false,
			'dialog_type' => "modal",
			'content_css' => apply_filters( 'mce_css', "/wp-content/themes/vip/kff/inc/fieldmanager/css/fieldmanager-richtext-content.css" ),
			'theme_advanced_toolbar_location' => "top",
			'theme_advanced_buttons1' => implode( ',', $buttons[0] ),
			'theme_advanced_buttons2' => implode( ',', $buttons[1] ),
			'theme_advanced_buttons3' => implode( ',', $buttons[2] ),
			'theme_advanced_buttons4' => implode( ',', $buttons[3] ),
			'height' => "250",
			'width' => "100%",
		);
		$options['plugins'] = array( 'inlinepopups', 'spellchecker', 'tabfocus', 'paste', 'media', 'fullscreen', 'wordpress', 'wpeditimage', 'wpgallery', 'wplink', 'wpdialogs' );
		$options['plugins'] = array_unique( apply_filters('tiny_mce_plugins', $options['plugins'] ) );
		$options['plugins'] = implode( ',', $options['plugins'] );
		$options = array_merge( $options, $this->init_options );
		if ( $this->apply_mce_filters ) {
			$options['content_css'] = apply_filters( 'mce_css', $options['content_css'] );
			$options = apply_filters( 'tiny_mce_before_init', $options, $editor_id );
		}
		if ( isset( $options['style_formats'] ) && !is_array( $options['style_formats'] ) ) {
			$options['style_formats'] = json_decode( $options['style_formats'] );
		}
 
		// print_r($options); exit;
		unset( $options['elements'] );
		return $options;
	}

	/**
	 * Form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = '' ) {
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