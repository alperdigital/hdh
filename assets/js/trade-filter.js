/**
 * HDH: Trade Filter - Visual Filter System
 * Handles AJAX filtering by offer items
 */

(function($) {
    'use strict';
    
    let currentFilter = null;
    let currentPage = 1;
    let isLoading = false;
    let debounceTimer = null;
    let retryCount = 0;
    const MAX_RETRIES = 3;
    
    $(document).ready(function() {
        // Filter item button click
        $('.filter-item-btn').on('click', function() {
            if (isLoading) return;
            
            const $btn = $(this);
            const itemSlug = $btn.data('item-slug');
            
            // Toggle active state
            if (currentFilter === itemSlug) {
                // If clicking the same filter, clear it
                clearFilter();
            } else {
                // Apply new filter
                applyFilter(itemSlug);
            }
        });
        
        // Clear filter button
        $('#btn-clear-filter-visual').on('click', function() {
            clearFilter();
        });
        
        // Retry button click
        $('#btn-retry').on('click', function() {
            retryLoading();
        });
        
        // Reload button click
        $('#btn-reload').on('click', function() {
            location.reload();
        });
        
        // Pagination click handler (delegated for dynamically loaded content)
        $(document).on('click', '.trade-pagination a', function(e) {
            e.preventDefault();
            if (isLoading) return;
            
            const href = $(this).attr('href');
            const pageMatch = href.match(/page=(\d+)/);
            if (pageMatch) {
                const page = parseInt(pageMatch[1]);
                if (currentFilter) {
                    loadTrades(currentFilter, page);
                }
            }
        });
    });
    
    /**
     * Apply filter with debounce
     */
    function applyFilter(itemSlug) {
        currentFilter = itemSlug;
        currentPage = 1;
        retryCount = 0;
        
        // Update UI immediately (optimistic)
        $('.filter-item-btn').removeClass('active');
        $('.filter-item-btn[data-item-slug="' + itemSlug + '"]').addClass('active');
        $('#btn-clear-filter-visual').fadeIn(300);
        
        // Debounce loading (prevent rapid clicks)
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            loadTrades(itemSlug, 1);
        }, 300);
    }
    
    /**
     * Clear filter
     */
    function clearFilter() {
        currentFilter = null;
        currentPage = 1;
        retryCount = 0;
        
        // Update UI
        $('.filter-item-btn').removeClass('active');
        $('#btn-clear-filter-visual').fadeOut(300);
        
        // Debounce loading
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            loadTrades('', 1);
        }, 300);
    }
    
    /**
     * Load trades via AJAX
     */
    function loadTrades(itemSlug, page) {
        if (isLoading) return;
        isLoading = true;
        currentPage = page;
        
        const $container = $('#trade-feed-container');
        const $skeleton = $('#trade-skeleton');
        const $error = $('#trade-error');
        const $grid = $('#trade-cards-grid');
        const $pagination = $('#trade-pagination');
        
        // Scroll to top of feed
        $('html, body').animate({
            scrollTop: $container.offset().top - 100
        }, 300);
        
        // Show skeleton loading (hide content and error first)
        $grid.fadeOut(150, function() {
            $pagination.fadeOut(150);
            $error.fadeOut(150);
            $skeleton.fadeIn(200);
        });
        
        // AJAX request with timeout
        $.ajax({
            url: hdhFilter.ajaxUrl,
            type: 'POST',
            timeout: 15000, // 15 second timeout
            data: {
                action: 'hdh_filter_trades',
                item_slug: itemSlug,
                page: page,
                nonce: hdhFilter.nonce
            },
            success: function(response) {
                // Reset retry count on success
                retryCount = 0;
                
                // Hide skeleton first
                $skeleton.fadeOut(150, function() {
                    if (response.success) {
                        // Update content
                        $grid.html(response.data.html).fadeIn(300);
                        
                        // Update pagination
                        if (response.data.pagination) {
                            $pagination.html(response.data.pagination).fadeIn(300);
                        } else {
                            $pagination.html('').hide();
                        }
                        
                        // Log for debugging
                        console.log('HDH Filter: Loaded ' + response.data.found_posts + ' trades');
                    } else {
                        // Server returned error
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Sunucu hatası oluştu.';
                        showError('server', errorMsg);
                    }
                });
            },
            error: function(xhr, status, error) {
                // Hide skeleton first
                $skeleton.fadeOut(150, function() {
                    let errorType = 'unknown';
                    let errorMsg = 'Bir hata oluştu. Lütfen tekrar deneyin.';
                    
                    if (status === 'timeout') {
                        errorType = 'timeout';
                        errorMsg = 'İstek zaman aşımına uğradı. İnternet bağlantınızı kontrol edin ve tekrar deneyin.';
                    } else if (status === 'error') {
                        errorType = 'network';
                        errorMsg = 'Sunucuya ulaşılamadı. İnternet bağlantınızı kontrol edin.';
                    } else if (status === 'abort') {
                        errorType = 'abort';
                        errorMsg = 'İstek iptal edildi.';
                    } else if (!navigator.onLine) {
                        errorType = 'offline';
                        errorMsg = 'İnternet bağlantınız yok. Lütfen bağlantınızı kontrol edin.';
                    }
                    
                    // Auto-retry for network errors (up to MAX_RETRIES)
                    if ((errorType === 'timeout' || errorType === 'network') && retryCount < MAX_RETRIES) {
                        retryCount++;
                        console.log('HDH Filter: Auto-retry ' + retryCount + '/' + MAX_RETRIES);
                        setTimeout(function() {
                            loadTrades(itemSlug, page);
                        }, 2000 * retryCount); // Exponential backoff
                    } else {
                        // Show error UI
                        showError(errorType, errorMsg);
                    }
                    
                    // Log for debugging
                    console.error('HDH Filter Error:', status, error, 'Retry:', retryCount);
                });
            },
            complete: function() {
                isLoading = false;
            }
        });
    }
    
    /**
     * Show error message
     */
    /**
     * Show error UI
     */
    function showError(errorType, message) {
        const $error = $('#trade-error');
        const $errorIcon = $('#error-icon');
        const $errorTitle = $('#error-title');
        const $errorMessage = $('#error-message');
        
        // Set error icon based on type
        const errorIcons = {
            'timeout': '⏱️',
            'network': '🌐',
            'offline': '📡',
            'server': '⚠️',
            'unknown': '❌'
        };
        
        const errorTitles = {
            'timeout': 'Zaman Aşımı',
            'network': 'Bağlantı Hatası',
            'offline': 'İnternet Yok',
            'server': 'Sunucu Hatası',
            'unknown': 'Bir Sorun Oluştu'
        };
        
        $errorIcon.text(errorIcons[errorType] || errorIcons.unknown);
        $errorTitle.text(errorTitles[errorType] || errorTitles.unknown);
        $errorMessage.text(message);
        
        // Show error UI
        $error.fadeIn(300);
    }
    
    /**
     * Retry loading trades
     */
    function retryLoading() {
        retryCount = 0; // Reset retry count for manual retry
        loadTrades(currentFilter || '', currentPage);
    }
    
})(jQuery);
