<?php
/**
 * HDH: Simple Cookie Consent Banner
 */
if (!defined('ABSPATH')) exit;
?>
<div id="hdh-cookie-consent" class="hdh-cookie-consent-banner" style="display: none;">
    <div class="cookie-consent-content">
        <p class="cookie-consent-text">
            Bu site, deneyiminizi iyileştirmek için çerezler kullanır. 
            <a href="<?php echo esc_url(home_url('/gizlilik-politikasi')); ?>" target="_blank">Çerez Politikası</a>
        </p>
        <div class="cookie-consent-actions">
            <button type="button" class="btn-cookie-accept" id="hdh-cookie-accept">Kabul Et</button>
            <button type="button" class="btn-cookie-reject" id="hdh-cookie-reject">Reddet</button>
        </div>
    </div>
</div>

