jQuery(document).ready(function($) {
    $('.fm-sortable-list').sortable({
        update: function(event, ui) {
            var $list = $(this);
            var order = [];
            $list.find('.fm-sortable-item').each(function() {
                order.push($(this).data('key'));
            });
            $list.next('input[type="hidden"]').val(order.join(','));
        }
    });
});
