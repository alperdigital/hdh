/**
 * HDH: Admin Settings Management JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Reset to defaults
        $('.hdh-reset-settings').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Bu kategorinin tüm ayarlarını varsayılan değerlere döndürmek istediğinize emin misiniz?')) {
                return;
            }
            
            var form = $('<form>', {
                'method': 'POST',
                'action': ''
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'hdh_reset_settings',
                'value': '1'
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'tab',
                'value': $(this).data('tab')
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_wpnonce',
                'value': $('#hdh-settings-form input[name="_wpnonce"]').val()
            }));
            
            $('body').append(form);
            form.submit();
        });
        
        // Form validation
        $('#hdh-settings-form').on('submit', function(e) {
            var hasErrors = false;
            
            $(this).find('input[type="number"]').each(function() {
                var $input = $(this);
                var value = parseInt($input.val());
                var min = parseInt($input.attr('min'));
                var max = parseInt($input.attr('max'));
                
                if (isNaN(value) || value < min || value > max) {
                    hasErrors = true;
                    $input.css('border-color', '#d63638');
                } else {
                    $input.css('border-color', '');
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                alert('Lütfen tüm değerleri belirtilen aralıkta girin.');
                return false;
            }
            
            var $submitBtn = $(this).find('input[type="submit"]');
            $submitBtn.prop('disabled', true).val('Kaydediliyor...');
        });
    });
})(jQuery);

