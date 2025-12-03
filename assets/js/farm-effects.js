/**
 * HDH: Farm-themed Lightweight Effects
 * Parallax and subtle animations for farm-game feel
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        
        // HDH: Subtle parallax for hero section
        const heroSection = document.querySelector('.farm-hero');
        if (heroSection) {
            let lastScrollY = window.scrollY;
            
            function handleParallax() {
                const currentScrollY = window.scrollY;
                const heroOffset = heroSection.offsetTop;
                const heroHeight = heroSection.offsetHeight;
                
                // Only apply parallax when hero is in viewport
                if (currentScrollY < heroOffset + heroHeight) {
                    const parallaxSpeed = 0.3;
                    const yPos = -(currentScrollY * parallaxSpeed);
                    const decoration = heroSection.querySelector('.hero-decoration');
                    
                    if (decoration) {
                        decoration.style.transform = 'translateY(' + yPos + 'px)';
                    }
                }
                
                lastScrollY = currentScrollY;
            }
            
            // Throttle scroll events for performance
            let ticking = false;
            window.addEventListener('scroll', function() {
                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        handleParallax();
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        }
        
        // HDH: Smooth scroll reveal for post cards
        const postCards = document.querySelectorAll('.post-item');
        if (postCards.length > 0) {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            postCards.forEach(function(card) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });
        }
        
        // HDH: Floating animation for farm icons/buttons
        const farmButtons = document.querySelectorAll('.btn-farm');
        farmButtons.forEach(function(button) {
            button.addEventListener('mouseenter', function() {
                this.style.animation = 'farmFloat 0.6s ease-in-out';
            });
            
            button.addEventListener('animationend', function() {
                this.style.animation = '';
            });
        });
        
        // HDH: Add CSS animation for floating effect
        if (!document.getElementById('farm-animations-style')) {
            const style = document.createElement('style');
            style.id = 'farm-animations-style';
            style.textContent = `
                @keyframes farmFloat {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-5px); }
                }
            `;
            document.head.appendChild(style);
        }
        
        // HDH: Gentle parallax for footer hills
        const footer = document.querySelector('footer');
        if (footer) {
            window.addEventListener('scroll', function() {
                const scrollY = window.scrollY;
                const windowHeight = window.innerHeight;
                const footerOffset = footer.offsetTop;
                
                if (scrollY + windowHeight > footerOffset) {
                    const parallaxOffset = (scrollY + windowHeight - footerOffset) * 0.1;
                    footer.style.backgroundPositionY = parallaxOffset + 'px';
                }
            });
        }
        
    });
    
})();

