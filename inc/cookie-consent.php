<?php
/**
 * Cookie Consent Banner
 */

function mi_cookie_consent_banner() {
    // Check if user has already accepted
    if (isset($_COOKIE['mi_cookie_consent'])) {
        return;
    }
    ?>
    <div id="cookie-consent" class="cookie-consent">
        <div class="cookie-consent-content">
            <div class="cookie-consent-text">
                <p><?php _e('Bu web sitesi, size en iyi deneyimi sunmak için çerezler kullanmaktadır. Siteyi kullanmaya devam ederek çerez kullanımını kabul etmiş olursunuz.', 'mi-theme'); ?></p>
            </div>
            <div class="cookie-consent-buttons">
                <button id="accept-cookies" class="cookie-accept"><?php _e('Kabul Et', 'mi-theme'); ?></button>
                <button id="decline-cookies" class="cookie-decline"><?php _e('Reddet', 'mi-theme'); ?></button>
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        const consentBanner = document.getElementById('cookie-consent');
        const acceptBtn = document.getElementById('accept-cookies');
        const declineBtn = document.getElementById('decline-cookies');
        
        if (!consentBanner) return;
        
        function setCookie(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "expires=" + date.toUTCString();
            document.cookie = name + "=" + value + ";" + expires + ";path=/";
        }
        
        function hideBanner() {
            consentBanner.style.display = 'none';
        }
        
        acceptBtn.addEventListener('click', function() {
            setCookie('mi_cookie_consent', 'accepted', 365);
            hideBanner();
        });
        
        declineBtn.addEventListener('click', function() {
            setCookie('mi_cookie_consent', 'declined', 365);
            hideBanner();
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'mi_cookie_consent_banner');

