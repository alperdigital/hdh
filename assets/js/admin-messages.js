/**
 * HDH: Admin Messages Management JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Reset to defaults
        $('.hdh-reset-messages').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Bu kategorinin tüm mesajlarını varsayılan değerlere döndürmek istediğinize emin misiniz?')) {
                return;
            }
            
            var form = $('<form>', {
                'method': 'POST',
                'action': ''
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'hdh_reset_messages',
                'value': '1'
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'category',
                'value': $(this).data('category')
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_wpnonce',
                'value': $('#hdh-messages-form input[name="_wpnonce"]').val()
            }));
            
            $('body').append(form);
            form.submit();
        });
        
        // Form submission
        $('#hdh-messages-form').on('submit', function() {
            var $submitBtn = $(this).find('input[type="submit"]');
            $submitBtn.prop('disabled', true).val('Kaydediliyor...');
        });
    });
})(jQuery);

