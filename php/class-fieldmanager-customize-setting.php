<?php
/**
 * Class file for Fieldmanager_Customize_Setting.
 *
 * @package Fieldmanager_Customize
 */

if ( class_exists( 'WP_Customize_Setting' ) ) :
	/**
	 * Represent a Fieldmanager field as a Customizer setting.
	 */
	class Fieldmanager_Customize_Setting extends WP_Customize_Setting {
		/**
		 * The Fieldmanager context controlling this setting.
		 *
		 * @var Fieldmanager_Context_Customizer
		 */
		protected $context;

		/**
		 * Constructor.
		 *
		 * @throws FM_Developer_Exception When no context is included.
		 *
		 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
		 * @param string               $id      Setting ID.
		 * @param array                $args    Setting arguments, including $context.
		 */
		public function __construct( $manager, $id, $args = array() ) {
			if ( isset( $args['context'] ) && ( $args['context'] instanceof Fieldmanager_Context ) ) {
				$this->context = $args['context'];

				// Set the default without checking isset() (as in WP_Customize_Setting) to support null values.
				$this->default = $this->context->fm->default_value;

				// Validate and sanitize with the context.
				$this->validate_callback = array( $this->context, 'validate_callback' );
				$this->sanitize_callback = array( $this->context, 'sanitize_callback' );

				// Use the Fieldmanager submenu default.
				$this->type = 'option';
			} elseif ( FM_DEBUG ) {
				throw new FM_Developer_Exception( __( 'Fieldmanager_Customize_Setting requires a Fieldmanager_Context_Customizer', 'fieldmanager' ) );
			}

			parent::__construct( $manager, $id, $args );
		}

		/**
		 * Filter non-multidimensional theme mods and options.
		 *
		 * Settings created with the Customizer context are non-multidimensional
		 * by default. If you create your own multidimensional settings, you
		 * might need to extend _multidimensional_preview_filter() accordingly.
		 *
		 * @param mixed $original Old value.
		 * @return mixed New or old value.
		 */
		public function _preview_filter( $original ) {
			/*
			 * Don't continue to the parent _preview_filter() while sanitizing
			 * or validating. _preview_filter() eventually calls
			 * sanitize_callback() and validate_callback(), which calls the
			 * hooks to those methods in Fieldmanager_Context_Customizer, which
			 * calls WP_Customize_Setting::value(), which ends up back here.
			 */
			if ( doing_filter( "customize_sanitize_{$this->id}" ) || doing_filter( "customize_validate_{$this->id}" ) ) {
				return $original;
			}

			return parent::_preview_filter( $original );
		}
	}
endif;
