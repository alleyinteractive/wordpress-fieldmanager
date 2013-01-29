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

	/**
	 * Form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = '' ) {
		return sprintf(
			'<textarea class="fm-element fm-richtext" name="%1$s" id="%2$s" %3$s />%4$s</textarea>',
			$this->get_form_name(),
			$this->get_element_id(),
			$this->get_element_attributes(),
			$value
		);
	}
	
}