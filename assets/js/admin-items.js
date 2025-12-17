/**
 * HDH: Admin Items Management JavaScript
 */

(function($) {
    'use strict';
    
    var itemIndex = $('#items-list .hdh-item-item').length;
    var mediaUploader;
    
    $(document).ready(function() {
        // Toggle item expand/collapse
        $(document).on('click', '.hdh-toggle-item', function(e) {
            e.stopPropagation();
            var $item = $(this).closest('.hdh-item-item');
            $item.toggleClass('collapsed');
        });
        
        // Update item title preview on input change
        $(document).on('input', 'input[name*="[label]"]', function() {
            var $item = $(this).closest('.hdh-item-item');
            var label = $(this).val() || 'Yeni Ürün';
            $item.find('.hdh-item-title-preview').text(label);
        });
        
        // Update item preview image when image URL changes
        $(document).on('change', '.hdh-image-url', function() {
            var $item = $(this).closest('.hdh-item-item');
            var imageUrl = $(this).val();
            var $previewContainer = $item.find('.hdh-image-preview-container');
            var $previewImage = $previewContainer.find('.hdh-image-preview');
            var $previewPlaceholder = $previewContainer.find('.hdh-image-placeholder');
            var $headerPreview = $item.find('.hdh-item-preview-image, .hdh-item-preview-placeholder');
            
            if (imageUrl) {
                // Update content preview
                if ($previewImage.length) {
                    $previewImage.attr('src', imageUrl);
                } else {
                    $previewPlaceholder.replaceWith('<img src="' + imageUrl + '" alt="Preview" class="hdh-image-preview" />');
                }
                
                // Update header preview
                if ($headerPreview.hasClass('hdh-item-preview-image')) {
                    $headerPreview.attr('src', imageUrl);
                } else {
                    $headerPreview.replaceWith('<img src="' + imageUrl + '" alt="Preview" class="hdh-item-preview-image" />');
                }
                
                // Show remove button
                var $removeBtn = $item.find('.hdh-remove-image-btn');
                if (!$removeBtn.length) {
                    var $uploadButtons = $item.find('.hdh-image-upload-buttons');
                    $uploadButtons.append('<button type="button" class="button button-link hdh-remove-image-btn" data-item-id="' + $item.data('item-id') + '"><span class="dashicons dashicons-trash"></span> Görseli Kaldır</button>');
                }
            } else {
                // Show placeholder
                if ($previewImage.length) {
                    $previewImage.replaceWith('<div class="hdh-image-placeholder"><span class="dashicons dashicons-format-image"></span><p>Görsel seçilmedi</p></div>');
                }
                
                // Update header preview
                if ($headerPreview.hasClass('hdh-item-preview-image')) {
                    $headerPreview.replaceWith('<div class="hdh-item-preview-placeholder"><span class="dashicons dashicons-format-image"></span></div>');
                }
                
                // Hide remove button
                $item.find('.hdh-remove-image-btn').remove();
            }
        });
        
        // Image upload button
        $(document).on('click', '.hdh-upload-image', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $button = $(this);
            var itemId = $button.data('item-id');
            var $item = $button.closest('.hdh-item-item');
            var $imageUrlInput = $item.find('.hdh-image-url');
            
            // If the uploader object has already been created, reopen it
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Create the media uploader
            mediaUploader = wp.media({
                title: hdhItemsAdmin.uploadTitle,
                button: {
                    text: hdhItemsAdmin.uploadButton
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // When an image is selected, run a callback
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $imageUrlInput.val(attachment.url).trigger('change');
            });
            
            // Open the uploader
            mediaUploader.open();
        });
        
        // Remove image button
        $(document).on('click', '.hdh-remove-image-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!confirm('Görseli kaldırmak istediğinize emin misiniz?')) {
                return;
            }
            
            var $item = $(this).closest('.hdh-item-item');
            var $imageUrlInput = $item.find('.hdh-image-url');
            $imageUrlInput.val('').trigger('change');
        });
        
        // Add new item
        $('#add-new-item').on('click', function() {
            var newItemId = 'new_item_' + Date.now() + '_' + itemIndex;
            var itemNumber = $('#items-list .hdh-item-item').length + 1;
            
            var template = $('#hdh-item-template').html();
            template = template.replace(/\{\{itemId\}\}/g, newItemId);
            template = template.replace(/\{\{itemNumber\}\}/g, itemNumber);
            
            $('#items-list').append(template);
            
            // Update item numbers
            updateItemNumbers();
            
            // Scroll to new item
            $('html, body').animate({
                scrollTop: $('#items-list .hdh-item-item:last').offset().top - 100
            }, 300);
            
            itemIndex++;
        });
        
        // Remove item
        $(document).on('click', '.hdh-remove-item', function(e) {
            e.stopPropagation();
            
            if (!confirm('Bu ürünü silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
                return;
            }
            
            var $item = $(this).closest('.hdh-item-item');
            
            $item.fadeOut(300, function() {
                $(this).remove();
                updateItemNumbers();
            });
        });
        
        // Move item up
        $(document).on('click', '.hdh-move-item-up', function(e) {
            e.stopPropagation();
            var $item = $(this).closest('.hdh-item-item');
            var $prev = $item.prev('.hdh-item-item');
            
            if ($prev.length) {
                $item.insertBefore($prev);
                updateItemNumbers();
            }
        });
        
        // Move item down
        $(document).on('click', '.hdh-move-item-down', function(e) {
            e.stopPropagation();
            var $item = $(this).closest('.hdh-item-item');
            var $next = $item.next('.hdh-item-item');
            
            if ($next.length) {
                $item.insertAfter($next);
                updateItemNumbers();
            }
        });
        
        // Update item numbers
        function updateItemNumbers() {
            $('#items-list .hdh-item-item').each(function(index) {
                $(this).find('.hdh-item-number').text('#' + (index + 1));
            });
        }
        
        // Form validation
        $('#hdh-items-form').on('submit', function(e) {
            var hasErrors = false;
            var errorMessages = [];
            
            // Check for duplicate item IDs
            var itemIds = {};
            $('input[name*="[id]"]').each(function() {
                var itemId = $(this).val().trim().toLowerCase();
                
                // Validate slug format (only lowercase letters, numbers, underscores)
                if (itemId && !/^[a-z0-9_]+$/.test(itemId)) {
                    hasErrors = true;
                    errorMessages.push('Ürün ID sadece küçük harf, rakam ve alt çizgi içerebilir: ' + itemId);
                    $(this).css('border-color', '#d63638');
                } else {
                    $(this).css('border-color', '');
                }
                
                if (itemId) {
                    if (itemIds[itemId]) {
                        hasErrors = true;
                        errorMessages.push('Aynı ürün ID\'si kullanılamaz: ' + itemId);
                        $(this).css('border-color', '#d63638');
                    } else {
                        itemIds[itemId] = true;
                    }
                }
            });
            
            // Check for empty required fields
            $('input[required]').each(function() {
                if (!$(this).val().trim()) {
                    hasErrors = true;
                    $(this).css('border-color', '#d63638');
                } else {
                    $(this).css('border-color', '');
                }
            });
            
            // Check for items without images
            $('.hdh-image-url').each(function() {
                if (!$(this).val().trim()) {
                    var $item = $(this).closest('.hdh-item-item');
                    var itemLabel = $item.find('input[name*="[label]"]').val() || 'Ürün';
                    hasErrors = true;
                    errorMessages.push(itemLabel + ' için görsel seçilmedi');
                    $(this).closest('.hdh-field-group').find('strong').css('color', '#d63638');
                } else {
                    $(this).closest('.hdh-field-group').find('strong').css('color', '');
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                alert('Lütfen formdaki hataları düzeltin:\n\n' + errorMessages.join('\n'));
                return false;
            }
            
            // Show loading state
            var $submitBtn = $(this).find('input[type="submit"]');
            $submitBtn.prop('disabled', true).val('Kaydediliyor...');
        });
        
        // Auto-format item ID (slug) on input
        $(document).on('input', 'input[name*="[id]"]', function() {
            var $input = $(this);
            var value = $input.val();
            // Convert to lowercase and replace spaces/special chars with underscores
            var formatted = value.toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
            if (formatted !== value) {
                $input.val(formatted);
            }
        });
        
        // Initialize: Update item numbers
        updateItemNumbers();
        
        // Initialize: Expand all items by default
        $('.hdh-item-item').removeClass('collapsed');
    });
})(jQuery);

