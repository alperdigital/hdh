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
        
        // Show loading
        $loading.fadeIn(200);
        $grid.fadeOut(200);
        $pagination.fadeOut(200);
        
        // Scroll to top of feed
        $('html, body').animate({
            scrollTop: $container.offset().top - 100
        }, 300);
        
        // AJAX request
        $.ajax({
            url: hdhFilter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hdh_filter_trades',
                item_slug: itemSlug,
                page: page,
                nonce: hdhFilter.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update content
                    $grid.html(response.data.html).fadeIn(300);
                    
                    // Update pagination
                    if (response.data.pagination) {
                        $pagination.html(response.data.pagination).fadeIn(300);
                    } else {
                        $pagination.html('').hide();
                    }
                } else {
                    $grid.html('<div class="no-trades-message"><p>Bir hata oluştu. Lütfen tekrar deneyin.</p></div>').fadeIn(300);
                    $pagination.html('').hide();
                }
            },
            error: function() {
                $grid.html('<div class="no-trades-message"><p>Bir hata oluştu. Lütfen sayfayı yenileyin.</p></div>').fadeIn(300);
                $pagination.html('').hide();
            },
            complete: function() {
                isLoading = false;
                $loading.fadeOut(200);
            }
        });
    }
    
})(jQuery);
