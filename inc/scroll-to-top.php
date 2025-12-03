<?php
/**
 * Scroll to Top Button
 */

function mi_scroll_to_top() {
    ?>
    <button id="scroll-to-top" class="scroll-to-top" aria-label="Yukarı Çık">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 15l-6-6-6 6"/>
        </svg>
    </button>
    
    <script>
    (function() {
        const scrollBtn = document.getElementById('scroll-to-top');
        
        if (!scrollBtn) return;
        
        // Show/hide button based on scroll position
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollBtn.classList.add('visible');
            } else {
                scrollBtn.classList.remove('visible');
            }
        });
        
        // Scroll to top on click
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'mi_scroll_to_top');

