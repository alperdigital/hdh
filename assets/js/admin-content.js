/**
 * HDH: Admin Content Management JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Reset to defaults
        $('.hdh-reset-content').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Bu sayfanın tüm içeriğini varsayılan değerlere döndürmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
                return;
            }
            
            var $button = $(this);
            var page = $button.data('page');
            
            // Get defaults via AJAX or reload page with reset parameter
            var form = $('<form>', {
                'method': 'POST',
                'action': ''
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'hdh_reset_content',
                'value': '1'
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'page',
                'value': page
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_wpnonce',
                'value': $('#hdh-content-form input[name="_wpnonce"]').val()
            }));
            
            $('body').append(form);
            form.submit();
        });
        
        // Auto-save indicator
        var $form = $('#hdh-content-form');
        var originalValues = {};
        
        // Store original values
        $form.find('input, textarea').each(function() {
            var $field = $(this);
            originalValues[$field.attr('name')] = $field.val();
        });
        
        // Check for changes
        $form.on('input change', 'input, textarea', function() {
            var $field = $(this);
            var fieldName = $field.attr('name');
            var hasChanges = false;
            
            $form.find('input, textarea').each(function() {
                var $f = $(this);
                var name = $f.attr('name');
                if (originalValues[name] !== $f.val()) {
                    hasChanges = true;
                    return false;
                }
            });
            
            // Show/hide save indicator
            var $submitBtn = $form.find('input[type="submit"]');
            if (hasChanges) {
                $submitBtn.css('background', '#f0b849');
            } else {
                $submitBtn.css('background', '');
            }
        });
        
        // Form submission
        $form.on('submit', function() {
            var $submitBtn = $(this).find('input[type="submit"]');
            $submitBtn.prop('disabled', true).val('Kaydediliyor...');
        });
        
        // Character counter for textareas (optional)
        $form.find('textarea').each(function() {
            var $textarea = $(this);
            var $counter = $('<span>', {
                'class': 'char-counter',
                'style': 'font-size: 11px; color: #646970; margin-top: 4px;'
            });
            
            $textarea.after($counter);
            
            function updateCounter() {
                var length = $textarea.val().length;
                $counter.text(length + ' karakter');
            }
            
            $textarea.on('input', updateCounter);
            updateCounter();
        });
    });
})(jQuery);

