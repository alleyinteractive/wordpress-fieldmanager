<?php
/**
 * Default template for Fieldmanager_Button
 *
 * @package Fieldmanager
 */

// Add additional classnames, if they exist.
$fieldmanager_classnames = array_key_exists( 'class', $this->attributes ) ? $this->attributes['class'] : '';
?>

<button
	class="fm-button-element <?php echo esc_attr( $fieldmanager_classnames ); ?>"
	id="<?php echo esc_attr( $this->get_element_id() ); ?>"
	name="<?php echo esc_attr( $this->get_form_name() ); ?>"
	role="<?php echo esc_attr( $this->input_type ); ?>"
	type="<?php echo esc_attr( $this->input_type ); ?>"
	<?php echo $this->get_element_attributes(); // Escaped interally. xss ok. ?>
>
	<?php echo $this->escape( 'button_content' ); // Escaped interally. xss ok. ?>
</button>
