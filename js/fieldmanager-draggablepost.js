(function($) {
	$(document).ready(function() {
		resetEmptyMessages();
		$('.sortables').sortable({
			connectWith: '.sortables',
			cancel: 'a'
		});
		$('.post-bin').droppable({
			hoverClass: 'post-bin-active',
			drop: function(event, ui) {
				ui.draggable.prependTo($(this));
				setTimeout(function() { resetEmptyMessages(); populateHiddenElements(); }, 10);
			}
		});
		$('.post-repository').droppable({
			hoverClass: 'post-repository-active',
			drop: function(event, ui) {
				ui.draggable.prependTo($(this));
				setTimeout(function() { resetEmptyMessages(); populateHiddenElements(); }, 10);
			}
		});
	});
	function resetEmptyMessages() {
		$('.post-bin').each(function(i) {
			if ($(this).find('.draggable-post').length > 0) {
				$(this).find('.empty-message').hide();
			}
			else {
				$(this).find('.empty-message').show();
			}
		});
	}
	function populateHiddenElements() {
		$('.post-bin').each(function(i) {
			var post_ids = [];
			var input_name = $(this).attr('id').replace('-bin', '');
			$(this).find('.draggable-post').each(function(i) {
				post_ids.push($(this).attr('post_id'));
			});
			$('#' + input_name).val(post_ids.join(','));
		});
	}
})(jQuery);