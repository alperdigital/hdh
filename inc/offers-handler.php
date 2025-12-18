<?php
/**
 * Offers and Messaging Handler
 * Handles AJAX requests for offers and messages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle make offer AJAX request
 */
function hdh_handle_make_offer() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_make_offer')) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'security_verification_failed', 'Güvenlik doğrulaması başarısız.')));
        return;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'login_required', 'Teklif yapmak için giriş yapmalısınız.')));
        return;
    }
    
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $wanted_qty = isset($_POST['offer_wanted_qty']) ? absint($_POST['offer_wanted_qty']) : 0;
    $offer_items = isset($_POST['offer_items']) ? $_POST['offer_items'] : array();
    $offer_qty = isset($_POST['offer_qty']) ? $_POST['offer_qty'] : array();
    
    // Validate
    if (!$listing_id || !$wanted_qty || empty($offer_items)) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'fill_all_fields', 'Lütfen tüm alanları doldurun.')));
        return;
    }
    
    // Check if listing exists
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'listing_not_found', 'İlan bulunamadı.')));
        return;
    }
    
    // Check if user is not the owner
    $current_user_id = get_current_user_id();
    if ($listing->post_author == $current_user_id) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'cannot_offer_own_listing', 'Kendi ilanınıza teklif yapamazsınız.')));
        return;
    }
    
    // Prepare offer items data
    $offer_items_data = array();
    foreach ($offer_items as $item_slug) {
        $qty = isset($offer_qty[$item_slug]) ? absint($offer_qty[$item_slug]) : 0;
        if ($qty > 0) {
            $offer_items_data[] = array(
                'slug' => sanitize_text_field($item_slug),
                'qty' => $qty
            );
        }
    }
    
    if (empty($offer_items_data)) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'select_at_least_one_gift', 'En az bir hediye seçmelisiniz.')));
        return;
    }
    
    // Create offer post
    $offer_id = wp_insert_post(array(
        'post_type' => 'hayday_offer',
        'post_status' => 'publish',
        'post_author' => $current_user_id,
        'post_title' => 'Offer for listing #' . $listing_id,
    ));
    
    if (is_wp_error($offer_id)) {
        wp_send_json_error(array('message' => hdh_get_message('ajax', 'offer_create_error', 'Teklif oluşturulurken bir hata oluştu.')));
        return;
    }
    
    // Save offer meta
    update_post_meta($offer_id, '_hdh_listing_id', $listing_id);
    update_post_meta($offer_id, '_hdh_offer_wanted_qty', $wanted_qty);
    update_post_meta($offer_id, '_hdh_offer_items', $offer_items_data);
    update_post_meta($offer_id, '_hdh_offer_status', 'pending');
    
    wp_send_json_success(array(
        'message' => hdh_get_message('ajax', 'offer_created_success', 'Teklifiniz başarıyla gönderildi!'),
        'offer_id' => $offer_id
    ));
}
add_action('wp_ajax_hdh_make_offer', 'hdh_handle_make_offer');

/**
 * Handle accept offer AJAX request
 */
function hdh_handle_accept_offer() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_offer_response')) {
        wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız.'));
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bu işlem için giriş yapmalısınız.'));
        return;
    }
    
    $offer_id = isset($_POST['offer_id']) ? absint($_POST['offer_id']) : 0;
    
    if (!$offer_id) {
        wp_send_json_error(array('message' => 'Geçersiz teklif ID.'));
        return;
    }
    
    // Get offer and listing
    $offer = get_post($offer_id);
    if (!$offer || $offer->post_type !== 'hayday_offer') {
        wp_send_json_error(array('message' => 'Teklif bulunamadı.'));
        return;
    }
    
    $listing_id = get_post_meta($offer_id, '_hdh_listing_id', true);
    $listing = get_post($listing_id);
    
    if (!$listing || $listing->post_type !== 'hayday_trade') {
        wp_send_json_error(array('message' => 'İlan bulunamadı.'));
        return;
    }
    
    // Check if current user is the listing owner
    $current_user_id = get_current_user_id();
    if ($listing->post_author != $current_user_id) {
        wp_send_json_error(array('message' => 'Bu işlemi yapma yetkiniz yok.'));
        return;
    }
    
    // Update offer status
    update_post_meta($offer_id, '_hdh_offer_status', 'accepted');
    
    // Update listing status
    update_post_meta($listing_id, '_hdh_trade_status', 'accepted');
    update_post_meta($listing_id, '_hdh_accepted_offer_id', $offer_id);
    update_post_meta($listing_id, '_hdh_accepted_offerer_id', $offer->post_author);
    
    // Reject all other offers
    $other_offers = new WP_Query(array(
        'post_type' => 'hayday_offer',
        'posts_per_page' => -1,
        'post__not_in' => array($offer_id),
        'meta_query' => array(
            array(
                'key' => '_hdh_listing_id',
                'value' => $listing_id,
                'compare' => '='
            )
        )
    ));
    
    if ($other_offers->have_posts()) {
        while ($other_offers->have_posts()) {
            $other_offers->the_post();
            update_post_meta(get_the_ID(), '_hdh_offer_status', 'rejected');
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success(array(
        'message' => 'Teklif kabul edildi! Artık mesajlaşabilirsiniz.',
    ));
}
add_action('wp_ajax_hdh_accept_offer', 'hdh_handle_accept_offer');

/**
 * Handle reject offer AJAX request
 */
function hdh_handle_reject_offer() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_offer_response')) {
        wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız.'));
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bu işlem için giriş yapmalısınız.'));
        return;
    }
    
    $offer_id = isset($_POST['offer_id']) ? absint($_POST['offer_id']) : 0;
    
    if (!$offer_id) {
        wp_send_json_error(array('message' => 'Geçersiz teklif ID.'));
        return;
    }
    
    // Get offer and listing
    $offer = get_post($offer_id);
    if (!$offer || $offer->post_type !== 'hayday_offer') {
        wp_send_json_error(array('message' => 'Teklif bulunamadı.'));
        return;
    }
    
    $listing_id = get_post_meta($offer_id, '_hdh_listing_id', true);
    $listing = get_post($listing_id);
    
    if (!$listing || $listing->post_type !== 'hayday_trade') {
        wp_send_json_error(array('message' => 'İlan bulunamadı.'));
        return;
    }
    
    // Check if current user is the listing owner
    $current_user_id = get_current_user_id();
    if ($listing->post_author != $current_user_id) {
        wp_send_json_error(array('message' => 'Bu işlemi yapma yetkiniz yok.'));
        return;
    }
    
    // Update offer status
    update_post_meta($offer_id, '_hdh_offer_status', 'rejected');
    
    wp_send_json_success(array(
        'message' => 'Teklif reddedildi.',
    ));
}
add_action('wp_ajax_hdh_reject_offer', 'hdh_handle_reject_offer');

/**
 * Handle send message AJAX request
 */
function hdh_handle_send_message() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_messaging')) {
        wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız.'));
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Mesaj göndermek için giriş yapmalısınız.'));
        return;
    }
    
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $offer_id = isset($_POST['offer_id']) ? absint($_POST['offer_id']) : 0;
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
    
    if (!$listing_id || !$offer_id || empty($message)) {
        wp_send_json_error(array('message' => 'Lütfen mesajınızı yazın.'));
        return;
    }
    
    // Verify user is part of the conversation
    $current_user_id = get_current_user_id();
    $listing = get_post($listing_id);
    $offer = get_post($offer_id);
    
    if (!$listing || !$offer) {
        wp_send_json_error(array('message' => 'İlan veya teklif bulunamadı.'));
        return;
    }
    
    $is_owner = ($listing->post_author == $current_user_id);
    $is_offerer = ($offer->post_author == $current_user_id);
    
    if (!$is_owner && !$is_offerer) {
        wp_send_json_error(array('message' => 'Bu konuşmaya katılma yetkiniz yok.'));
        return;
    }
    
    // Create message post
    $message_id = wp_insert_post(array(
        'post_type' => 'hayday_message',
        'post_status' => 'publish',
        'post_author' => $current_user_id,
        'post_content' => $message,
        'post_title' => 'Message for listing #' . $listing_id,
    ));
    
    if (is_wp_error($message_id)) {
        wp_send_json_error(array('message' => 'Mesaj gönderilirken bir hata oluştu.'));
        return;
    }
    
    // Save message meta
    update_post_meta($message_id, '_hdh_listing_id', $listing_id);
    update_post_meta($message_id, '_hdh_offer_id', $offer_id);
    
    wp_send_json_success(array(
        'message' => 'Mesaj gönderildi.',
        'message_id' => $message_id,
        'author_name' => get_the_author_meta('display_name', $current_user_id),
        'date' => current_time('d M Y, H:i'),
        'content' => $message
    ));
}
add_action('wp_ajax_hdh_send_message', 'hdh_handle_send_message');

/**
 * Handle load messages AJAX request
 */
function hdh_handle_load_messages() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_messaging')) {
        wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız.'));
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Mesajları görmek için giriş yapmalısınız.'));
        return;
    }
    
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    $offer_id = isset($_POST['offer_id']) ? absint($_POST['offer_id']) : 0;
    
    if (!$listing_id || !$offer_id) {
        wp_send_json_error(array('message' => 'Geçersiz parametreler.'));
        return;
    }
    
    // Get messages
    $messages_query = new WP_Query(array(
        'post_type' => 'hayday_message',
        'posts_per_page' => 50,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_hdh_listing_id',
                'value' => $listing_id,
                'compare' => '='
            ),
            array(
                'key' => '_hdh_offer_id',
                'value' => $offer_id,
                'compare' => '='
            )
        ),
        'orderby' => 'date',
        'order' => 'ASC'
    ));
    
    $messages = array();
    $current_user_id = get_current_user_id();
    
    if ($messages_query->have_posts()) {
        while ($messages_query->have_posts()) {
            $messages_query->the_post();
            $author_id = get_post_field('post_author', get_the_ID());
            $messages[] = array(
                'id' => get_the_ID(),
                'author_id' => $author_id,
                'author_name' => get_the_author_meta('display_name', $author_id),
                'content' => get_the_content(),
                'date' => get_the_date('d M Y, H:i'),
                'is_own' => ($author_id == $current_user_id)
            );
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success(array(
        'messages' => $messages
    ));
}
add_action('wp_ajax_hdh_load_messages', 'hdh_handle_load_messages');

/**
 * Handle confirm exchange AJAX request
 */
function hdh_handle_confirm_exchange() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_confirm_exchange')) {
        wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız.'));
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bu işlem için giriş yapmalısınız.'));
        return;
    }
    
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    
    if (!$listing_id) {
        wp_send_json_error(array('message' => 'Geçersiz ilan ID.'));
        return;
    }
    
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'hayday_trade') {
        wp_send_json_error(array('message' => 'İlan bulunamadı.'));
        return;
    }
    
    $current_user_id = get_current_user_id();
    $is_owner = ($listing->post_author == $current_user_id);
    $accepted_offerer_id = get_post_meta($listing_id, '_hdh_accepted_offerer_id', true);
    $is_offerer = ($current_user_id == $accepted_offerer_id);
    
    if (!$is_owner && !$is_offerer) {
        wp_send_json_error(array('message' => 'Bu işlemi yapma yetkiniz yok.'));
        return;
    }
    
    // Mark confirmation
    if ($is_owner) {
        update_post_meta($listing_id, '_hdh_owner_confirmed', true);
    } else {
        update_post_meta($listing_id, '_hdh_offerer_confirmed', true);
    }
    
    // Check if both confirmed
    $owner_confirmed = get_post_meta($listing_id, '_hdh_owner_confirmed', true);
    $offerer_confirmed = get_post_meta($listing_id, '_hdh_offerer_confirmed', true);
    
    if ($owner_confirmed && $offerer_confirmed) {
        // Complete the trade
        update_post_meta($listing_id, '_hdh_trade_status', 'completed');
        
        // NOTE: Bilet ödülleri artık görevlerden alınacak, burada otomatik ödül verilmiyor
        // Kullanıcılar görevlerden "Ödülünü Al" butonuna tıklayarak ödüllerini alacaklar
        
        // Trigger exchange completed hook for quest/task tracking (progress update only, no auto-reward)
        do_action('hdh_exchange_completed', $listing->post_author, $listing_id);
        do_action('hdh_exchange_completed', $accepted_offerer_id, $listing_id);
        
        wp_send_json_success(array(
            'message' => 'Hediyeleşme tamamlandı! Görevlerden ödüllerinizi alabilirsiniz.',
            'completed' => true
        ));
    } else {
        wp_send_json_success(array(
            'message' => 'Onayınız kaydedildi. Diğer tarafın onayı bekleniyor.',
            'completed' => false
        ));
    }
}
add_action('wp_ajax_hdh_confirm_exchange', 'hdh_handle_confirm_exchange');

