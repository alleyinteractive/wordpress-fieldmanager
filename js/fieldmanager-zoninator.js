( function( $ ) {

	$( document ).ready( function() {

		$('.fm-zoninator-post-form').on('submit', function(e) {
			e.preventDefault();
			var $button = $(this).find('.button-primary');
			var $message = $(this).find('.fm-zone-post-fornm-message');
			var label = $button.val();
			$button.addClass('disabled').val(fm_zoninator_localization['updating']);
			$.post(ajaxurl, { action: 'fm_zoninator_post_form_process', data: $(this).serialize() }, function(data) {
				$button.removeClass('disabled').val(label);
				$message.html(fm_zoninator_localization['updated']);
			});
		});

	} );

} )( jQuery );