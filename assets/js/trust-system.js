/**
 * HDH: Trust System - Comment Rating
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Handle rating button clicks
        $('.btn-rate').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const commentId = $button.data('comment-id');
            const rating = $button.data('rating');
            const $container = $button.closest('.comment-rating-buttons');
            
            // Disable buttons during request
            $container.find('.btn-rate').prop('disabled', true);
            
            $.ajax({
                url: hdhTrust.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hdh_rate_comment',
                    comment_id: commentId,
                    rating: rating,
                    nonce: hdhTrust.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Hide buttons and show success message
                        $container.html('<span class="rating-success">✅ Değerlendirme kaydedildi!</span>');
                        
                        // Optionally update trust score display on page
                        // This would require refreshing the trust score element
                    } else {
                        alert(response.data.message || 'Bir hata oluştu.');
                        $container.find('.btn-rate').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                    $container.find('.btn-rate').prop('disabled', false);
                }
            });
        });
        
    });
    
})(jQuery);

