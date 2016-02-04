<?php
/**
 * Class file for Fieldmanager_Customize_Control.
 */

if ( class_exists( 'WP_Customize_Control' ) ) :
	/**
	 * Render a Fieldmanager field as a Customizer control.
	 */
	class Fieldmanager_Customize_Control extends WP_Customize_Control {
		/**
		 * The Fieldmanager context controlling this field.
		 *
		 * @var Fieldmanager_Context
		 */
		protected $context;

		/**
		 * The control 'type', used in scripts to identify FM controls.
		 *
		 * @var string
		 */
		public $type = 'fieldmanager';

		/**
		 * Enqueue control-related scripts and styles.
		 */
		public function enqueue() {
			wp_register_script(
				'fm-serializejson',
				fieldmanager_get_baseurl() . 'js/jquery-serializejson/jquery.serializejson.min.js',
				array(),
				'2.0.0',
				true
			);

			fm_add_script(
				'fm-customize',
				'js/fieldmanager-customize.js',
				array( 'jquery', 'underscore', 'editor', 'quicktags', 'fieldmanager_script', 'customize-controls', 'fm-serializejson' ),
				'1.0.0',
				true
			);
		}

		/**
		 * Render the control's content.
		 *
		 * @see Fieldmanager_Field::element_markup().
		 */
		public function render_content() {
			$this->context->render_field();
		}
	}
endif;
