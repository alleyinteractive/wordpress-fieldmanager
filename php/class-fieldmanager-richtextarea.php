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
				jQuery( "#%2$s" ).on( "click", function() {
					tinyMCE.init( {
						mode: "exact",
						theme: "advanced",
						language: "en", 
						skin: "wp_theme",
						editor_css: "/wp-includes/css/editor.css", 
						theme_advanced_toolbar_align:"left",
						theme_advanced_statusbar_location:"bottom",
						theme_advanced_resizing:true,
						theme_advanced_resize_horizontal:false,
						dialog_type:"modal",
						content_css: "/wp-content/themes/vip/kff/inc/fieldmanager/css/fieldmanager-richtext-content.css",
						theme_advanced_toolbar_location: "top",
						theme_advanced_buttons1: "bold,italic,strikethrough,|,bullist,numlist,blockquote,|,justifyleft,justifycenter,justifyright,|,link,unlink,|,wp_fullscreen,|,formatselect,underline,justifyfull,forecolor,|,pastetext,pasteword,removeformat,|,charmap,|,outdent,indent,|,undo,redo,",
						theme_advanced_buttons2: "",
						plugins: "inlinepopups,spellchecker,tabfocus,paste,media,fullscreen,wordpress,wpeditimage,wpgallery,wplink,wpdialogs,-table,wpfullscreen",
						style_formats:[{"title":"Custom Paragraph Style","selector":"p","classes":"kff-style1"}],
						height: "250",
						width: "100%%",
					} );
					tinyMCE.ScriptLoader.markDone( "http://kff.dev/wp-includes/js/tinymce/langs/en.js" );
					tinyMCE.ScriptLoader.markDone( "http://kff.dev/wp-includes/js/tinymce/themes/advanced/langs/en.js" );
					tinyMCE.execCommand( "mceAddControl", false, "%2$s" );
				} );
			</script>',
			$this->get_form_name(),
			$this->get_element_id(),
			$this->get_element_attributes(),
			$value
		);
	}

}