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
		 * The field object to use. Supply via `$args['fm']`.
		 *
		 * @var Fieldmanager_Field
		 */
		protected $fm;

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
				'fm_customizer',
				'js/fieldmanager-customize.js',
				array( 'jquery', 'underscore', 'fieldmanager_script', 'customize-controls', 'fm-serializejson' ),
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
			echo $this->fm->element_markup( $this->value() );
		}
	}
endif;
