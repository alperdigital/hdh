<?php
/**
 * HDH: Lobby Chat Component
 * Public lobby chat with pre-login teaser and post-login participation
 */

if (!defined('ABSPATH')) exit;

/**
 * Render lobby chat component
 */
function hdh_render_lobby_chat() {
    // Check if chat is enabled
    $chat_enabled = get_option('hdh_chat_enabled', true);
    if (!$chat_enabled) {
        return;
    }
    
    $is_logged_in = is_user_logged_in();
    $current_user_id = get_current_user_id();
    
    // Get active users count
    $active_users_count = 0;
    if (function_exists('hdh_get_chat_active_users_count')) {
        $active_users_count = hdh_get_chat_active_users_count(120);
    }
    
    // Get pre-login behavior setting
    $pre_login_behavior = get_option('hdh_chat_pre_login_behavior', 'show_blurred');
    
    // If not logged in and behavior is 'hide', don't show chat
    if (!$is_logged_in && $pre_login_behavior === 'hide') {
        return;
    }
    
    // Get chat messages (only for logged-in users)
    $messages = array();
    if ($is_logged_in && function_exists('hdh_get_chat_messages')) {
        $messages = hdh_get_chat_messages(50, 0);
    }
    
    // Check if user is banned/muted
    $is_banned = false;
    $is_muted = false;
    if ($is_logged_in) {
        if (function_exists('hdh_is_user_chat_banned')) {
            $is_banned = hdh_is_user_chat_banned($current_user_id);
        }
        if (function_exists('hdh_is_user_chat_muted')) {
            $is_muted = hdh_is_user_chat_muted($current_user_id);
        }
    }
    
    // Get post-login behavior
    $post_login_behavior = get_option('hdh_chat_post_login_behavior', 'allow_posting');
    $read_only = ($post_login_behavior === 'read_only');
    
    ?>
    <section class="lobby-chat-section">
        <div class="container">
            <div class="lobby-chat-container">
                <!-- Chat Header -->
                <div class="lobby-chat-header">
                    <h2 class="lobby-chat-title">💬 Lobby Chat</h2>
                    <?php if ($active_users_count > 0) : ?>
                        <div class="lobby-chat-active-users">
                            <span class="active-users-icon">🟢</span>
                            <span class="active-users-count"><?php echo esc_html($active_users_count); ?> kullanıcı aktif</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Messages Area -->
                <div class="lobby-chat-messages" id="lobby-chat-messages">
                    <?php if (!$is_logged_in) : ?>
                        <!-- Pre-login View -->
                        <div class="lobby-chat-pre-login">
                            <?php if ($pre_login_behavior === 'show_blurred') : ?>
                                <div class="chat-messages-blurred">
                                    <?php 
                                    // Show blurred placeholder messages
                                    for ($i = 0; $i < 3; $i++) : 
                                    ?>
                                        <div class="chat-message-placeholder">
                                            <div class="message-placeholder-avatar"></div>
                                            <div class="message-placeholder-content">
                                                <div class="message-placeholder-line"></div>
                                                <div class="message-placeholder-line short"></div>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                            <div class="chat-locked-message">
                                <span class="lock-icon">🔒</span>
                                <p class="lock-text">Sohbete katılmak için giriş yapın</p>
                                <a href="<?php echo esc_url(home_url('/profil')); ?>" class="btn-chat-login">
                                    Giriş yap / Üye ol
                                </a>
                            </div>
                        </div>
                    <?php elseif ($is_banned) : ?>
                        <!-- Banned User View -->
                        <div class="lobby-chat-banned">
                            <span class="ban-icon">🚫</span>
                            <p class="ban-text">Chat kullanımınız yasaklandı</p>
                        </div>
                    <?php else : ?>
                        <!-- Post-login View -->
                        <div class="lobby-chat-messages-list" id="lobby-chat-messages-list">
                            <?php if (empty($messages)) : ?>
                                <div class="chat-empty-state">
                                    <span class="empty-icon">💬</span>
                                    <p class="empty-text">Henüz mesaj yok. İlk mesajı sen gönder!</p>
                                </div>
                            <?php else : ?>
                                <?php foreach ($messages as $message) : 
                                    $user_id = (int) $message['user_id'];
                                    $user_name = $message['user_name'] ?? 'Bilinmeyen';
                                    $user_level = $message['user_level'] ?? 1;
                                    $message_text = $message['message'];
                                    $message_time = $message['created_at'];
                                    $is_censored = ($message['status'] === 'censored');
                                    
                                    // Format timestamp
                                    $time_ago = human_time_diff(strtotime($message_time), current_time('timestamp'));
                                    if ($time_ago === '0 saniye') {
                                        $time_ago = 'Az önce';
                                    } else {
                                        $time_ago = $time_ago . ' önce';
                                    }
                                ?>
                                    <div class="chat-message-item" data-message-id="<?php echo esc_attr($message['id']); ?>">
                                        <div class="chat-message-header">
                                            <a href="<?php echo esc_url(home_url('/profil?user=' . $user_id)); ?>" class="chat-message-user">
                                                <div class="hdh-level-badge lvl-d<?php echo strlen((string)$user_level); ?>" 
                                                     aria-label="Seviye <?php echo esc_attr($user_level); ?>">
                                                    <?php echo esc_html($user_level); ?>
                                                </div>
                                                <span class="chat-message-farm-name"><?php echo esc_html($user_name); ?></span>
                                            </a>
                                            <span class="chat-message-time"><?php echo esc_html($time_ago); ?></span>
                                        </div>
                                        <div class="chat-message-content <?php echo $is_censored ? 'message-censored' : ''; ?>">
                                            <?php echo wp_kses_post($message_text); ?>
                                            <?php if ($is_censored) : ?>
                                                <span class="censored-badge" title="Bu mesaj moderasyon tarafından düzenlendi">⚠️</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Load More Button -->
                        <div class="lobby-chat-load-more" id="lobby-chat-load-more" style="display: none;">
                            <button type="button" class="btn-load-more-messages" id="btn-load-more-messages">
                                Daha fazla yükle
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Input Area (Post-login only, if not banned/muted/read-only) -->
                <?php if ($is_logged_in && !$is_banned && !$is_muted && !$read_only) : 
                    $max_length = (int) get_option('hdh_chat_max_message_length', 200);
                ?>
                    <div class="lobby-chat-input-area">
                        <form id="lobby-chat-form" class="lobby-chat-form">
                            <div class="chat-input-wrapper">
                                <textarea 
                                    id="lobby-chat-input" 
                                    class="lobby-chat-input" 
                                    placeholder="Mesajınızı yazın..."
                                    maxlength="<?php echo esc_attr($max_length); ?>"
                                    rows="2"
                                ></textarea>
                                <button type="submit" class="btn-send-chat-message" id="btn-send-chat-message">
                                    <span class="send-icon">📨</span>
                                </button>
                            </div>
                            <div class="chat-input-footer">
                                <span class="chat-char-count" id="chat-char-count">0 / <?php echo esc_html($max_length); ?></span>
                                <?php if ($is_muted) : ?>
                                    <span class="chat-muted-notice">Chat kullanımınız geçici olarak kısıtlandı</span>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                <?php elseif ($is_logged_in && $read_only) : ?>
                    <div class="lobby-chat-read-only">
                        <p class="read-only-text">Chat şu anda sadece okuma modunda</p>
                    </div>
                <?php endif; ?>
                
                <!-- New Messages Indicator -->
                <div class="lobby-chat-new-messages-indicator" id="lobby-chat-new-messages-indicator" style="display: none;">
                    <button type="button" class="btn-scroll-to-bottom" id="btn-scroll-to-bottom">
                        Yeni mesajlar var ⬇️
                    </button>
                </div>
            </div>
        </div>
    </section>
    <?php
}
