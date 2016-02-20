<?php if ( !empty( $value ) && is_numeric( $value ) ): ?>
	<div class="file-thumb-wrapper">
		<?php echo wp_get_attachment_image( $value, 'thumbnail' ); ?>
		<input type="hidden" class="fm-incrementable" name="<?php echo $this->get_form_saved_name(); ?>" value="<?php echo intval( $value ); ?>" />
	</div>
<?php endif; ?>
<input
	class="fm-element <?php echo $this->field_class; ?>"
	type="file"
	name="<?php echo $this->get_form_name(); ?>"
	id="<?php echo $this->get_element_id(); ?>"
	<?php echo $this->get_element_attributes(); ?>
/>