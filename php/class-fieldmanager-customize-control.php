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
		 * Constructor.
		 *
		 * @param WP_Customize_Manager $manager
		 * @param string $id Control ID.
		 * @param array $args Control arguments, including $context.
		 */
		public function __construct( $manager, $id, $args = array() ) {
			parent::__construct( $manager, $id, $args );

			if ( ! ( $this->context instanceof Fieldmanager_Context_Customizer ) && FM_DEBUG ) {
				throw new FM_Developer_Exception(
					__( 'Fieldmanager_Customize_Control requires a Fieldmanager_Context_Customizer', 'fieldmanager' )
				);
			}
		}

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
		 * @see Fieldmanager_Context::render_field().
		 * @see WP_Customize_Control::render_content().
		 */
		protected function render_content() {
			?>

			<?php if ( ! empty( $this->label ) ) : ?>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $this->description ) ) : ?>
				<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
			<?php endif; ?>

			<?php
			$this->context->render_field( array( 'data' => $this->value() ) );
		}
	}
endif;
