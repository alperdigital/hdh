<?php
/**
 * HDH: Trade Roadmap Component
 * Step-by-step gift exchange stepper (5 steps)
 */

if (!defined('ABSPATH')) exit;

function hdh_render_trade_roadmap($session, $listing_id, $current_user_id) {
    if (!$session) {
        return;
    }
    
    $is_starter = ($session['starter_user_id'] == $current_user_id);
    $is_owner = ($session['owner_user_id'] == $current_user_id);
    $current_step = hdh_get_trade_session_current_step($session);
    $status = $session['status'];
    
    // Get user info
    $owner_info = get_userdata($session['owner_user_id']);
    $starter_info = get_userdata($session['starter_user_id']);
    // Get farm tag (Çiftlik Etiketi) from registration, fallback to hayday_farm_number
    $owner_farm_number = get_user_meta($session['owner_user_id'], 'farm_tag', true);
    if (empty($owner_farm_number)) {
        $owner_farm_number = get_user_meta($session['owner_user_id'], 'hayday_farm_number', true);
    }
    $starter_farm_number = get_user_meta($session['starter_user_id'], 'farm_tag', true);
    if (empty($starter_farm_number)) {
        $starter_farm_number = get_user_meta($session['starter_user_id'], 'hayday_farm_number', true);
    }
    
    // Get user levels
    $owner_level = 1;
    $starter_level = 1;
    if (function_exists('hdh_get_user_state')) {
        $owner_state = hdh_get_user_state($session['owner_user_id']);
        $starter_state = hdh_get_user_state($session['starter_user_id']);
        $owner_level = $owner_state['level'] ?? 1;
        $starter_level = $starter_state['level'] ?? 1;
    }
    
    // Get user display names
    $owner_name = $owner_info ? $owner_info->display_name : 'Bilinmeyen';
    $starter_name = $starter_info ? $starter_info->display_name : 'Bilinmeyen';
    
    // Determine digit classes
    $owner_digit_class = strlen((string)$owner_level) === 1 ? 'lvl-d1' : (strlen((string)$owner_level) === 2 ? 'lvl-d2' : 'lvl-d3');
    $starter_digit_class = strlen((string)$starter_level) === 1 ? 'lvl-d1' : (strlen((string)$starter_level) === 2 ? 'lvl-d2' : 'lvl-d3');
    
    // Get listing data
    $trade_data = hdh_get_trade_data($listing_id);
    
    // Step definitions (5 steps)
    $steps = array(
        1 => array(
            'icon' => '👥',
            'title' => 'Arkadaş olarak ekle',
            'description' => 'İlan sahibinin çiftlik kodunu kopyalayıp oyunda arkadaşlık isteği gönderin',
            'user_role' => 'starter',
            'button_text' => 'Arkadaşlık isteği gönderdim',
            'show_farm_code' => 'owner', // Show owner's farm code
        ),
        2 => array(
            'icon' => '✅',
            'title' => 'Arkadaşlık isteğini kabul edin',
            'description' => 'Arkadaşlık isteğini kabul edin',
            'user_role' => 'owner',
            'button_text' => 'Arkadaşlık isteğini kabul ettim',
            'show_farm_code' => 'starter', // Show starter's farm code
        ),
        3 => array(
            'icon' => '🎁',
            'title' => 'Vereceğiniz hediyeyi hazırlayıp dükkana koyun',
            'description' => 'Vereceğiniz hediyeyi hazırlayıp dükkana koyun',
            'user_role' => 'starter',
            'button_text' => 'Hediyemi hazırladım',
            'show_farm_code' => false,
        ),
        4 => array(
            'icon' => '📦',
            'title' => 'Hediyeni al ve hediyeni hazırla',
            'description' => 'Hediyeyi aldıktan sonra kendi hediyenizi hazırlayın',
            'user_role' => 'owner',
            'button_text' => 'Hediyeni aldım ve kendi hediyemi hazırladım',
            'show_farm_code' => false,
        ),
        5 => array(
            'icon' => '🎉',
            'title' => 'Hediyeni al',
            'description' => 'İlan sahibinin gönderdiği hediyeyi aldığınızı onaylayın',
            'user_role' => 'starter',
            'button_text' => 'Hediyemi aldım',
            'show_farm_code' => false,
        ),
    );
    
    ?>
    <section id="trade-roadmap" class="trade-roadmap-section">
        <div class="roadmap-header">
            <h2 class="roadmap-title">
                <span class="roadmap-icon">🗺️</span>
                <span class="roadmap-text">Hediyeleşme Yol Haritası</span>
            </h2>
            <div class="roadmap-progress">
                <span class="progress-text"><?php echo esc_html($current_step); ?>/5 tamamlandı</span>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo esc_attr(($current_step / 5) * 100); ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- Participants Info Cards -->
        <div class="roadmap-participants">
            <!-- Owner Card -->
            <div class="participant-card participant-owner">
                <div class="participant-header">
                    <div class="hdh-level-badge <?php echo esc_attr($owner_digit_class); ?>" 
                         aria-label="Seviye <?php echo esc_attr($owner_level); ?>">
                        <?php echo esc_html($owner_level); ?>
                    </div>
                    <div class="participant-name"><?php echo esc_html($owner_name); ?></div>
                </div>
                <div class="participant-farm-code">
                    <span class="farm-code-label">Çiftlik Kodu:</span>
                    <span class="farm-code-value"><?php echo esc_html($owner_farm_number ?: 'Belirtilmemiş'); ?></span>
                    <?php if ($owner_farm_number) : ?>
                        <button type="button" class="btn-copy-farm-code" data-farm-code="<?php echo esc_attr($owner_farm_number); ?>">
                            📋 Kopyala
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Arrow with Gift Icon -->
            <div class="participant-arrow-wrapper">
                <div class="participant-gift-icon">🎁</div>
                <div class="participant-arrow-left">→</div>
                <div class="participant-arrow-right">→</div>
            </div>
            
            <!-- Starter Card -->
            <div class="participant-card participant-starter">
                <div class="participant-header">
                    <div class="hdh-level-badge <?php echo esc_attr($starter_digit_class); ?>" 
                         aria-label="Seviye <?php echo esc_attr($starter_level); ?>">
                        <?php echo esc_html($starter_level); ?>
                    </div>
                    <div class="participant-name"><?php echo esc_html($starter_name); ?></div>
                </div>
                <div class="participant-farm-code">
                    <span class="farm-code-label">Çiftlik Kodu:</span>
                    <span class="farm-code-value"><?php echo esc_html($starter_farm_number ?: 'Belirtilmemiş'); ?></span>
                    <?php if ($starter_farm_number) : ?>
                        <button type="button" class="btn-copy-farm-code" data-farm-code="<?php echo esc_attr($starter_farm_number); ?>">
                            📋 Kopyala
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($status === 'DISPUTED') : ?>
            <div class="roadmap-dispute-alert">
                <span class="dispute-icon">⚠️</span>
                <div class="dispute-content">
                    <strong>İnceleme Altında</strong>
                    <p>Bu hediyeleşme için bir anlaşmazlık bildirildi. İnceleme tamamlanana kadar adımlar kilitlidir.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="roadmap-steps">
            <?php foreach ($steps as $step_num => $step) : 
                $step_done = false;
                $step_locked = false;
                $step_current = false;
                $can_complete = false;
                
                if ($step_num == 1) {
                    $step_done = !empty($session['step1_starter_done_at']);
                    $step_current = ($current_step == 1 && $status === 'ACTIVE');
                    $can_complete = ($is_starter && $step_current && !$step_done);
                    $step_locked = ($current_step < 1);
                } elseif ($step_num == 2) {
                    $step_done = !empty($session['step2_owner_done_at']);
                    $step_current = ($current_step == 2 && $status === 'ACTIVE');
                    $can_complete = ($is_owner && $step_current && !$step_done);
                    $step_locked = ($current_step < 2);
                } elseif ($step_num == 3) {
                    $step_done = !empty($session['step3_starter_done_at']);
                    $step_current = ($current_step == 3 && $status === 'ACTIVE');
                    $can_complete = ($is_starter && $step_current && !$step_done);
                    $step_locked = ($current_step < 3);
                } elseif ($step_num == 4) {
                    $step_done = !empty($session['step4_owner_done_at']);
                    $step_current = ($current_step == 4 && $status === 'ACTIVE');
                    $can_complete = ($is_owner && $step_current && !$step_done);
                    $step_locked = ($current_step < 4);
                } elseif ($step_num == 5) {
                    $step_done = !empty($session['step5_starter_done_at']);
                    $step_current = ($current_step == 5 && $status === 'ACTIVE');
                    $can_complete = ($is_starter && $step_current && !$step_done);
                    $step_locked = ($current_step < 5);
                }
                
                $step_status = 'locked';
                if ($step_done) {
                    $step_status = 'completed';
                } elseif ($step_current) {
                    $step_status = 'current';
                } elseif ($step_locked) {
                    $step_status = 'locked';
                } else {
                    $step_status = 'waiting';
                }
                
                $waiting_for_other = ($step_current && !$can_complete);
                
                // Determine alignment: starter steps (1,3,5) = right, owner steps (2,4) = left
                $step_alignment = ($step['user_role'] === 'starter') ? 'right' : 'left';
                $step_class = 'roadmap-step-' . $step_alignment;
            ?>
                <div class="roadmap-step roadmap-step-<?php echo esc_attr($step_status); ?> <?php echo esc_attr($step_class); ?>" data-step="<?php echo esc_attr($step_num); ?>">
                    <div class="step-header">
                        <div class="step-icon-wrapper">
                            <span class="step-icon"><?php echo esc_html($step['icon']); ?></span>
                            <?php if ($step_done) : ?>
                                <span class="step-check">✅</span>
                            <?php elseif ($step_locked) : ?>
                                <span class="step-lock">🔒</span>
                            <?php endif; ?>
                        </div>
                        <div class="step-info">
                            <h3 class="step-title"><?php echo esc_html($step['title']); ?></h3>
                            <p class="step-description"><?php echo esc_html($step['description']); ?></p>
                        </div>
                    </div>
                    
                    <?php 
                    // Show farm code for step 1 (owner's code for starter) and step 2 (starter's code for owner)
                    $show_farm_code = false;
                    $farm_code_to_show = '';
                    if ($step_num == 1 && $step_current && $can_complete && $step['show_farm_code'] === 'owner' && $owner_farm_number) {
                        $show_farm_code = true;
                        $farm_code_to_show = $owner_farm_number;
                    } elseif ($step_num == 2 && $step_current && $can_complete && $step['show_farm_code'] === 'starter' && $starter_farm_number) {
                        $show_farm_code = true;
                        $farm_code_to_show = $starter_farm_number;
                    }
                    
                    if ($show_farm_code) : ?>
                        <div class="step-farm-code">
                            <div class="farm-code-display">
                                <span class="farm-code-label">Çiftlik Kodu:</span>
                                <span class="farm-code-value" id="farm-code-value-<?php echo esc_attr($step_num); ?>"><?php echo esc_html($farm_code_to_show); ?></span>
                                <?php if ($step_num == 1) : ?>
                                    <button type="button" class="btn-copy-farm-code" data-farm-code="<?php echo esc_attr($farm_code_to_show); ?>">
                                        📋 Kopyala
                                    </button>
                                <?php else : ?>
                                    <span class="farm-code-readonly">(Yazılı olarak gösteriliyor)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="step-actions">
                        <?php if ($can_complete) : ?>
                            <button type="button" 
                                    class="btn-step-complete" 
                                    data-session-id="<?php echo esc_attr($session['id']); ?>"
                                    data-step="<?php echo esc_attr($step_num); ?>">
                                <?php echo esc_html($step['button_text']); ?>
                            </button>
                        <?php elseif ($waiting_for_other) : ?>
                            <div class="step-waiting">
                                <span class="waiting-icon">⏳</span>
                                <span class="waiting-text">Karşı tarafın işlemi bekleniyor...</span>
                            </div>
                        <?php elseif ($step_done) : ?>
                            <div class="step-completed-badge">
                                <span class="completed-icon">✅</span>
                                <span class="completed-text">Tamamlandı</span>
                            </div>
                        <?php elseif ($step_locked) : ?>
                            <div class="step-locked-badge">
                                <span class="locked-icon">🔒</span>
                                <span class="locked-text">Önceki adımları tamamlayın</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($status === 'ACTIVE' || $status === 'DISPUTED') : ?>
            <div class="roadmap-dispute-section">
                <button type="button" class="btn-dispute" data-session-id="<?php echo esc_attr($session['id']); ?>">
                    <span class="dispute-icon">⚠️</span>
                    <span class="dispute-text">Sorun Bildir / Anlaşmazlık</span>
                </button>
            </div>
        <?php endif; ?>
    </section>
    
    <!-- Dispute Modal -->
    <div id="dispute-modal" class="dispute-modal" style="display: none;">
        <div class="dispute-modal-overlay"></div>
        <div class="dispute-modal-content">
            <div class="dispute-modal-header">
                <h3 class="dispute-modal-title">Sorun Bildir / Anlaşmazlık</h3>
                <button type="button" class="dispute-modal-close">×</button>
            </div>
            <form id="dispute-form" class="dispute-form">
                <input type="hidden" name="session_id" id="dispute-session-id" value="<?php echo esc_attr($session['id']); ?>">
                
                <div class="form-group">
                    <label for="dispute-reason" class="form-label">Sebep</label>
                    <select name="reason" id="dispute-reason" class="form-select" required>
                        <option value="">Seçiniz...</option>
                        <option value="friend_request_not_accepted">Arkadaşlık isteği kabul edilmedi</option>
                        <option value="gift_not_received">Hediye gönderildi ama alınmadı</option>
                        <option value="wrong_gift">Hediye yanlış/eksik geldi</option>
                        <option value="other_not_completing">Karşı taraf adımı tamamlamıyor</option>
                        <option value="other">Diğer</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="dispute-text" class="form-label">Açıklama</label>
                    <textarea name="text" 
                              id="dispute-text" 
                              class="form-textarea" 
                              rows="4" 
                              maxlength="500"
                              placeholder="Lütfen sorunu detaylı olarak açıklayın..."
                              required></textarea>
                    <small class="form-hint">
                        <span id="dispute-char-count">0</span>/500 karakter
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel-dispute">İptal</button>
                    <button type="submit" class="btn-submit-dispute">Gönder</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}
