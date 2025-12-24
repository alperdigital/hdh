<?php
/**
 * HDH: Trade Report Modal Component
 * Structured report form for trade issues
 */

if (!defined('ABSPATH')) exit;

/**
 * Render trade report modal
 * 
 * @param int $session_id Trade session ID
 */
function hdh_render_trade_report_modal($session_id = 0) {
    ?>
    <div id="trade-report-modal" class="trade-report-modal" style="display: none;">
        <div class="report-modal-overlay"></div>
        <div class="report-modal-content">
            <div class="report-modal-header">
                <h3 class="report-modal-title">Sorun Bildir</h3>
                <button type="button" class="report-modal-close" id="trade-report-modal-close">×</button>
            </div>
            <form id="trade-report-form" class="trade-report-form">
                <input type="hidden" name="session_id" id="trade-report-session-id" value="<?php echo esc_attr($session_id); ?>">
                
                <div class="form-group">
                    <label class="form-label">Sorun Tipi</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="issue_type" value="no_response" required>
                            <span class="radio-text">Yanıt vermiyor</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="issue_type" value="scam" required>
                            <span class="radio-text">Dolandırıcılık şüphesi</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="issue_type" value="other" required>
                            <span class="radio-text">Diğer</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="trade-report-description" class="form-label">Açıklama (İsteğe bağlı)</label>
                    <textarea name="description" 
                              id="trade-report-description" 
                              class="form-textarea" 
                              rows="3" 
                              maxlength="200"
                              placeholder="Sorunu kısaca açıklayın..."></textarea>
                    <small class="form-hint">
                        <span id="trade-report-char-count">0</span>/200 karakter
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel-report" id="btn-cancel-report">İptal</button>
                    <button type="submit" class="btn-submit-report" id="btn-submit-report">Gönder</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

