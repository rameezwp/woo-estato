jQuery(document).ready(function($) {
    $('.wcp-progress').hide();

    $(".add-field").click(function(event) {
        event.preventDefault();
        var clone_this = $(this).closest('tr').clone(true);
        clone_this.removeClass('first-element');
        $(clone_this).appendTo('.table-woo-packages').hide().fadeIn('slow');
    });

    $(".delete-field").click(function(event) {
        event.preventDefault();
        if ($(this).closest('tr').hasClass('first-element')) {
            swal('Sorry!', 'You can not delete first package.', 'warning');
        } else {
            $(this).closest('tr').fadeOut(500, function() { $(this).remove(); });
        }
    });

    $('#rem-woo-form').submit(function(event) {
	    event.preventDefault();
        $('.wcp-progress').show();
        var packages = [];
        $('.table-woo-packages').find('tr').each(function(index, el) {
            if (!$(this).hasClass('rem-table-header')) {
                var pkg = {
                    count: $(this).find('.count').val(),
                    woo_product_id: $(this).find('.woo_product').val(),
                }
                packages.push(pkg);
            }
        });
        var data = {
            action: 'wcp_rem_save_woo_estato',
            subscription_type: $('#subscription_type').val(),
            packages: packages
        }
        
        $.post(ajaxurl, data, function(resp) {
            $('.wcp-progress').hide();
            swal(resp.title, resp.message, resp.status);
        }, 'json');

    });

});