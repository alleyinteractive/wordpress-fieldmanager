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
		}
		$this->attributes = array(
			'cols' => '50',
			'rows' => '10'
		);
		parent::__construct( $options );
	}

	/**
	 * Form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = '' ) {
		return sprintf(
			'<textarea class="fm-element" name="%1$s" id="%2$s" %3$s />%4$s</textarea>' . 
			'<script type="text/javascript">
				jQuery(document).ready(function() {
					tinyMCE.init( { mode: "exact", theme: "advanced", language: "en", skin: "wp_theme" } );
					tinyMCE.ScriptLoader.markDone( "http://kff.netaustin.dev/wp-includes/js/tinymce/langs/en.js" );
					tinyMCE.ScriptLoader.markDone( "http://kff.netaustin.dev/wp-includes/js/tinymce/themes/advanced/langs/en.js" );
					tinyMCE.execCommand( "mceAddControl", false, "%2$s" );
					// new tinyMCE.Editor( "%2$s", {mode: "exact", theme: "advanced", language: "en", skin: "wp_theme"} ).render();
				} );
			</script>',
			$this->get_form_name(),
			$this->get_element_id(),
			$this->get_element_attributes(),
			$value
		);
	}

}