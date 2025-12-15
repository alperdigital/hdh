/**
 * HDH: Share Buttons JavaScript
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const shareButtons = document.querySelectorAll('.share-btn');
        
        shareButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const url = this.getAttribute('data-url') || window.location.href;
                const text = this.getAttribute('data-text') || '';
                const shareType = this.classList.contains('share-whatsapp') ? 'whatsapp' :
                                this.classList.contains('share-twitter') ? 'twitter' :
                                this.classList.contains('share-facebook') ? 'facebook' :
                                this.classList.contains('share-copy') ? 'copy' :
                                this.classList.contains('share-story') ? 'story' : '';
                
                switch (shareType) {
                    case 'whatsapp':
                        window.open('https://wa.me/?text=' + encodeURIComponent(text + ' ' + url), '_blank');
                        break;
                    
                    case 'twitter':
                        window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(text) + '&url=' + encodeURIComponent(url), '_blank');
                        break;
                    
                    case 'facebook':
                        window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank');
                        break;
                    
                    case 'copy':
                        if (navigator.clipboard) {
                            navigator.clipboard.writeText(url).then(function() {
                                showToast('Link kopyalandı!');
                            });
                        } else {
                            // Fallback
                            const textarea = document.createElement('textarea');
                            textarea.value = url;
                            document.body.appendChild(textarea);
                            textarea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textarea);
                            showToast('Link kopyalandı!');
                        }
                        break;
                    
                    case 'story':
                        const storyImage = this.getAttribute('data-story-image');
                        if (storyImage) {
                            // Download image
                            const link = document.createElement('a');
                            link.href = storyImage;
                            link.download = 'story-image.jpg';
                            link.click();
                        }
                        break;
                }
                
                // Track quest progress
                if (shareType !== 'copy' && shareType !== 'story' && window.hdhSingleTrade) {
                    // Track share_listing quest
                    const formData = new URLSearchParams();
                    formData.append('action', 'hdh_track_share');
                    formData.append('nonce', window.hdhSingleTrade.makeOfferNonce); // Reuse nonce
                    formData.append('listing_id', window.hdhSingleTrade.listingId || '');
                    
                    fetch(window.hdhSingleTrade.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData.toString()
                    }).catch(function() {});
                }
            });
        });
    });
    
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast toast-success';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(function() {
            toast.classList.add('show');
        }, 10);
        
        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() {
                document.body.removeChild(toast);
            }, 300);
        }, 2000);
    }
})();

