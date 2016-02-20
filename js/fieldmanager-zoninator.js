( function( $ ) {

	$( document ).ready( function() {

		$('.fm-zoninator-post-form').on('submit', function(e) {
			fm_zoninator_ajax(e, this);
		});

		$('.zone-posts-wrapper').on('loading.end', function() {
			$('.fm-zoninator-post-form').on('submit', function(e) {
				fm_zoninator_ajax(e, this);
			});
		});

	} );

	function fm_zoninator_ajax(e, form) {
		e.preventDefault();
		var $button = $(form).find('.button-primary');
		var $message = $(form).find('.fm-zone-post-form-message');
		var label = $button.val();
		$button.addClass('disabled').prop('disabled', true).val(fm_zoninator_localization['updating']);
		$.post(ajaxurl, { action: 'fm_zoninator_post_form_process', data: $(form).serialize() }, function(data) {
			$button.removeClass('disabled').prop('disabled', false).val(label);
			$message.html(fm_zoninator_localization['updated']);
		});
	}

} )( jQuery );