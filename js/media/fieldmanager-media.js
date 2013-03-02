( function( $ ) {

$( document ).ready(function() {
	$('a.remove-link').click(function(e) {
		e.preventDefault();
		$(this).parents('.fm-image').find('.fm-media-id').val(0);
		$(this).parents('.fm-image').find('.media-wrapper').html('');
	});
});

$( document ).on( 'click', '.fm-media-button', function() {
	tb_show( '', 'media-upload.php?TB_iframe=true' );
	var old_send_to_editor = window.send_to_editor;
	var input = this;
	window.send_to_editor = function( html ) {
		var classes = $('img', html).attr('class').split(' ');
		var attachment_id = 0;
		for (i = 0; i < classes.length; i++) {
			if (classes[i].indexOf('wp-image') >= 0) {
				attachment_id = classes[i].split('-')[2];
			}
		}
		$(input).parent().find('.fm-media-id').val(attachment_id);
		$(input).parent().find('.media-wrapper').html('Uploaded file:<br /> ' + html + '<br /><a class="remove-link" href="#">remove image</a>').find('img').removeClass('alignright');
		$(input).parent().find('.media-wrapper').find('.remove-link').click(function(e) {
			e.preventDefault();
			$(input).parent().find('fm-media-id').val(0);
			$(input).parent().find('.media-wrapper').html('');
		});
		window.send_to_editor = old_send_to_editor;
		tb_remove();
	}
} );

} )( jQuery );