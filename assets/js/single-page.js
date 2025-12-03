/**
 * Single Page Site Mode - Smooth Scroll Navigation
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        var singlePageMode = $('body').data('single-page-mode') === '1' || $('body').data('single-page-mode') === 1;
        
        if (!singlePageMode) {
            return; // Tek sayfa modu kapalıysa çalışma
        }
        
        // Menü linklerine tıklandığında smooth scroll
        $('.nav-menu a[href^="#section-"]').on('click', function(e) {
            e.preventDefault();
            
            var targetId = $(this).attr('href');
            var $target = $(targetId);
            
            if ($target.length) {
                var offset = 80; // Header yüksekliği için offset
                var targetPosition = $target.offset().top - offset;
                
                $('html, body').animate({
                    scrollTop: targetPosition
                }, 800, 'swing');
                
                // URL hash'ini güncelle (tarayıcı geçmişi için)
                if (history.pushState) {
                    history.pushState(null, null, targetId);
                } else {
                    window.location.hash = targetId;
                }
            }
        });
        
        // Sayfa yüklendiğinde hash varsa o bölüme scroll yap
        if (window.location.hash) {
            var hash = window.location.hash;
            var $target = $(hash);
            
            if ($target.length && $target.hasClass('front-page-section')) {
                setTimeout(function() {
                    var offset = 80;
                    var targetPosition = $target.offset().top - offset;
                    
                    $('html, body').animate({
                        scrollTop: targetPosition
                    }, 600, 'swing');
                }, 100);
            }
        }
        
        // Scroll sırasında aktif menü öğesini güncelle
        var $sections = $('.front-page-section');
        var $navLinks = $('.nav-menu a[href^="#section-"]');
        
        if ($sections.length && $navLinks.length) {
            $(window).on('scroll', function() {
                var scrollPos = $(window).scrollTop() + 100; // Offset
                
                $sections.each(function() {
                    var $section = $(this);
                    var sectionTop = $section.offset().top;
                    var sectionBottom = sectionTop + $section.outerHeight();
                    var sectionId = '#' + $section.attr('id');
                    
                    if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                        $navLinks.removeClass('active');
                        $navLinks.filter('[href="' + sectionId + '"]').addClass('active');
                    }
                });
            });
        }
    });
})(jQuery);

