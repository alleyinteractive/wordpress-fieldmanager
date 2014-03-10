<input
	class="<?php echo $this->field_class; ?>"
	type="text"
	name="<?php echo $this->get_form_name(); ?>"
	id="<?php echo $this->get_element_id(); ?>"
	value="<?php echo !empty( $value ) ? htmlspecialchars( $value ) : ''; ?>"
	<?php echo $this->get_element_attributes(); ?>
/>