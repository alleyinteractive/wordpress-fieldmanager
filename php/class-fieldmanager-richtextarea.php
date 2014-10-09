<?php

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
	 * Add the "code" plugin to tinymce.
	 * @var boolean
	 */
	public $add_code_plugin = false;

	/**
	 * First row of buttons for the tinymce toolbar
	 * @var array
	 */
	public $buttons_1 = array( 'bold', 'italic', 'strikethrough', 'bullist', 'numlist', 'blockquote', 'justifyleft', 'justifycenter', 'justifyright', 'add_media', 'link', 'unlink', 'wp_more', 'spellchecker', 'fullscreen', 'wp_adv' );

	/**
	 * Second row of buttons for the tinymce toolbar
	 * @var array
	 */
	public $buttons_2 = array( 'formatselect', 'underline', 'justifyfull', 'forecolor', 'pastetext', 'pasteword', 'removeformat', 'charmap', 'outdent', 'indent', 'undo', 'redo', 'wp_help', 'code' );

	/**
	 * Third row of buttons for the tinymce toolbar
	 * @var array
	 */
	public $buttons_3 = array();

	/**
	 * Fourth row of buttons for the tinymce toolbar
	 * @var array
	 */
	public $buttons_4 = array();

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
	 * Construct defaults for this field attributes.
	 *
	 * @param array $options
	 * @todo dectect locale and apply correct language file
	 */
	public function __construct( $label, $options = array() ) {
		$this->sanitize = function( $value ) {
			return wp_filter_post_kses( wpautop( $value ) ); // run through wpautop first to preserve breaks.
		};
		fm_add_script( 'fm_richtext', 'js/richtext.js', array( 'jquery' ), '1.0.3' );
		parent::__construct( $label, $options );
	}


	/**
	 * Render the form element, which is a textarea by default.
	 *
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = '' ) {
		ob_start();
		$proto = $this->has_proto();
		$settings = array(
			'textarea_name' => $this->get_form_name(),
			'editor_class' => 'fm-element fm-richtext',
			'tinymce' => array( 'wp_skip_init' => false ),
		);
		if ( $proto ) {
			add_filter( 'the_editor', array( $this, 'add_proto_id' ) );
			$settings['tinymce'] = array( 'wp_skip_init' => true );
		}
		wp_editor( $value, $this->get_element_id(), $settings );
		if ( $proto ) {
			remove_filter( 'the_editor', array( $this, 'add_proto_id' ) );
		}
		return ob_get_clean();
	}

	/**
	 * Note the ID for the proto so in repeated fields we can dig up the editor settings.
	 *
	 * @param string $editor HTML for the editor.
	 * @return string The editor HTML with a `data-proto-id` attribute added.
	 */
	public function add_proto_id( $editor ) {
		return str_replace( '<textarea', '<textarea data-proto-id="' . $this->get_element_id() . '"', $editor );
	}
}
