<?php
/**
 * Class file for Fieldmanager_Context_Widget.
 *
 * @package Fieldmanager
 */

/**
 * Add Fieldmanager Fields to widget forms.
 */
class Fieldmanager_Context_Widget extends Fieldmanager_Context {

	/**
	 * Field object.
	 *
	 * @var Fieldmanager_Field
	 */
	public $field;

	/**
	 * Settings for this context instance.
	 *
	 * @see Fieldmanager_Context_Widget::__construct() for application of the defaults.
	 *
	 * @var array {
	 *     @type array $include Optional. The root ID of widgets the field
	 *                          should be limited to, if any. Default is to
	 *                          include the field on all widgets.
	 *                          {@see WP_Widget::id_base}.
	 *     @type array $exclude Optional. The root IDs of widgets the field
	 *                          should be excluded from, if any. Default is to
	 *                          not exclude the field from any widgets.
	 *                          {@see WP_Widget::id_base}.
	 * }
	 */
	public $args = array();

	/**
	 * Set up adding and saving the field in the widget form.
	 *
	 * @see Fieldmanager_Context_Widget::args for detail about the $args parameter.
	 *
	 * @param Fieldmanager_Field $field
	 * @param array $args Optional. Context settings.
	 */
	public function __construct( $field, $args = array() ) {
		if ( ! $field instanceof Fieldmanager_Field ) {
			return;
		}

		$this->field = $field;
		$this->args = wp_parse_args( $args, array(
			'include' => array(),
			'exclude' => array(),
		) );

		add_action( 'in_widget_form', array( $this, 'add_field' ), 10, 3 );
		add_filter( 'widget_update_callback', array( $this, 'save_field' ), 10, 4 );
	}

	/**
	 * Add the field markup to the widget form.
	 *
	 * @param WP_Widget $widget The widget instance, passed by reference
	 * @param null $return Not used in this context, passed by reference
	 * @param array $instance An array of the widget's settings
	 */
	public function add_field( $widget, $return, $instance ) {
		if ( ! $this->is_included( $widget ) || $this->is_excluded( $widget ) ) {
			return;
		}

		if ( ! isset( $instance[ $this->field->name ] ) ) {
			$instance[ $this->field->name ] = '';
		}

		$value = $instance[ $this->field->name ];

		// Switch to a clone so we can make the field name widget-appropriate.
		$field = clone $this->field;
		$field->name = $widget->get_field_name( $field->name );
		echo $field->element_markup( $value );
	}

	/**
	 * Save the field to the widget.
	 *
	 * @param array $instance The settings that will be saved
	 * @param array $submitted The submitted settings
	 * @param array $existing The existing settings
	 * @param WP_Widget $widget The current widget instance
	 * @return array The widget instance settings to save
	 */
	public function save_field( $instance, $submitted, $existing, $widget ) {
		if ( ! $this->is_included( $widget ) || $this->is_excluded( $widget ) ) {
			return $instance;
		}

		$value   = isset( $submitted[ $this->field->name ] ) ? $submitted[ $this->field->name ] : '';
		$current = isset( $existing[ $this->field->name ] ) ? $existing[ $this->field->name ] : null;
		$data    = $this->field->presave_all( $value, $current );
		$instance[ $this->field->name ] = $data;

		return $instance;
	}

	/**
	 * Whether a widget should display this field in its form.
	 *
	 * @param WP_Widget $widget Widget instance
	 * @return bool
	 */
	protected function is_included( $widget ) {
		return empty( $this->args['include'] ) || in_array( $widget->id_base, $this->args['include'] );
	}

	/**
	 * Whether a widget should not display this field in its form.
	 *
	 * @param WP_Widget $widget Widget instance
	 * @return bool
	 */
	protected function is_excluded( $widget ) {
		return ! empty( $this->args['exclude'] ) && in_array( $widget->id_base, $this->args['exclude'] );
	}
}

