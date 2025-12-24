# HDH System Architecture Documentation

## Genel Bakış

Bu dokümantasyon, hayday.help platformunun tamamlanmış 3 parça sisteminin (Presence Tracking, Trade Request & Gift Overlay, Lobby Chat & Moderation) mimari yapısını, veritabanı şemasını, backend/frontend bağlantılarını ve güvenlik kontrollerini açıklar.

---

## 1. Sistem Bileşenleri

### 1.1 Parça 1: Presence Tracking & Listing Enhancement System

**Amaç:** Kullanıcı varlık takibi ve ilanlarda presence-based etiketler

**Dosyalar:**
- `inc/presence-system.php` - Core presence logic
- `components/trade-card.php` - Listing card component (presence labels)
- `page-ara.php` - Search page (presence-first sorting)
- `front-page.php` - Homepage (presence-first sorting)
- `inc/presence-admin.php` - Admin settings

**Veritabanı Tabloları:**
- `wp_hdh_user_presence` - User presence tracking

**Ana Fonksiyonlar:**
- `hdh_update_user_presence($user_id)` - Presence güncelleme (throttled: 30s)
- `hdh_get_user_presence($user_id)` - Presence verisi alma
- `hdh_get_active_users_count($threshold_seconds)` - Aktif kullanıcı sayısı
- `hdh_get_presence_bucket($user_id)` - Bucket hesaplama (online/5min/1hour/today/yesterday/3+days)
- `hdh_format_presence_label($bucket, $timestamp)` - Türkçe label formatlama
- `hdh_get_listings_with_presence($args, $sort_by)` - Presence-first sorting

**Güncelleme Mekanizması:**
- WordPress Heartbeat API (her 15-60 saniyede bir)
- Event system integration (`hdh_event_logged` hook)
- Throttling: Max 1 update per 30 seconds per user

**Admin Ayarları:**
- `hdh_presence_online_threshold` (default: 120s)
- `hdh_presence_5min_threshold` (default: 300s)
- `hdh_presence_1hour_threshold` (default: 3600s)
- `hdh_presence_privacy_default` (default: false)

---

### 1.2 Parça 2: Trade Request & Gift Overlay System

**Amaç:** Trade request flow (120s accept window) ve global gift overlay

**Dosyalar:**
- `inc/trade-request-system.php` - Trade request core logic
- `inc/trade-request-handlers.php` - AJAX handlers
- `inc/notification-system.php` - Notification system
- `inc/trade-ping-system.php` - Ping/check-in system
- `inc/trade-ping-handlers.php` - Ping AJAX handlers
- `inc/trade-report-system.php` - Report system
- `inc/trade-report-handlers.php` - Report AJAX handlers
- `components/notification-bell.php` - Notification UI
- `components/gift-overlay.php` - Gift overlay component
- `components/trade-report-modal.php` - Report form modal
- `assets/js/trade-request.js` - Trade request frontend
- `assets/js/notifications.js` - Notification frontend
- `assets/js/gift-overlay.js` - Gift overlay frontend
- `assets/css/notifications.css` - Notification styles
- `assets/css/gift-overlay.css` - Gift overlay styles
- `single-hayday_trade.php` - Single trade page (updated UI)

**Veritabanı Tabloları:**
- `wp_hdh_trade_requests` - Trade requests
- `wp_hdh_notifications` - Site notifications
- `wp_hdh_trade_pings` - Trade pings
- `wp_hdh_trade_reports` - Trade reports

**Ana Fonksiyonlar:**

**Trade Request:**
- `hdh_create_trade_request($listing_id, $requester_user_id)` - Request oluşturma
- `hdh_accept_trade_request($request_id, $owner_user_id)` - Request kabul etme
- `hdh_reject_trade_request($request_id, $owner_user_id)` - Request reddetme
- `hdh_get_trade_request($request_id)` - Request alma
- `hdh_get_pending_requests_for_owner($owner_user_id)` - Owner için pending requests
- `hdh_expire_old_requests()` - Expired requests işaretleme (cron)

**Notification:**
- `hdh_create_notification($user_id, $type, $title, $message, $link_url)` - Notification oluşturma
- `hdh_get_user_notifications($user_id, $unread_only, $limit)` - Notifications alma
- `hdh_mark_notification_read($notification_id, $user_id)` - Okundu işaretleme
- `hdh_mark_all_read($user_id)` - Tümünü okundu işaretleme
- `hdh_get_unread_count($user_id)` - Unread count

**Ping System:**
- `hdh_send_trade_ping($trade_session_id, $from_user_id)` - Ping gönderme
- `hdh_respond_to_ping($ping_id, $to_user_id, $response)` - Ping yanıtlama
- `hdh_get_pending_pings($user_id)` - Pending pings alma

**Report System:**
- `hdh_create_trade_report($trade_session_id, $reporter_user_id, $issue_type, $description)` - Report oluşturma
- `hdh_get_trade_reports($status, $limit)` - Reports alma (admin)
- `hdh_update_trade_report_status($report_id, $status, $admin_note)` - Status güncelleme
- `hdh_get_user_report_count($user_id, $hours)` - Rate limiting için count

**Rate Limiting:**
- Trade Request: Max 3 requests per listing per user per day, 10min cooldown
- Ping: Max 1 ping per 10 minutes per trade, max 5 pings per day per user
- Report: Max 3 reports per day per user

**AJAX Endpoints:**
- `hdh_send_trade_request` - Request gönderme
- `hdh_accept_trade_request` - Request kabul etme
- `hdh_reject_trade_request` - Request reddetme
- `hdh_get_trade_request_status` - Status polling
- `hdh_get_notifications` - Notifications alma
- `hdh_mark_notification_read` - Okundu işaretleme
- `hdh_mark_all_notifications_read` - Tümünü okundu işaretleme
- `hdh_get_unread_count` - Unread count
- `hdh_send_trade_ping` - Ping gönderme
- `hdh_respond_to_ping` - Ping yanıtlama
- `hdh_get_pending_pings` - Pending pings
- `hdh_create_trade_report` - Report oluşturma
- `hdh_get_trade_reports` - Reports alma (admin)
- `hdh_update_trade_report_status` - Status güncelleme (admin)
- `hdh_get_active_trades` - Active trades listesi

**Integration:**
- Trade request accept edildiğinde otomatik trade session oluşturuluyor (`trade-request-handlers.php` line 103-107)
- Trade session sistemi mevcut (`inc/trade-session.php`) ile uyumlu

---

### 1.3 Parça 3: Lobby Chat & Moderation System

**Amaç:** Ana sayfada lobby chat ve moderasyon sistemi

**Dosyalar:**
- `inc/chat-system.php` - Chat core logic
- `inc/chat-moderation.php` - Moderation engine
- `inc/chat-handlers.php` - Chat AJAX handlers
- `inc/chat-admin.php` - Admin panel (settings + moderation queue + trade reports)
- `components/lobby-chat.php` - Chat UI component
- `assets/js/lobby-chat.js` - Chat frontend
- `assets/css/lobby-chat.css` - Chat styles
- `front-page.php` - Homepage (chat component)

**Veritabanı Tabloları:**
- `wp_hdh_chat_messages` - Chat messages
- `wp_hdh_chat_warnings` - Chat warnings

**Ana Fonksiyonlar:**
- `hdh_create_chat_message($user_id, $message)` - Message oluşturma
- `hdh_get_chat_messages($limit, $offset, $include_deleted)` - Messages alma
- `hdh_moderate_message($message, $user_id)` - Moderation engine
- `hdh_check_chat_rate_limit($user_id)` - Rate limiting
- `hdh_check_duplicate_message($user_id, $message)` - Duplicate detection
- `hdh_increment_chat_warning($user_id, $message_id, $warning_type)` - Warning increment
- `hdh_is_user_chat_banned($user_id)` - Ban check
- `hdh_is_user_chat_muted($user_id)` - Mute check

**Moderation Rules:**
- Profanity check (word list)
- Insults check (word list)
- Links check (regex: http/https/www/shorteners)
- Phone check (regex)
- Email check (regex)

**Rate Limiting:**
- Max 3 messages per minute per user
- Cooldown: 20 seconds between messages
- Slow mode: Level < 5: 60s, Level 5-10: 30s, Level 10+: normal

**AJAX Endpoints:**
- `hdh_send_chat_message` - Message gönderme
- `hdh_get_chat_messages` - Messages alma
- `hdh_get_active_users_count` - Active users count

**Admin Settings:**
- Chat enable/disable
- Pre-login behavior (show/hide/blurred)
- Post-login behavior (allow_posting/read_only)
- Moderation filters toggle
- Action on violation (censor/block)
- Warning thresholds
- Rate limits
- Slow mode rules

---

## 2. Veritabanı Şeması

### 2.1 Presence System

```sql
wp_hdh_user_presence
- id (bigint, PK)
- user_id (bigint, UNIQUE)
- last_seen_at (datetime)
- updated_at (datetime)
- INDEX: last_seen_at
```

### 2.2 Trade Request System

```sql
wp_hdh_trade_requests
- id (bigint, PK)
- listing_id (bigint, INDEX)
- requester_user_id (bigint, INDEX)
- owner_user_id (bigint, INDEX)
- status (varchar(20): pending/accepted/rejected/expired, INDEX)
- requested_at (datetime)
- expires_at (datetime, INDEX)
- accepted_at (datetime)
- rejected_at (datetime)
- created_at (datetime)
- updated_at (datetime)
```

### 2.3 Notification System

```sql
wp_hdh_notifications
- id (bigint, PK)
- user_id (bigint, INDEX)
- type (varchar(50), INDEX)
- title (varchar(255))
- message (text)
- link_url (varchar(500))
- is_read (tinyint(1), INDEX)
- created_at (datetime, INDEX)
```

### 2.4 Ping System

```sql
wp_hdh_trade_pings
- id (bigint, PK)
- trade_session_id (bigint, INDEX)
- from_user_id (bigint, INDEX)
- to_user_id (bigint, INDEX)
- status (varchar(20): pending/responded/ignored, INDEX)
- response (varchar(50): here/10min_later/not_available_today)
- created_at (datetime, INDEX)
- responded_at (datetime)
```

### 2.5 Report System

```sql
wp_hdh_trade_reports
- id (bigint, PK)
- trade_session_id (bigint, INDEX)
- reporter_user_id (bigint, INDEX)
- reported_user_id (bigint, INDEX)
- issue_type (varchar(50): no_response/scam/other)
- description (varchar(200))
- status (varchar(20): pending/reviewed/resolved, INDEX)
- admin_note (text)
- created_at (datetime, INDEX)
- reviewed_at (datetime)
- updated_at (datetime)
```

### 2.6 Chat System

```sql
wp_hdh_chat_messages
- id (bigint, PK)
- user_id (bigint, INDEX)
- message (text, sanitized)
- message_raw (text, original)
- status (varchar(20): published/censored/blocked/deleted, INDEX)
- moderation_flags (longtext, JSON)
- warning_strikes (int)
- created_at (datetime, INDEX)

wp_hdh_chat_warnings
- id (bigint, PK)
- user_id (bigint, INDEX)
- message_id (bigint, INDEX)
- warning_type (varchar(50), INDEX)
- strike_count (int)
- created_at (datetime, INDEX)
```

---

## 3. Backend-Frontend Bağlantıları

### 3.1 Presence System

**Backend → Frontend:**
- Presence data: `hdh_get_presence_bucket()` → `components/trade-card.php` (listing cards)
- Active users count: `hdh_get_active_users_count()` → `components/lobby-chat.php` (chat header)

**Frontend → Backend:**
- Heartbeat API: Automatic presence updates (no explicit frontend call)

### 3.2 Trade Request System

**Backend → Frontend:**
- Request status: `hdh_get_trade_request()` → `single-hayday_trade.php` (status display)
- Pending requests: `hdh_get_pending_requests_for_owner()` → `single-hayday_trade.php` (owner view)

**Frontend → Backend (AJAX):**
- `hdh_send_trade_request` → `assets/js/trade-request.js` (send request)
- `hdh_accept_trade_request` → `single-hayday_trade.php` (accept button)
- `hdh_reject_trade_request` → `single-hayday_trade.php` (reject button)
- `hdh_get_trade_request_status` → `assets/js/trade-request.js` (polling every 5s)

**Data Flow:**
```
User clicks "Teklif Gönder"
  → AJAX: hdh_send_trade_request
    → Backend: hdh_create_trade_request()
      → Creates notification for owner
      → Returns request_id
  → Frontend: Updates UI, starts countdown timer
  → Polling: hdh_get_trade_request_status (every 5s)
    → Updates UI when status changes
```

### 3.3 Notification System

**Backend → Frontend:**
- Unread count: `hdh_get_unread_count()` → `components/notification-bell.php` (badge)
- Notifications list: `hdh_get_user_notifications()` → `assets/js/notifications.js` (dropdown)

**Frontend → Backend (AJAX):**
- `hdh_get_notifications` → `assets/js/notifications.js` (load notifications)
- `hdh_mark_notification_read` → `assets/js/notifications.js` (mark as read)
- `hdh_mark_all_notifications_read` → `assets/js/notifications.js` (mark all read)
- `hdh_get_unread_count` → `assets/js/notifications.js` (polling every 30s)

**Data Flow:**
```
Notification created (backend)
  → Stored in wp_hdh_notifications
  → Frontend polling detects new unread count
    → Updates badge
    → User clicks bell
      → AJAX: hdh_get_notifications
        → Displays in dropdown
      → User clicks notification
        → AJAX: hdh_mark_notification_read
          → Navigates to link_url
```

### 3.4 Gift Overlay System

**Backend → Frontend:**
- Active trades: `hdh_get_user_active_trades()` → `components/gift-overlay.php` (trade list)
- Trade session: `hdh_get_trade_session()` → `assets/js/gift-overlay.js` (detail view)

**Frontend → Backend (AJAX):**
- `hdh_get_active_trades` → `assets/js/gift-overlay.js` (load trades, polling every 10s)
- `hdh_get_trade_session` → `assets/js/gift-overlay.js` (detail view, polling every 10s)
- `hdh_get_listing_data` → `assets/js/gift-overlay.js` (listing info for detail)
- `hdh_complete_trade_step` → `assets/js/gift-overlay.js` (step completion)
- `hdh_send_trade_ping` → `assets/js/gift-overlay.js` (ping button)
- `hdh_create_trade_report` → `assets/js/gift-overlay.js` (report form)

**Data Flow:**
```
User opens gift overlay
  → AJAX: hdh_get_active_trades
    → Backend: hdh_get_user_active_trades()
      → Queries wp_hdh_trade_sessions
      → Enriches with listing/user data
      → Returns array of trades
  → Frontend: Renders trade list
  → User clicks "Aç"
    → AJAX: hdh_get_trade_session + hdh_get_listing_data
      → Renders detail view
      → Starts polling (every 10s)
        → Updates UI when step/status changes
```

### 3.5 Ping System

**Backend → Frontend:**
- Pending pings: `hdh_get_pending_pings()` → Notification system (ping notifications)

**Frontend → Backend (AJAX):**
- `hdh_send_trade_ping` → `assets/js/gift-overlay.js` (ping button)
- `hdh_respond_to_ping` → Notification UI (one-tap responses)

**Data Flow:**
```
User clicks "Ping / Kontrol Et"
  → AJAX: hdh_send_trade_ping
    → Backend: hdh_send_trade_ping()
      → Rate limiting check
      → Creates ping record
      → Creates notification for counterpart
  → Frontend: Shows success message
  → Counterpart sees notification
    → Clicks notification
      → Shows ping response options
      → AJAX: hdh_respond_to_ping
        → Updates ping status
        → Creates notification for sender
```

### 3.6 Report System

**Backend → Frontend:**
- Reports list: `hdh_get_trade_reports()` → `inc/chat-admin.php` (admin queue)

**Frontend → Backend (AJAX):**
- `hdh_create_trade_report` → `assets/js/gift-overlay.js` (report form submission)
- `hdh_get_trade_reports` → Admin panel (reports list)
- `hdh_update_trade_report_status` → Admin panel (review/resolve/dismiss)

**Data Flow:**
```
User clicks "Sorun Bildir"
  → Opens report modal
  → Fills form (issue_type, description)
  → AJAX: hdh_create_trade_report
    → Backend: hdh_create_trade_report()
      → Validation (max 200 chars, duplicate check, self-report check)
      → Rate limiting (max 3/day)
      → Creates report record
  → Frontend: Shows success, closes modal
  → Admin sees in moderation queue
    → Actions: Review/Resolve/Dismiss
      → AJAX: hdh_update_trade_report_status
        → Updates status + admin_note
```

### 3.7 Chat System

**Backend → Frontend:**
- Messages: `hdh_get_chat_messages()` → `components/lobby-chat.php` (message list)
- Active users: `hdh_get_active_users_count()` → `components/lobby-chat.php` (header)

**Frontend → Backend (AJAX):**
- `hdh_send_chat_message` → `assets/js/lobby-chat.js` (message input)
- `hdh_get_chat_messages` → `assets/js/lobby-chat.js` (polling every 5s)
- `hdh_get_active_users_count` → `assets/js/lobby-chat.js` (polling every 30s)

**Data Flow:**
```
User types message
  → AJAX: hdh_send_chat_message
    → Backend: hdh_create_chat_message()
      → Rate limiting check
      → Duplicate check
      → Moderation: hdh_moderate_message()
        → Returns: status (published/censored/blocked), flags, censored_message
      → Warning increment if violation
      → Stores message
  → Frontend: Adds message to list (if published/censored)
  → Polling: hdh_get_chat_messages (every 5s)
    → Detects new messages
    → Updates UI
```

---

## 4. Güvenlik Kontrolleri

### 4.1 Authentication & Authorization

**Tüm AJAX Handler'lar:**
- `is_user_logged_in()` check (logged-in only endpoints)
- `current_user_can('manage_options')` check (admin-only endpoints)
- User ID verification (user can only access own data)

**Örnekler:**
```php
// trade-request-handlers.php
if (!is_user_logged_in()) {
    wp_send_json_error(array('message' => 'Giriş yapmanız gerekiyor'));
    return;
}

// trade-report-handlers.php (admin endpoints)
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_send_json_error(array('message' => 'Yetkiniz yok'));
    return;
}
```

### 4.2 Nonce Verification

**Tüm AJAX Endpoints:**
- `wp_verify_nonce($_POST['nonce'], 'action_name')` check
- Nonce'lar frontend'de `wp_localize_script()` ile oluşturuluyor

**Nonce Action'ları:**
- `hdh_trade_request` - Trade request actions
- `hdh_trade_session` - Trade session actions (gift overlay, ping, report)
- `hdh_notifications` - Notification actions
- `hdh_chat_message` - Chat message actions
- `hdh_admin_moderation` - Admin moderation actions

**Örnek:**
```php
// functions.php
wp_localize_script('hdh-gift-overlay', 'hdhGiftOverlay', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('hdh_trade_session'),
));

// trade-report-handlers.php
if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hdh_trade_session')) {
    wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız'));
    return;
}
```

### 4.3 Input Sanitization

**Tüm User Inputs:**
- `sanitize_text_field()` - Text inputs
- `sanitize_textarea_field()` - Textarea inputs
- `sanitize_key()` - Keys/IDs
- `absint()` - Integer IDs
- `esc_url_raw()` - URLs
- `esc_html()` - HTML output
- `esc_attr()` - HTML attributes

**Örnekler:**
```php
// trade-report-handlers.php
$session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
$issue_type = isset($_POST['issue_type']) ? sanitize_text_field($_POST['issue_type']) : '';
$description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';

// trade-report-system.php
'issue_type' => sanitize_key($issue_type),
'description' => sanitize_text_field($description),
```

### 4.4 SQL Injection Prevention

**Tüm Database Queries:**
- `$wpdb->prepare()` kullanımı (prepared statements)
- Direct SQL string concatenation YOK

**Örnek:**
```php
// trade-report-system.php
$duplicate = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$table_name}
     WHERE trade_session_id = %d
     AND reporter_user_id = %d
     AND created_at >= %s
     LIMIT 1",
    $trade_session_id,
    $reporter_user_id,
    $twenty_four_hours_ago
));
```

### 4.5 XSS Prevention

**Frontend:**
- `escapeHtml()` function in JavaScript (gift-overlay.js, notifications.js, lobby-chat.js)
- `innerHTML` kullanımından önce escape

**Backend:**
- `esc_html()`, `esc_attr()`, `esc_url()` kullanımı
- Raw user input direkt output edilmiyor

**Örnek:**
```javascript
// assets/js/gift-overlay.js
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Usage
content.innerHTML = `<div>${escapeHtml(userInput)}</div>`;
```

### 4.6 Rate Limiting

**Trade Request:**
- Max 3 requests per listing per user per day
- 10 minutes cooldown between requests to same owner

**Ping:**
- Max 1 ping per 10 minutes per trade
- Max 5 pings per day per user

**Report:**
- Max 3 reports per day per user
- Duplicate prevention: Same session, same reporter, within 24 hours

**Chat:**
- Max 3 messages per minute per user
- 20 seconds cooldown between messages
- Slow mode: Level-based cooldowns

**Implementation:**
```php
// trade-report-system.php
$twenty_four_hours_ago = date('Y-m-d H:i:s', current_time('timestamp') - 86400);
$duplicate = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$table_name}
     WHERE trade_session_id = %d
     AND reporter_user_id = %d
     AND created_at >= %s
     LIMIT 1",
    $trade_session_id,
    $reporter_user_id,
    $twenty_four_hours_ago
));
```

---

## 5. Error Handling

### 5.1 Backend Error Handling

**WP_Error Kullanımı:**
- Tüm fonksiyonlar `WP_Error` döndürüyor hata durumlarında
- Frontend'de `is_wp_error()` check yapılıyor

**Örnek:**
```php
// trade-report-system.php
if (!$session) {
    return new WP_Error('session_not_found', 'Hediyeleşme oturumu bulunamadı');
}

// trade-report-handlers.php
$report_id = hdh_create_trade_report($session_id, $user_id, $issue_type, $description);

if (is_wp_error($report_id)) {
    wp_send_json_error(array('message' => $report_id->get_error_message()));
    return;
}
```

### 5.2 Frontend Error Handling

**Try-Catch & Error Responses:**
- AJAX calls'da `.catch()` kullanımı
- Error messages kullanıcıya gösteriliyor (toast notifications)

**Örnek:**
```javascript
// assets/js/gift-overlay.js
fetch(config.ajaxUrl, {...})
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Başarılı!', 'success');
        } else {
            showToast(data.data?.message || 'Hata oluştu', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Bir hata oluştu', 'error');
    });
```

### 5.3 Graceful Degradation

**Function Existence Checks:**
- `function_exists()` checks before calling optional functions
- Fallback mechanisms where applicable

**Örnek:**
```php
// trade-report-system.php
if (!function_exists('hdh_get_trade_session')) {
    return new WP_Error('function_not_found', 'Trade session sistemi mevcut değil');
}

// presence-system.php
if (function_exists('hdh_get_user_state')) {
    $counterpart_state = hdh_get_user_state($counterpart_id);
    $session['counterpart_level'] = $counterpart_state['level'] ?? 1;
}
```

---

## 6. Dependency Chain & Load Order

### 6.1 Functions.php Include Order

```php
// Core systems (no dependencies)
require_once get_template_directory() . '/inc/presence-system.php';
require_once get_template_directory() . '/inc/notification-system.php';
require_once get_template_directory() . '/inc/trade-request-system.php';
require_once get_template_directory() . '/inc/trade-session.php'; // Existing
require_once get_template_directory() . '/inc/trade-ping-system.php';
require_once get_template_directory() . '/inc/trade-report-system.php';

// Handlers (depend on systems)
require_once get_template_directory() . '/inc/trade-request-handlers.php';
require_once get_template_directory() . '/inc/trade-ping-handlers.php';
require_once get_template_directory() . '/inc/trade-report-handlers.php';

// Components (depend on systems)
require_once get_template_directory() . '/components/notification-bell.php';
require_once get_template_directory() . '/components/gift-overlay.php';
require_once get_template_directory() . '/components/trade-report-modal.php';
require_once get_template_directory() . '/components/lobby-chat.php';

// Admin (depend on systems)
require_once get_template_directory() . '/inc/chat-system.php';
require_once get_template_directory() . '/inc/chat-moderation.php';
require_once get_template_directory() . '/inc/chat-handlers.php';
require_once get_template_directory() . '/inc/chat-admin.php';
require_once get_template_directory() . '/inc/presence-admin.php';
```

### 6.2 Function Dependencies

**Trade Report System:**
- Requires: `hdh_get_trade_session()` (from `inc/trade-session.php`)
- Optional: `hdh_log_event()` (from `inc/event-system.php`)

**Trade Request System:**
- Requires: `hdh_create_trade_session()` (from `inc/trade-session.php`)
- Optional: `hdh_create_notification()` (from `inc/notification-system.php`)
- Optional: `hdh_log_event()` (from `inc/event-system.php`)

**Ping System:**
- Requires: `hdh_get_trade_session()` (from `inc/trade-session.php`)
- Optional: `hdh_create_notification()` (from `inc/notification-system.php`)

**Presence System:**
- Optional: `hdh_get_user_state()` (from `inc/user-state-system.php`)
- Optional: `hdh_log_event()` (from `inc/event-system.php`)

**Chat System:**
- Optional: `hdh_get_active_users_count()` (from `inc/presence-system.php`)
- Fallback: Uses `user_meta` for `hdh_last_active` if presence system not available

---

## 7. Potansiyel Sorunlar ve Çözümler

### 7.1 Tespit Edilen ve Düzeltilen Sorunlar

**1. SQL Query Hatası (Düzeltildi)**
- **Sorun:** `inc/trade-report-system.php` line 118'de SQL sorgusu eksikti
- **Çözüm:** `SELECT id FROM {$table_name}` eklendi

**2. Missing `updated_at` Column (Düzeltildi)**
- **Sorun:** `wp_hdh_trade_reports` tablosunda `updated_at` column yoktu ama kullanılıyordu
- **Çözüm:** Table schema'ya `updated_at datetime DEFAULT NULL` eklendi

**3. Missing Trade Reports Functions (Düzeltildi)**
- **Sorun:** `inc/chat-admin.php`'de `hdh_render_trade_reports_tab()` ve `hdh_handle_trade_report_action()` fonksiyonları eksikti
- **Çözüm:** Fonksiyonlar eklendi

**4. Duplicate Chat Menu (Düzeltildi)**
- **Sorun:** `inc/admin-panel.php`'de duplicate Chat Management submenu vardı
- **Çözüm:** Duplicate kaldırıldı

### 7.2 Potansiyel Riskler ve Önlemler

**1. Database Connection Failure**
- **Risk:** `$wpdb->insert()` veya `$wpdb->update()` false dönebilir
- **Önlem:** Tüm database operations `=== false` check yapıyor, `WP_Error` döndürüyor

**2. Missing Function Dependencies**
- **Risk:** Optional functions (`hdh_log_event`, `hdh_get_user_state`) yoksa hata
- **Önlem:** `function_exists()` checks before calling

**3. Race Conditions**
- **Risk:** Concurrent requests (e.g., multiple trade requests)
- **Önlem:** Database-level constraints (UNIQUE indexes), application-level duplicate checks

**4. Memory Issues**
- **Risk:** Large result sets (e.g., 1000+ notifications)
- **Önlem:** Limits applied (50 notifications, 50 reports, 50 messages)

**5. Performance Issues**
- **Risk:** Excessive polling (multiple tabs, high frequency)
- **Önlem:** Throttling (30s for presence, 10s for trades, 5s for chat), polling only when panel open

---

## 8. Test Senaryoları

### 8.1 Trade Request Flow

1. **Request Creation:**
   - User A sends request to User B's listing
   - Request created with 120s expiry
   - Notification sent to User B
   - Countdown timer starts on User A's page

2. **Request Acceptance:**
   - User B accepts request
   - Trade session automatically created
   - Other pending requests for same listing rejected
   - Notifications sent to both parties

3. **Request Expiry:**
   - 120 seconds pass
   - Request marked as expired (cron or on-read)
   - UI updates to show expired status

### 8.2 Gift Overlay Flow

1. **Overlay Display:**
   - User has active trades
   - Gift icon appears with badge count
   - Click opens overlay panel

2. **Trade Detail View:**
   - User clicks "Aç" on a trade
   - Detail view shows: summary, 5-step checklist, farm codes, buttons
   - Real-time polling updates UI when counterpart completes step

3. **Step Completion:**
   - User clicks "Tamamla" button
   - AJAX call to `hdh_complete_trade_step`
   - UI updates, toast notification shown

### 8.3 Report System Flow

1. **Report Creation:**
   - User clicks "Sorun Bildir"
   - Modal opens with structured form
   - User selects issue type, enters description (max 200 chars)
   - Submit → AJAX call → Validation → Rate limiting → Create report

2. **Admin Review:**
   - Admin sees report in moderation queue
   - Actions: Review, Resolve, Dismiss
   - Admin note added, status updated

### 8.4 Chat System Flow

1. **Message Sending:**
   - User types message
   - Submit → AJAX call → Rate limiting → Duplicate check → Moderation
   - Message stored (published/censored/blocked)
   - Warning incremented if violation

2. **Real-time Updates:**
   - Polling every 5 seconds for new messages
   - UI updates with new messages
   - Active users count updates every 30 seconds

---

## 9. Performance Optimizations

### 9.1 Database Optimizations

- **Indexes:** All foreign keys and frequently queried columns indexed
- **Query Optimization:** JOINs used efficiently, no N+1 queries
- **Limits:** All queries have reasonable limits (50 items default)

### 9.2 Caching

- **Active Users Count:** Could be cached (transient) for 30 seconds
- **Presence Buckets:** Computed on-demand, not cached (real-time requirement)

### 9.3 Polling Optimization

- **Throttling:** Polling intervals configurable, reduced when panel closed
- **Conditional Polling:** Only polls when relevant UI is open
- **Payload Size:** Minimal data sent (only necessary fields)

---

## 10. Admin Panel Structure

### 10.1 HDH Dashboard Menu

```
HDH Dashboard (main menu)
├── Dashboard
├── Pre-Login
├── Post-Login
├── Global Design
├── Content
├── Components
├── Çekiliş
├── Presence (NEW)
├── Chat
└── Advanced
```

### 10.2 Chat Yönetimi Submenu

```
Chat Yönetimi
├── Ayarlar (tab)
│   ├── Core Toggles
│   ├── Moderation Settings
│   └── Rate Limits
├── Moderation Queue (tab)
│   ├── Users with Warnings
│   └── Filter-Triggered Messages
└── Trade Reports (tab) (NEW)
    ├── Pending Reports
    ├── Reviewed Reports
    └── Resolved Reports
```

### 10.3 Presence Ayarları Page

```
Presence Ayarları
├── Online Threshold (seconds)
├── 5min Threshold (seconds)
├── 1hour Threshold (seconds)
└── Privacy Mode Default (toggle)
```

---

## 11. File Structure Summary

### 11.1 Core System Files

```
inc/
├── presence-system.php (Parça 1)
├── presence-admin.php (NEW - Phase 2)
├── trade-request-system.php (Parça 2)
├── trade-request-handlers.php (Parça 2)
├── notification-system.php (Parça 2)
├── trade-ping-system.php (Parça 2)
├── trade-ping-handlers.php (Parça 2)
├── trade-report-system.php (NEW - Phase 1)
├── trade-report-handlers.php (NEW - Phase 1)
├── chat-system.php (Parça 3)
├── chat-moderation.php (Parça 3)
├── chat-handlers.php (Parça 3)
└── chat-admin.php (Parça 3, updated with trade reports)
```

### 11.2 Component Files

```
components/
├── notification-bell.php (Parça 2)
├── gift-overlay.php (Parça 2)
├── trade-report-modal.php (NEW - Phase 1)
└── lobby-chat.php (Parça 3)
```

### 11.3 Frontend Files

```
assets/js/
├── trade-request.js (Parça 2)
├── notifications.js (Parça 2)
├── gift-overlay.js (Parça 2, updated with report modal)
└── lobby-chat.js (Parça 3)

assets/css/
├── notifications.css (Parça 2)
├── gift-overlay.css (Parça 2, updated with report modal styles)
└── lobby-chat.css (Parça 3)
```

---

## 12. Critical Code Paths

### 12.1 Trade Request → Trade Session Flow

```
1. User sends trade request
   → hdh_create_trade_request()
   → Creates wp_hdh_trade_requests record
   → Creates notification for owner

2. Owner accepts request
   → hdh_accept_trade_request()
   → Updates request status to 'accepted'
   → Rejects other pending requests
   → hdh_create_trade_session() (automatic)
   → Creates wp_hdh_trade_sessions record
   → Creates notifications for both parties

3. Trade session active
   → Users can complete steps
   → Gift overlay shows active trade
   → Real-time updates via polling
```

### 12.2 Report Creation → Admin Review Flow

```
1. User creates report
   → hdh_create_trade_report()
   → Validation (type, length, duplicate, self-report)
   → Rate limiting check
   → Creates wp_hdh_trade_reports record
   → Logs event (if available)

2. Admin views reports
   → hdh_get_trade_reports('pending')
   → Enriches with user/session data
   → Displays in admin queue

3. Admin takes action
   → hdh_update_trade_report_status()
   → Updates status + admin_note
   → Logs event (if available)
```

### 12.3 Chat Message → Moderation Flow

```
1. User sends message
   → hdh_create_chat_message()
   → Rate limiting check
   → Duplicate check
   → hdh_moderate_message()
     → Runs all enabled filters
     → Returns status + flags
   → Stores message (published/censored/blocked)
   → Increments warning if violation

2. Message displayed
   → hdh_get_chat_messages()
   → Returns messages with user data
   → Frontend renders (censored messages show ***)

3. Admin moderation
   → Views in moderation queue
   → Can delete, mute user, ban user, reset warnings
```

---

## 13. Security Checklist

### ✅ Implemented Security Measures

- [x] All AJAX endpoints require authentication
- [x] All AJAX endpoints verify nonce
- [x] Admin endpoints check `current_user_can('manage_options')`
- [x] All user inputs sanitized
- [x] All database queries use prepared statements
- [x] XSS prevention (escapeHtml in JS, esc_html in PHP)
- [x] Rate limiting on all user actions
- [x] Duplicate prevention (reports, requests)
- [x] Self-action prevention (self-reporting)
- [x] Error messages don't leak sensitive information
- [x] Function existence checks before calling optional functions

### ⚠️ Areas Requiring Attention

1. **Nonce Action Names:** Ensure consistency across all endpoints
2. **Rate Limiting:** Monitor and adjust thresholds based on usage
3. **Database Indexes:** Verify all foreign keys are indexed
4. **Error Logging:** Consider adding error logging for production debugging

---

## 14. Known Limitations

1. **Polling Instead of WebSockets:** Shared hosting constraint, polling used instead
2. **No Real-time Notifications:** Notifications require page refresh or polling
3. **Presence Throttling:** Max 1 update per 30 seconds (performance trade-off)
4. **Message Length Limits:** Chat messages limited to 200 chars (admin configurable)
5. **Report Description Limit:** 200 chars max (abuse prevention)

---

## 15. Maintenance Notes

### 15.1 Database Cleanup

**Recommended Cron Jobs:**
- Expire old trade requests (every 5 minutes)
- Cleanup old notifications (30+ days, read only)
- Cleanup old chat messages (optional, based on storage)

### 15.2 Performance Monitoring

**Key Metrics to Monitor:**
- Database query times (especially presence queries)
- AJAX response times
- Polling frequency impact
- Memory usage (large result sets)

### 15.3 Backup Considerations

**Critical Tables to Backup:**
- `wp_hdh_trade_sessions` (active trades)
- `wp_hdh_trade_requests` (pending requests)
- `wp_hdh_notifications` (user notifications)
- `wp_hdh_chat_messages` (chat history)

---

## 16. Integration Points with Existing Systems

### 16.1 Trade Session System

**File:** `inc/trade-session.php`

**Integration:**
- Trade request acceptance automatically creates trade session
- Gift overlay uses `hdh_get_trade_session()` for detail view
- Report system uses `hdh_get_trade_session()` to get session data

### 16.2 Event System

**File:** `inc/event-system.php` (assumed)

**Integration:**
- Trade request creation logged (if `hdh_log_event` exists)
- Trade report creation logged (if `hdh_log_event` exists)
- Presence updates trigger events (if `hdh_event_logged` hook exists)

### 16.3 User State System

**File:** `inc/user-state-system.php` (assumed)

**Integration:**
- Presence system uses `hdh_get_user_state()` for user levels (optional)
- Chat system uses user levels for slow mode

---

## 17. Conclusion

Sistem, 3 parça halinde tamamlanmış ve birbirleriyle entegre çalışıyor. Tüm güvenlik kontrolleri, error handling, ve validation mekanizmaları yerinde. Database şeması optimize edilmiş, frontend-backend bağlantıları doğru kurulmuş.

**Sistem Durumu:** Production-ready ✅

**Kritik Kontroller:**
- ✅ Tüm fonksiyonlar tanımlı
- ✅ Tüm AJAX handler'lar registered
- ✅ Tüm database tabloları oluşturuluyor
- ✅ Tüm include'lar doğru sırada
- ✅ Güvenlik kontrolleri mevcut
- ✅ Error handling mevcut

**Son Düzeltmeler:**
- ✅ SQL query hatası düzeltildi
- ✅ `updated_at` column eklendi
- ✅ Trade reports admin fonksiyonları eklendi
- ✅ Duplicate chat menu kaldırıldı

---

**Dokümantasyon Tarihi:** 2024
**Versiyon:** 1.0
**Durum:** Complete & Production-Ready

