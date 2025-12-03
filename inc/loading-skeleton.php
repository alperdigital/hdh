<?php
/**
 * Loading Skeleton
 */

function mi_loading_skeleton() {
    ?>
    <div class="skeleton-loader" id="skeleton-loader" style="display: none;">
        <div class="skeleton-item">
            <div class="skeleton-thumbnail"></div>
            <div class="skeleton-content">
                <div class="skeleton-line skeleton-title"></div>
                <div class="skeleton-line skeleton-meta"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line skeleton-short"></div>
            </div>
        </div>
        <div class="skeleton-item">
            <div class="skeleton-thumbnail"></div>
            <div class="skeleton-content">
                <div class="skeleton-line skeleton-title"></div>
                <div class="skeleton-line skeleton-meta"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line skeleton-short"></div>
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        // Show skeleton on page load
        window.addEventListener('load', function() {
            const skeleton = document.getElementById('skeleton-loader');
            if (skeleton) {
                setTimeout(function() {
                    skeleton.style.display = 'none';
                }, 500);
            }
        });
        
        // Show skeleton on AJAX requests
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxStart(function() {
                const skeleton = document.getElementById('skeleton-loader');
                if (skeleton) {
                    skeleton.style.display = 'block';
                }
            }).ajaxComplete(function() {
                const skeleton = document.getElementById('skeleton-loader');
                if (skeleton) {
                    setTimeout(function() {
                        skeleton.style.display = 'none';
                    }, 300);
                }
            });
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'mi_loading_skeleton');

