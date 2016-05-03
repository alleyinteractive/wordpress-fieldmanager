<?php

/**
 * WordPress visual editor (TinyMCE) which submits HTML.
 *
 * @package Fieldmanager_Field
 */
class Fieldmanager_RichTextArea extends Fieldmanager_Field {

	/**
	 * @var string
	 * Override field_class
	 */
	public $field_class = 'richtext';

	/**
	 * @deprecated
	 */
	public $apply_mce_filters = true;

	/**
	 * @deprecated
	 * @see Fieldmanager_RichTextArea::$editor_settings
	 */
	public $init_options = array();

	/**
	 * Arguments passed to wp_editor()'s `$settings` parameter.
	 * @see http://codex.wordpress.org/Function_Reference/wp_editor#Arguments
	 * @var array
	 */
	public $editor_settings = array();

	/**
	 * @deprecated
	 */
	public $add_code_plugin = false;

	/**
	 * First row of buttons for the tinymce toolbar
	 * @var array
	 */
	public $buttons_1;

	/**
	 * Second row of buttons for the tinymce toolbar
	 * @var array
	 */
	public $buttons_2;

	/**
	 * Third row of buttons for the tinymce toolbar
	 * @var array
	 */
	public $buttons_3;

	/**
	 * Fourth row of buttons for the tinymce toolbar
	 * @var array
	 */
	public $buttons_4;

	/**
	 * External stylesheet(s) to include in the editor. Multiple files can be included
	 * delimited by commas.
	 * @var string
	 */
	public $stylesheet;

	/**
	 * Indicates if we should be altering the tinymce config.
	 * @access protected
	 * @var boolean
	 */
	protected $edit_config = false;

	/**
	 * Construct defaults for this field.
	 *
	 * @param string $label title of form field
	 * @param array $options with keys matching vars of the field in use.
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->sanitize = array( $this, 'sanitize' );
		fm_add_script( 'fm_richtext', 'js/richtext.js', array( 'jquery', 'fieldmanager_script' ), '1.0.8' );

		parent::__construct( $label, $options );
	}

	/**
	 * Default sanitization function for RichTextAreas.
	 *
	 * @param  string $value Raw content for this field.
	 * @return string sanitized content.
	 */
	public function sanitize( $value ) {
		return wp_filter_post_kses( wpautop( $value ) ); // run through wpautop first to preserve breaks.
	}

	/**
	 * Render the form element, which is a textarea by default.
	 *
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = '' ) {
		$proto = $this->has_proto();
		$wrapper_classes = array();

		$this->prep_editor_config();

		$settings = $this->array_merge_deep( $this->editor_settings, array(
			'textarea_name'  => $this->get_form_name(),
			'editor_class'   => 'fm-element fm-richtext',
			'tinymce'        => array( 'wp_skip_init' => true ),
		) );

		if ( $proto ) {
			add_filter( 'the_editor', array( $this, 'add_proto_id' ) );
		}

		if ( ! isset( $settings['default_editor'] ) ) {
			$settings['default_editor'] = 'tinymce';
		} elseif ( 'cookie' == $settings['default_editor'] ) {
			if ( $proto ) {
				$settings['default_editor'] = 'tinymce';
			} else {
				$cookie_value = '';
				if ( $user = wp_get_current_user() ) { // look for cookie
					$setting_key = str_replace( '-', '_', $this->get_element_id() );
					$setting_key = preg_replace( '/[^a-z0-9_]/i', '', $setting_key );
					$cookie_value = get_user_setting( 'editor_' . $setting_key, 'tinymce' );
				}

				$settings['default_editor'] = in_array( $cookie_value, array( 'tinymce', 'html' ) ) ? $cookie_value : 'tinymce';
			}

			$wrapper_classes[] = 'fm-richtext-remember-editor';
		}

		$this->add_editor_filters();

		ob_start();
		wp_editor( $value, $this->get_element_id(), $settings );
		$content = ob_get_clean();

		$this->remove_editor_filters();

		if ( $proto ) {
			remove_filter( 'the_editor', array( $this, 'add_proto_id' ) );
		}

		// Add classes to the wrapper if needed
		if ( ! empty( $wrapper_classes ) ) {
			$content = str_replace( 'wp-core-ui wp-editor-wrap', 'wp-core-ui wp-editor-wrap ' . implode( ' ', $wrapper_classes ), $content );
		}

		return $content;
	}

	/**
	 * Before generating the editor, manipualte the settings as needed.
	 */
	protected function prep_editor_config() {
		// Attempt to maintain some backwards compatibility for $init_options
		if ( ! empty( $this->init_options ) ) {
			if ( ! isset( $this->stylesheet ) && ! empty( $this->init_options['content_css'] ) ) {
				$this->stylesheet = $this->init_options['content_css'];
				unset( $this->init_options['content_css'] );
			}
			if ( empty( $this->editor_settings['tinymce'] ) ) {
				$this->editor_settings['tinymce'] = array();
			}
			$this->editor_settings['tinymce'] = wp_parse_args( $this->editor_settings['tinymce'], $this->init_options );
		}

		if ( isset( $this->stylesheet ) ) {
			$this->edit_config = true;
		}
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

	/**
	 * Filter the editor buttons.
	 *
	 * @param  array $buttons Buttons for the editor toolbar.
	 * @return array Filtered buttons.
	 */
	public function customize_buttons( $buttons ) {
		switch ( current_filter() ) {
			case 'teeny_mce_buttons':
			case 'mce_buttons'      : return $this->buttons_1;
			case 'mce_buttons_2'    : return $this->buttons_2;
			case 'mce_buttons_3'    : return $this->buttons_3;
			case 'mce_buttons_4'    : return $this->buttons_4;
		}
		return $buttons;
	}

	/**
	 * Make final tweaks to the editor config.
	 *
	 * @param  array $mceInit The raw settings passed to TinyMCE.
	 * @return array The raw settings passed to TinyMCE.
	 */
	public function editor_config( $mceInit ) {
		if ( isset( $this->stylesheet ) ) {
			$this->stylesheet = explode( ',', $this->stylesheet );
			$this->stylesheet = array_map( 'esc_url_raw', $this->stylesheet );
			$mceInit['content_css'] = implode( ',', $this->stylesheet );
		}
		return $mceInit;
	}

	/**
	 * Add necessary filters before generating the editor.
	 */
	protected function add_editor_filters() {
		if ( isset( $this->buttons_1 ) ) {
			add_filter( 'mce_buttons', array( $this, 'customize_buttons' ) );
			add_filter( 'teeny_mce_buttons', array( $this, 'customize_buttons' ) );
		}
		if ( isset( $this->buttons_2 ) ) {
			add_filter( 'mce_buttons_2', array( $this, 'customize_buttons' ) );
		}
		if ( isset( $this->buttons_3 ) ) {
			add_filter( 'mce_buttons_3', array( $this, 'customize_buttons' ) );
		}
		if ( isset( $this->buttons_4 ) ) {
			add_filter( 'mce_buttons_4', array( $this, 'customize_buttons' ) );
		}
		if ( $this->edit_config ) {
			if ( ! empty( $this->editor_settings['teeny'] ) ) {
				add_filter( 'teeny_mce_before_init', array( $this, 'editor_config' ) );
			} else {
				add_filter( 'tiny_mce_before_init', array( $this, 'editor_config' ) );
			}
		}

		// WordPress < 4.3 assumes there's only one editor on any given page, so
		// it adds a filter based on the visual vs. text state of that editor.
		// It will re-add filters for each editor, so there's no harm in
		// removing whatever it added.
		remove_filter( 'the_editor_content', 'wp_htmledit_pre' );
		remove_filter( 'the_editor_content', 'wp_richedit_pre' );
	}

	/**
	 * Remove the filters we added before generating the editor so they don't
	 * affect other editors.
	 */
	protected function remove_editor_filters() {
		remove_filter( 'mce_buttons', array( $this, 'customize_buttons' ) );
		remove_filter( 'mce_buttons_2', array( $this, 'customize_buttons' ) );
		remove_filter( 'mce_buttons_3', array( $this, 'customize_buttons' ) );
		remove_filter( 'mce_buttons_4', array( $this, 'customize_buttons' ) );
		remove_filter( 'teeny_mce_before_init', array( $this, 'editor_config' ) );
		remove_filter( 'tiny_mce_before_init', array( $this, 'editor_config' ) );
	}

	/**
	 * array_merge_recursive as it should have been done. Modeled on the
	 * similarly named function in drupal. This differs from
	 * array_merge_recursive in that if it sees two strings, it doesn't merge
	 * them into an array of strings. That's dumb.
	 *
	 * @return array
	 */
	protected function array_merge_deep() {
		$result = array();
		foreach ( func_get_args() as $array ) {
			foreach ( $array as $key => $value) {
				if ( is_integer( $key ) ) {
					// Renumber integer keys as array_merge_recursive() does. Note that PHP
					// automatically converts array keys that are integer strings (e.g., '1')
					// to integers.
					$result[] = $value;
				} elseif ( isset( $result[ $key ] ) && is_array( $result[ $key ] ) && is_array( $value ) ) {
					// Recurse when both values are arrays.
					$result[ $key ] = $this->array_merge_deep( $result[ $key ], $value );
				} else {
					// Otherwise, use the latter value, overriding any previous value.
					$result[ $key ] = $value;
				}
			}
		}
		return $result;
	}

}
