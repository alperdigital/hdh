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
        
        // HDH: Parallax for hero section
        const heroSection = $('.farm-hero-world');
        if (heroSection.length) {
            $(window).on('scroll', function() {
                const scrolled = $(this).scrollTop();
                const heroOffset = heroSection.offset().top;
                const heroHeight = heroSection.outerHeight();
                
                if (scrolled < heroOffset + heroHeight) {
                    const parallaxSpeed = 0.3;
                    const yPos = -(scrolled * parallaxSpeed);
                    heroSection.find('.farm-hero-background').css('transform', 'translateY(' + yPos + 'px)');
                }
            });
        }
        
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
        
        // HDH: Floating elements random movement
        $('.floating-cloud, .floating-leaf').each(function() {
            const $el = $(this);
            const delay = Math.random() * 2;
            const duration = 8 + Math.random() * 4;
            $el.css({
                'animation-delay': delay + 's',
                'animation-duration': duration + 's'
            });
        });
        
        // HDH: Sparkle random positions
        $('.sparkle').each(function() {
            const $el = $(this);
            const delay = Math.random() * 2;
            $el.css('animation-delay', delay + 's');
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
        
        // HDH: Farm sun rotation
        const sun = $('.farm-sun');
        if (sun.length) {
            let rotation = 0;
            setInterval(function() {
                rotation += 0.5;
                sun.css('transform', 'rotate(' + rotation + 'deg)');
            }, 50);
        }
        
    });
    
})(jQuery);

