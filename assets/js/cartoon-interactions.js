/**
 * HDH: Cartoon Farm Interactions
 * Micro-interactions, scroll effects, and animated elements
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // HDH: Header scroll effect
        let lastScroll = 0;
        const header = $('#cartoon-header');
        
        $(window).on('scroll', function() {
            const currentScroll = $(this).scrollTop();
            
            if (currentScroll > 50) {
                header.addClass('scrolled');
            } else {
                header.removeClass('scrolled');
            }
            
            lastScroll = currentScroll;
        });
        
        // HDH: Animate farm board cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const cardObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry, index) {
                if (entry.isIntersecting) {
                    setTimeout(function() {
                        $(entry.target).addClass('animate-in');
                    }, index * 100);
                }
            });
        }, observerOptions);
        
        $('.farm-board-card').each(function() {
            $(this).css({
                'opacity': '0',
                'transform': 'translateY(30px)',
                'transition': 'all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55)'
            });
            cardObserver.observe(this);
        });
        
        // HDH: Add animate-in class styles
        if (!$('#cartoon-animations').length) {
            $('<style id="cartoon-animations">')
                .text('.farm-board-card.animate-in { opacity: 1 !important; transform: translateY(0) !important; }')
                .appendTo('head');
        }
        
        // HDH: Button bounce on click
        $('.btn-wooden-sign').on('click', function() {
            $(this).addClass('clicked');
            setTimeout(function() {
                $(this).removeClass('clicked');
            }.bind(this), 300);
        });
        
        // HDH: Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 800, 'swing');
            }
        });
        
        // HDH: Make trade cards clickable
        $('.trade-card-clickable').on('click', function(e) {
            // Don't navigate if clicking on interactive elements
            if ($(e.target).closest('a, button, input, select, textarea').length) {
                return;
            }
            
            const postUrl = $(this).data('post-url');
            if (postUrl) {
                window.location.href = postUrl;
            }
        });
        
    });
    
})(jQuery);

