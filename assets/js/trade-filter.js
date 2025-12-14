/**
 * HDH: Trade Filter - Visual Filter System
 * Handles AJAX filtering by offer items
 */

(function($) {
    'use strict';
    
    let currentFilter = null;
    let currentPage = 1;
    let isLoading = false;
    
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
     * Apply filter
     */
    function applyFilter(itemSlug) {
        currentFilter = itemSlug;
        currentPage = 1;
        
        // Update UI
        $('.filter-item-btn').removeClass('active');
        $('.filter-item-btn[data-item-slug="' + itemSlug + '"]').addClass('active');
        $('#btn-clear-filter-visual').fadeIn(300);
        
        // Load trades
        loadTrades(itemSlug, 1);
    }
    
    /**
     * Clear filter
     */
    function clearFilter() {
        currentFilter = null;
        currentPage = 1;
        
        // Update UI
        $('.filter-item-btn').removeClass('active');
        $('#btn-clear-filter-visual').fadeOut(300);
        
        // Load all trades (no filter)
        loadTrades('', 1);
    }
    
    /**
     * Load trades via AJAX
     */
    function loadTrades(itemSlug, page) {
        if (isLoading) return;
        isLoading = true;
        currentPage = page;
        
        const $container = $('#trade-feed-container');
        const $loading = $('#trade-loading');
        const $grid = $('#trade-cards-grid');
        const $pagination = $('#trade-pagination');
        
        // Scroll to top of feed
        $('html, body').animate({
            scrollTop: $container.offset().top - 100
        }, 300);
        
        // Show loading state (hide content first, then show loading)
        $grid.fadeOut(150, function() {
            $pagination.fadeOut(150);
            $loading.fadeIn(200);
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
                // Hide loading first
                $loading.fadeOut(150, function() {
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
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Bir hata oluştu.';
                        showErrorMessage(errorMsg, true);
                    }
                });
            },
            error: function(xhr, status, error) {
                // Hide loading first
                $loading.fadeOut(150, function() {
                    let errorMsg = 'Bir hata oluştu. Lütfen tekrar deneyin.';
                    
                    if (status === 'timeout') {
                        errorMsg = 'İstek zaman aşımına uğradı. İnternet bağlantınızı kontrol edin.';
                    } else if (status === 'error') {
                        errorMsg = 'Sunucuya ulaşılamadı. Lütfen sayfayı yenileyin.';
                    }
                    
                    showErrorMessage(errorMsg, false);
                    
                    // Log for debugging
                    console.error('HDH Filter Error:', status, error);
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
    function showErrorMessage(message, canRetry) {
        const $grid = $('#trade-cards-grid');
        const $pagination = $('#trade-pagination');
        
        let html = '<div class="no-trades-message">';
        html += '<div class="no-trades-message-icon">⚠️</div>';
        html += '<h3 class="no-trades-message-title">Bir Sorun Oluştu</h3>';
        html += '<p>' + message + '</p>';
        html += '<div class="no-trades-message-actions">';
        
        if (canRetry) {
            html += '<button type="button" class="btn-create-listing" onclick="location.reload();">';
            html += '<span>🔄</span><span>Sayfayı Yenile</span>';
            html += '</button>';
        } else {
            html += '<a href="' + window.location.href + '" class="btn-create-listing">';
            html += '<span>🔄</span><span>Sayfayı Yenile</span>';
            html += '</a>';
        }
        
        html += '</div></div>';
        
        $grid.html(html).fadeIn(300);
        $pagination.html('').hide();
    }
    
})(jQuery);
