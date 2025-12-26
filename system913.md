# HDH System 913 - Bağımlılık Haritası ve Sistem Mimarisi

**Oluşturulma Tarihi:** 2024-12-19  
**Son Güncelleme:** 2024-12-19  
**Amaç:** Sistem bağımlılıklarını, fonksiyon ilişkilerini ve potansiyel sorunları belgelemek

---

## 📋 İçindekiler

1. [Dosya Yükleme Sırası](#dosya-yükleme-sırası)
2. [Fonksiyon Bağımlılıkları](#fonksiyon-bağımlılıkları)
3. [Veritabanı Tabloları](#veritabanı-tabloları)
4. [AJAX Handler'ları](#ajax-handlerları)
5. [Admin Panel Yapısı](#admin-panel-yapısı)
6. [Component Yapısı](#component-yapısı)
7. [Potansiyel Sorunlar ve Çözümler](#potansiyel-sorunlar-ve-çözümler)
8. [Devre Dışı Sistemler](#devre-dışı-sistemler)

---

## 1. Dosya Yükleme Sırası

### 1.1 Core Systems (functions.php - Satır 43-86)

**Yükleme Sırası:**
```
1. inc/items-config.php                    # Eşya konfigürasyonu (diğerleri tarafından kullanılır)
2. components/item-card.php                # Eşya kartı component
3. components/trade-card.php               # Takas kartı component
4. inc/trade-offers.php                    # Custom Post Type (hayday_trade)
5. inc/jeton-system.php                    # Jeton sistemi
6. inc/create-trade-handler.php            # İlan oluşturma handler
7. inc/trade-settings.php                  # Takas ayarları
8. inc/registration-handler.php            # Kayıt handler
9. inc/trust-system.php                    # Güven sistemi
10. inc/listing-actions-handler.php         # İlan aksiyonları
11. inc/offers-cpt.php                      # Offers CPT
12. inc/offers-handler.php                  # Offers handler
13. inc/widgets.php                         # Widget alanları
14. inc/social-functions.php               # Sosyal medya fonksiyonları
15. inc/ajax-handlers.php                   # Genel AJAX handler'ları
16. inc/lottery-config.php                  # Çekiliş konfigürasyonu
17. inc/lottery-management.php            # Çekiliş yönetimi
18. inc/lottery-handler.php                # Çekiliş handler
19. inc/auth-redirect.php                   # Auth yönlendirme
20. inc/asset-loader.php                    # Asset yükleme
21. inc/trade-integrity.php                 # Takas bütünlüğü
22. inc/trade-session.php                   # Takas oturumu (5-step sistem)
23. inc/trade-session-handlers.php          # Takas oturumu handler'ları
24. inc/trade-session-admin.php             # Takas oturumu admin
25. inc/event-system.php                    # Event logging sistemi
26. inc/user-state-system.php               # Kullanıcı durumu (level, bilet)
27. inc/presence-system.php                 # Kullanıcı varlık takibi
28. inc/trade-request-system.php            # Takas istek sistemi
29. inc/notification-system.php             # Bildirim sistemi
30. inc/trade-request-handlers.php          # Takas istek handler'ları
31. inc/chat-system.php                     # Lobby chat sistemi
32. inc/chat-moderation.php                 # Chat moderasyon
33. inc/chat-handlers.php                   # Chat handler'ları
34. inc/chat-admin.php                      # Chat admin paneli
35. inc/kvkk-compliance.php                 # KVKK uyumluluğu
36. inc/moderation-system.php               # Moderation sistemi
37. inc/admin-moderation-ui.php             # Admin moderation UI
38. inc/trust-display.php                   # Güven gösterimi
39. components/user-badge.php                # Kullanıcı rozeti
40. components/quest-panel.php              # Görev paneli
41. components/tasks-panel.php              # Görevler paneli
42. components/lobby-chat.php               # Lobby chat component
```

### 1.2 Admin Systems (functions.php - Satır 104-125)

```
43. inc/admin-panel.php                     # Premium admin panel
44. components/share-buttons.php            # Paylaşım butonları
45. inc/seo-handler.php                     # SEO handler
46. inc/share-image-generator.php           # Paylaşım görseli oluşturucu
47. inc/share-tracking-handler.php          # Paylaşım takibi
48. inc/email-verification.php              # Email doğrulama
49. inc/firebase-config.php                 # Firebase konfigürasyonu
50. inc/firebase-verification.php           # Firebase doğrulama
51. inc/quest-system.php                    # Görev sistemi
52. inc/tasks-database.php                  # Görevler veritabanı
53. inc/tasks-system.php                    # Görevler sistemi (ÖNEMLİ: İlk yüklenmeli)
54. inc/tasks-progress.php                  # Görev ilerlemesi
55. inc/tasks-claim-atomic.php              # Görev ödül alma (atomic)
56. inc/tasks-migration.php                 # Görev migrasyonu
57. inc/tasks-handler.php                   # Görev handler'ları
58. inc/tasks-admin.php                     # Görevler admin
59. inc/items-admin.php                     # Eşyalar admin
60. inc/content-management.php             # İçerik yönetimi
61. inc/content-admin.php                   # İçerik admin
62. inc/messages-admin.php                  # Mesajlar admin
63. inc/messages-localize.php               # Mesajlar lokalizasyonu
64. inc/settings-admin.php                  # Ayarlar admin
65. social-share.php                        # Sosyal paylaşım
```

### 1.3 Devre Dışı Sistemler (functions.php - Satır 92-101)

**NOT:** Bu dosyalar şu anda devre dışı bırakılmıştır (critical error nedeniyle):

```
❌ inc/gift-exchange-system.php            # Mesajlaşma tabanlı hediyeleşme
❌ inc/gift-exchange-handlers.php          # Gift exchange AJAX handler'ları
❌ components/gift-exchange-panel.php      # Gift exchange panel component
❌ components/trade-report-modal.php      # Trade report modal
❌ inc/trade-ping-system.php               # Trade ping sistemi (hook'lar devre dışı)
❌ inc/trade-ping-handlers.php             # Trade ping handler'ları
❌ inc/trade-report-system.php             # Trade report sistemi (hook'lar devre dışı)
❌ inc/trade-report-handlers.php           # Trade report handler'ları
❌ inc/presence-admin.php                  # Presence admin ayarları
```

---

## 2. Fonksiyon Bağımlılıkları

### 2.1 Core Functions (Bağımlılık Yok)

**inc/items-config.php:**
- `hdh_get_items_config()` - Eşya konfigürasyonu (diğer sistemler tarafından kullanılır)

**inc/trade-offers.php:**
- `hdh_get_completed_gift_count($user_id)` - Tamamlanan hediye sayısı
- `hdh_get_total_completed_exchanges()` - Toplam tamamlanan takas

**inc/user-state-system.php:**
- `hdh_get_user_state($user_id)` - Kullanıcı durumu (level, bilet)
- `hdh_update_user_level($user_id, $new_level)` - Seviye güncelleme
- `hdh_add_bilet($user_id, $amount)` - Bilet ekleme

### 2.2 Trade Session System

**inc/trade-session.php:**
- `hdh_create_trade_session_table()` - Tablo oluşturma (after_switch_theme hook)
- `hdh_get_trade_session($session_id, $listing_id, $user_id)` - Oturum alma
- `hdh_create_trade_session($listing_id, $owner_user_id, $starter_user_id)` - Oturum oluşturma
- `hdh_complete_trade_session($session_id)` - Oturum tamamlama
- `hdh_increment_completed_gift_count($user_id)` - Tamamlanan hediye sayısını artırma

**Bağımlılıklar:**
- `hdh_log_event()` (inc/event-system.php) - Opsiyonel
- `hdh_increment_completed_gift_count()` (inc/trade-session.php) - Kendi içinde tanımlı

**inc/trade-session-handlers.php:**
- AJAX handler'ları (wp_ajax_*)
- Bağımlılıklar: `hdh_get_trade_session()`, `hdh_create_trade_session()`, vb.

### 2.3 Presence System

**inc/presence-system.php:**
- `hdh_update_user_presence($user_id)` - Presence güncelleme (throttled: 30s)
- `hdh_get_user_presence($user_id)` - Presence verisi
- `hdh_get_active_users_count($threshold_seconds)` - Aktif kullanıcı sayısı
- `hdh_get_presence_bucket($user_id)` - Bucket hesaplama
- `hdh_format_presence_label($bucket, $timestamp)` - Label formatlama

**Bağımlılıklar:**
- `hdh_get_user_state()` (inc/user-state-system.php) - Opsiyonel
- `hdh_log_event()` (inc/event-system.php) - Opsiyonel

**Hook'lar:**
- WordPress Heartbeat API (her 15-60 saniyede bir)
- `hdh_event_logged` hook (event-system.php'den)

### 2.4 Chat System

**inc/chat-system.php:**
- `hdh_create_chat_message($user_id, $message)` - Mesaj oluşturma
- `hdh_get_chat_messages($limit, $offset)` - Mesajları alma
- `hdh_get_active_users_count()` - Aktif kullanıcı sayısı

**Bağımlılıklar:**
- `hdh_get_active_users_count()` (inc/presence-system.php) - Opsiyonel
- Fallback: `user_meta` için `hdh_last_active` kullanır

**inc/chat-moderation.php:**
- `hdh_moderate_chat_message($message)` - Mesaj moderasyonu
- `hdh_check_profanity($text)` - Küfür kontrolü
- `hdh_check_links($text)` - Link kontrolü
- `hdh_check_phone($text)` - Telefon kontrolü
- `hdh_check_email($text)` - Email kontrolü

**inc/chat-handlers.php:**
- `hdh_ajax_send_chat_message()` - Mesaj gönderme AJAX
- `hdh_ajax_get_chat_messages()` - Mesajları alma AJAX

**inc/chat-admin.php:**
- `hdh_render_chat_admin_page()` - Chat admin sayfası
- `hdh_render_chat_settings_tab()` - Ayarlar tab'ı
- `hdh_render_chat_moderation_tab()` - Moderation tab'ı
- `hdh_render_trade_reports_tab()` - Trade reports tab'ı (function_exists kontrolü var)
- `hdh_handle_trade_report_action()` - Trade report aksiyonları (function_exists kontrolü var)

**Bağımlılıklar:**
- `hdh_get_trade_reports()` (inc/trade-report-system.php) - Devre dışı, function_exists kontrolü var
- `hdh_update_trade_report_status()` (inc/trade-report-system.php) - Devre dışı, function_exists kontrolü var

### 2.5 Trade Request System

**inc/trade-request-system.php:**
- `hdh_create_trade_request($listing_id, $requester_user_id)` - İstek oluşturma
- `hdh_accept_trade_request($request_id, $owner_user_id)` - İstek kabul etme
- `hdh_reject_trade_request($request_id, $owner_user_id)` - İstek reddetme
- `hdh_get_trade_request($request_id)` - İstek alma
- `hdh_get_pending_requests_for_owner($owner_user_id)` - Bekleyen istekler

**Bağımlılıklar:**
- `hdh_create_trade_session()` (inc/trade-session.php) - Gerekli
- `hdh_create_notification()` (inc/notification-system.php) - Opsiyonel
- `hdh_log_event()` (inc/event-system.php) - Opsiyonel

**inc/trade-request-handlers.php:**
- `hdh_ajax_create_trade_request()` - İstek oluşturma AJAX
- `hdh_ajax_accept_trade_request()` - İstek kabul etme AJAX
- `hdh_ajax_reject_trade_request()` - İstek reddetme AJAX

### 2.6 Notification System

**inc/notification-system.php:**
- `hdh_create_notification($user_id, $type, $title, $message, $link_url)` - Bildirim oluşturma
- `hdh_get_user_notifications($user_id, $unread_only, $limit)` - Bildirimleri alma
- `hdh_mark_notification_read($notification_id, $user_id)` - Okundu işaretleme
- `hdh_get_unread_count($user_id)` - Okunmamış sayısı

**Bağımlılıklar:**
- Yok (standalone sistem)

### 2.7 Tasks System

**inc/tasks-system.php:**
- `hdh_get_one_time_tasks_config()` - Tek seferlik görevler
- `hdh_get_daily_tasks_config()` - Günlük görevler
- `hdh_get_weekly_tasks_config()` - Haftalık görevler
- `hdh_get_task_config($task_id)` - Görev konfigürasyonu

**ÖNEMLİ:** Bu dosya diğer tasks dosyalarından ÖNCE yüklenmeli (functions.php satır 114)

**inc/tasks-progress.php:**
- `hdh_get_task_progress($user_id, $task_id, $period_key)` - Görev ilerlemesi
- `hdh_get_claimable_count($user_id, $task_id, $period_key)` - Alınabilir sayı

**Bağımlılıklar:**
- `hdh_get_one_time_tasks_config()` (inc/tasks-system.php) - Gerekli
- `hdh_get_daily_tasks_config()` (inc/tasks-system.php) - Gerekli

**inc/tasks-handler.php:**
- `hdh_ajax_claim_task_reward()` - Görev ödülü alma AJAX

**Bağımlılıklar:**
- `hdh_get_task_progress()` (inc/tasks-progress.php)
- `hdh_claim_task_reward_atomic()` (inc/tasks-claim-atomic.php)

### 2.8 Admin Panel

**inc/admin-panel.php:**
- `hdh_add_premium_admin_menu()` - Admin menü yapısı
- `hdh_render_dashboard_page()` - Dashboard sayfası
- `hdh_render_presence_admin_page()` - Presence admin (DEVRE DIŞI - function_exists kontrolü yok)

**Sorun:** `hdh_render_presence_admin_page()` fonksiyonu çağrılıyor ama `inc/presence-admin.php` devre dışı.

**Çözüm:** Presence admin submenu'su yorum satırına alındı (satır 102-109).

---

## 3. Veritabanı Tabloları

### 3.1 Aktif Tablolar

**wp_hdh_trade_sessions:**
- Oluşturma: `hdh_create_trade_session_table()` (inc/trade-session.php)
- Hook: `after_switch_theme`, `admin_init`
- Kullanım: 5-step takas sistemi

**wp_hdh_trade_timeline_events:**
- Oluşturma: `hdh_create_trade_timeline_events_table()` (inc/trade-session.php)
- Hook: `after_switch_theme`, `admin_init`
- Kullanım: 3-step takas sistemi timeline

**wp_hdh_user_presence:**
- Oluşturma: `hdh_create_user_presence_table()` (inc/presence-system.php)
- Hook: `after_switch_theme`, `admin_init`
- Kullanım: Kullanıcı varlık takibi

**wp_hdh_trade_requests:**
- Oluşturma: `hdh_create_trade_requests_table()` (inc/trade-request-system.php)
- Hook: `after_switch_theme`, `admin_init`
- Kullanım: Takas istekleri

**wp_hdh_notifications:**
- Oluşturma: `hdh_create_notifications_table()` (inc/notification-system.php)
- Hook: `after_switch_theme`, `admin_init`
- Kullanım: Site bildirimleri

**wp_hdh_chat_messages:**
- Oluşturma: `hdh_create_chat_messages_table()` (inc/chat-system.php)
- Hook: `after_switch_theme`, `admin_init`
- Kullanım: Lobby chat mesajları

**wp_hdh_chat_warnings:**
- Oluşturma: `hdh_create_chat_warnings_table()` (inc/chat-moderation.php)
- Hook: `after_switch_theme`, `admin_init`
- Kullanım: Chat moderasyon uyarıları

**wp_hdh_task_progress:**
- Oluşturma: `hdh_create_task_progress_table()` (inc/tasks-database.php)
- Hook: `after_switch_theme`, `admin_init`
- Kullanım: Görev ilerlemesi

### 3.2 Devre Dışı Tablolar

**wp_hdh_trade_pings:**
- Oluşturma: `hdh_create_trade_pings_table()` (inc/trade-ping-system.php)
- Hook: DEVRE DIŞI (yorum satırına alındı)
- Durum: Sistem devre dışı

**wp_hdh_trade_reports:**
- Oluşturma: `hdh_create_trade_reports_table()` (inc/trade-report-system.php)
- Hook: DEVRE DIŞI (yorum satırına alındı)
- Durum: Sistem devre dışı

**wp_hdh_gift_exchanges:**
- Oluşturma: `hdh_create_gift_exchanges_table()` (inc/gift-exchange-system.php)
- Hook: Yok (lazy loading kullanılıyor)
- Durum: Sistem devre dışı

**wp_hdh_gift_messages:**
- Oluşturma: `hdh_create_gift_messages_table()` (inc/gift-exchange-system.php)
- Hook: Yok (lazy loading kullanılıyor)
- Durum: Sistem devre dışı

---

## 4. AJAX Handler'ları

### 4.1 Aktif Handler'lar

**inc/trade-session-handlers.php:**
- `wp_ajax_hdh_get_trade_session` → `hdh_ajax_get_trade_session()`
- `wp_ajax_hdh_complete_trade_step` → `hdh_ajax_complete_trade_step()`
- `wp_ajax_hdh_create_trade_dispute` → `hdh_ajax_create_trade_dispute()`

**inc/trade-request-handlers.php:**
- `wp_ajax_hdh_create_trade_request` → `hdh_ajax_create_trade_request()`
- `wp_ajax_hdh_accept_trade_request` → `hdh_ajax_accept_trade_request()`
- `wp_ajax_hdh_reject_trade_request` → `hdh_ajax_reject_trade_request()`

**inc/chat-handlers.php:**
- `wp_ajax_hdh_send_chat_message` → `hdh_ajax_send_chat_message()`
- `wp_ajax_hdh_get_chat_messages` → `hdh_ajax_get_chat_messages()`

**inc/tasks-handler.php:**
- `wp_ajax_hdh_claim_task_reward` → `hdh_ajax_claim_task_reward()`

### 4.2 Devre Dışı Handler'lar

**inc/trade-ping-handlers.php:** (Sistem devre dışı)
- `wp_ajax_hdh_send_trade_ping` → `hdh_ajax_send_trade_ping()`
- `wp_ajax_hdh_respond_to_ping` → `hdh_ajax_respond_to_ping()`

**inc/trade-report-handlers.php:** (Sistem devre dışı)
- `wp_ajax_hdh_create_trade_report` → `hdh_ajax_create_trade_report()`

**inc/gift-exchange-handlers.php:** (Sistem devre dışı)
- `wp_ajax_hdh_start_gift_exchange` → `hdh_ajax_start_gift_exchange()`
- `wp_ajax_hdh_send_gift_message` → `hdh_ajax_send_gift_message()`
- `wp_ajax_hdh_get_gift_messages` → `hdh_ajax_get_gift_messages()`
- `wp_ajax_hdh_get_gift_exchanges` → `hdh_ajax_get_gift_exchanges()`
- `wp_ajax_hdh_complete_gift_exchange` → `hdh_ajax_complete_gift_exchange()`
- `wp_ajax_hdh_report_gift_exchange` → `hdh_ajax_report_gift_exchange()`
- `wp_ajax_hdh_mark_messages_read` → `hdh_ajax_mark_messages_read()`

---

## 5. Admin Panel Yapısı

### 5.1 Ana Menü (inc/admin-panel.php)

**HDH Dashboard:**
- Dashboard (ana sayfa)
- Pre-Login Experience
- Post-Login Experience
- Global Design
- Content Library
- Components & Presets
- Çekiliş
- ~~Presence~~ (DEVRE DIŞI - yorum satırına alındı)
- Chat
- Advanced
- Logs

**Submenu'lar:**
- `hdh-dashboard` → `hdh_render_dashboard_page()`
- `hdh-pre-login` → `hdh_render_experience_page()`
- `hdh-post-login` → `hdh_render_experience_page()`
- `hdh-global-design` → `hdh_render_global_design_page()`
- `hdh-content-library` → `hdh_render_content_library_page()`
- `hdh-components` → `hdh_render_components_page()`
- `hdh-lottery` → `hdh_render_lottery_admin_page()`
- `hdh-presence` → `hdh_render_presence_admin_page()` (DEVRE DIŞI)
- `hdh-chat` → `hdh_render_chat_admin_page()`
- `hdh-advanced` → `hdh_render_advanced_page()`
- `hdh-logs` → `hdh_render_logs_page()`

### 5.2 Chat Admin (inc/chat-admin.php)

**Tab'lar:**
- Settings → `hdh_render_chat_settings_tab()`
- Moderation → `hdh_render_chat_moderation_tab()`
- Trade Reports → `hdh_render_trade_reports_tab()` (function_exists kontrolü var)

**Sorun:** Trade Reports tab'ı `hdh_get_trade_reports()` fonksiyonunu çağırıyor ama sistem devre dışı.

**Çözüm:** `function_exists()` kontrolleri eklendi (satır 416, 420, 525).

---

## 6. Component Yapısı

### 6.1 Aktif Component'ler

**components/trade-card.php:**
- `hdh_render_trade_card($post, $args)` - Takas kartı render
- Bağımlılıklar: `hdh_get_presence_bucket()` (inc/presence-system.php)

**components/lobby-chat.php:**
- `hdh_render_lobby_chat()` - Lobby chat render
- Bağımlılıklar: `hdh_get_active_users_count()` (inc/presence-system.php)

**components/tasks-panel.php:**
- `hdh_render_tasks_panel($user_id)` - Görevler paneli
- Bağımlılıklar: `hdh_get_task_progress()` (inc/tasks-progress.php)

**components/quest-panel.php:**
- `hdh_render_quest_panel($user_id)` - Görev paneli
- Bağımlılıklar: `hdh_get_user_state()` (inc/user-state-system.php)

**components/user-badge.php:**
- `hdh_render_user_badge($user_id)` - Kullanıcı rozeti
- Bağımlılıklar: `hdh_get_user_state()` (inc/user-state-system.php)

### 6.2 Devre Dışı Component'ler

**components/gift-exchange-panel.php:**
- `hdh_render_gift_exchange_panel($user_id)` - Gift exchange paneli
- Durum: Erken return ile devre dışı bırakıldı
- Bağımlılıklar: `hdh_ensure_gift_tables_exist()`, `hdh_get_total_unread_count()` (devre dışı)

**components/trade-report-modal.php:**
- `hdh_render_trade_report_modal($session_id)` - Trade report modal
- Durum: Sistem devre dışı

---

## 7. Potansiyel Sorunlar ve Çözümler

### 7.1 Tespit Edilen Sorunlar

**Sorun 1: Trade Roadmap Referansları**
- **Dosya:** `functions.php` (satır 46), `single-hayday_trade.php` (satır 184)
- **Sorun:** Silinen `trade-roadmap.php` dosyasına referanslar vardı
- **Çözüm:** Referanslar kaldırıldı, `function_exists()` kontrolleri eklendi
- **Durum:** ✅ Düzeltildi

**Sorun 2: Chat Admin Trade Reports Tab**
- **Dosya:** `inc/chat-admin.php` (satır 49, 408, 513)
- **Sorun:** `hdh_get_trade_reports()` ve `hdh_update_trade_report_status()` fonksiyonları devre dışı sistemde
- **Çözüm:** `function_exists()` kontrolleri eklendi, tab sadece sistem aktifse görünüyor
- **Durum:** ✅ Düzeltildi

**Sorun 3: Admin Panel Presence Page**
- **Dosya:** `inc/admin-panel.php` (satır 102-109)
- **Sorun:** `hdh_render_presence_admin_page()` fonksiyonu çağrılıyor ama `inc/presence-admin.php` devre dışı
- **Çözüm:** Presence admin submenu'su yorum satırına alındı
- **Durum:** ✅ Düzeltildi

**Sorun 4: Trade Ping/Report System Hook'ları**
- **Dosya:** `inc/trade-ping-system.php` (satır 48, 53), `inc/trade-report-system.php` (satır 51, 56)
- **Sorun:** `add_action` hook'ları kayıt oluyordu ama sistem devre dışı
- **Çözüm:** Hook'lar yorum satırına alındı
- **Durum:** ✅ Düzeltildi

**Sorun 5: Gift Exchange Panel**
- **Dosya:** `components/gift-exchange-panel.php` (satır 7)
- **Sorun:** Fonksiyon devre dışı sistemlere bağımlı fonksiyonları çağırıyordu
- **Çözüm:** Fonksiyonun başına erken return eklendi
- **Durum:** ✅ Düzeltildi

**Sorun 6: Gift Exchange Script Enqueue'ları**
- **Dosya:** `functions.php` (satır 227-248, 212-224)
- **Sorun:** Devre dışı sistem için script'ler enqueue ediliyordu
- **Çözüm:** Enqueue'lar yorum satırına alındı
- **Durum:** ✅ Düzeltildi

### 7.2 Potansiyel Sorunlar

**Potansiyel Sorun 1: Function Exists Kontrolleri Eksik**
- **Risk:** Bazı fonksiyonlar `function_exists()` kontrolü olmadan çağrılıyor olabilir
- **Öneri:** Tüm opsiyonel fonksiyon çağrılarında `function_exists()` kontrolü yapılmalı

**Potansiyel Sorun 2: Hook Kayıtları**
- **Risk:** Devre dışı sistemlerdeki hook'lar hala kayıt olabilir
- **Öneri:** Devre dışı sistemlerdeki tüm `add_action` ve `add_filter` hook'ları kontrol edilmeli

**Potansiyel Sorun 3: Database Table Creation**
- **Risk:** Devre dışı sistemlerin tabloları oluşturulmaya çalışılabilir
- **Öneri:** Tablo oluşturma hook'ları devre dışı sistemlerde yorum satırına alınmalı

---

## 8. Devre Dışı Sistemler

### 8.1 Gift Exchange System (Mesajlaşma Tabanlı)

**Dosyalar:**
- `inc/gift-exchange-system.php` - Core sistem
- `inc/gift-exchange-handlers.php` - AJAX handler'ları
- `components/gift-exchange-panel.php` - Panel component
- `assets/js/gift-exchange-panel.js` - Frontend JS
- `assets/js/gift-exchange-button.js` - Button handler JS
- `assets/css/gift-exchange-panel.css` - Styles

**Durum:** Tamamen devre dışı (functions.php satır 92-94)

**Neden Devre Dışı:** Critical error nedeniyle geçici olarak devre dışı bırakıldı

**Yeniden Aktifleştirme:**
1. `functions.php`'de require_once'ları aktif et
2. `footer.php`'de `hdh_render_gift_exchange_panel()` çağrısını aktif et
3. `functions.php`'de script enqueue'larını aktif et
4. `components/gift-exchange-panel.php`'deki erken return'ü kaldır

### 8.2 Trade Ping System

**Dosyalar:**
- `inc/trade-ping-system.php` - Core sistem (hook'lar devre dışı)
- `inc/trade-ping-handlers.php` - AJAX handler'ları

**Durum:** Hook'lar devre dışı (functions.php satır 97-98)

**Neden Devre Dışı:** Critical error nedeniyle geçici olarak devre dışı bırakıldı

**Yeniden Aktifleştirme:**
1. `functions.php`'de require_once'ları aktif et
2. `inc/trade-ping-system.php`'deki hook'ları aktif et (satır 48, 53)

### 8.3 Trade Report System

**Dosyalar:**
- `inc/trade-report-system.php` - Core sistem (hook'lar devre dışı)
- `inc/trade-report-handlers.php` - AJAX handler'ları
- `components/trade-report-modal.php` - Modal component

**Durum:** Hook'lar devre dışı (functions.php satır 99-100)

**Neden Devre Dışı:** Critical error nedeniyle geçici olarak devre dışı bırakıldı

**Yeniden Aktifleştirme:**
1. `functions.php`'de require_once'ları aktif et
2. `inc/trade-report-system.php`'deki hook'ları aktif et (satır 51, 56)
3. `inc/chat-admin.php`'deki `function_exists()` kontrolleri zaten var

### 8.4 Presence Admin

**Dosyalar:**
- `inc/presence-admin.php` - Admin ayarları

**Durum:** Devre dışı (functions.php satır 101, admin-panel.php satır 102-109)

**Neden Devre Dışı:** Critical error nedeniyle geçici olarak devre dışı bırakıldı

**Yeniden Aktifleştirme:**
1. `functions.php`'de require_once'u aktif et
2. `inc/admin-panel.php`'deki submenu'yu aktif et (satır 102-109)

---

## 9. Önemli Notlar

### 9.1 Yükleme Sırası Kritik Dosyalar

1. **inc/tasks-system.php** - Diğer tasks dosyalarından ÖNCE yüklenmeli (satır 114)
2. **inc/items-config.php** - Diğer sistemler tarafından kullanılır (satır 43)
3. **inc/trade-session.php** - Trade request sistem tarafından kullanılır (satır 66)

### 9.2 Function Exists Kontrolleri

Aşağıdaki dosyalarda `function_exists()` kontrolleri yapılıyor:
- `inc/chat-admin.php` - Trade reports fonksiyonları için
- `components/gift-exchange-panel.php` - Gift exchange fonksiyonları için
- `inc/trade-session.php` - Opsiyonel fonksiyonlar için
- `inc/presence-system.php` - Opsiyonel fonksiyonlar için

### 9.3 Hook Öncelikleri

- `admin_menu` hook'u: `hdh_add_premium_admin_menu()` priority 5 (satır 141)
- `admin_menu` hook'u: `hdh_add_trade_session_admin_menu()` priority 20 (inc/trade-session-admin.php satır 22)

---

## 10. Güncelleme Notları

**2024-12-19:**
- Sistem haritası oluşturuldu
- Tüm bağımlılıklar belgelendi
- Devre dışı sistemler işaretlendi
- Potansiyel sorunlar tespit edildi ve çözüldü

---

## 11. Benzer Site Oluşturma Rehberi

### 11.1 Temel Yapı

1. **Core Systems:**
   - Items config (eşya konfigürasyonu)
   - Trade offers (CPT)
   - Trade session (takas oturumu)
   - User state (kullanıcı durumu)

2. **Presence System:**
   - User presence tracking
   - Presence-based sorting
   - Presence labels

3. **Chat System:**
   - Lobby chat
   - Moderation
   - Rate limiting

4. **Tasks System:**
   - Task configuration
   - Task progress
   - Task rewards

### 11.2 Yükleme Sırası

1. Core config dosyaları
2. CPT'ler
3. Core sistemler
4. Handler'lar
5. Component'ler
6. Admin panel

### 11.3 Güvenlik

- Tüm AJAX handler'larında nonce kontrolü
- Capability kontrolü (admin fonksiyonları için)
- Input sanitization
- SQL injection koruması
- XSS koruması

---

**Son Güncelleme:** 2024-12-19  
**Versiyon:** 1.0.0

---

## 12. Tespit Edilen ve Düzeltilen Sorunlar (Güncel)

### 12.1 2024-12-19 Tespitleri

**✅ Sorun 1: Trade Roadmap Referansları**
- **Dosya:** `functions.php` (satır 46), `single-hayday_trade.php` (satır 184)
- **Durum:** Düzeltildi
- **Açıklama:** Silinen dosyaya referanslar kaldırıldı

**✅ Sorun 2: Chat Admin Trade Reports**
- **Dosya:** `inc/chat-admin.php`
- **Durum:** Düzeltildi
- **Açıklama:** function_exists kontrolleri eklendi

**✅ Sorun 3: Admin Panel Presence Page**
- **Dosya:** `inc/admin-panel.php`
- **Durum:** Düzeltildi
- **Açıklama:** Submenu yorum satırına alındı

**✅ Sorun 4: Trade Ping/Report Hook'ları**
- **Dosya:** `inc/trade-ping-system.php`, `inc/trade-report-system.php`
- **Durum:** Düzeltildi
- **Açıklama:** Hook'lar yorum satırına alındı

**✅ Sorun 5: Gift Exchange Panel**
- **Dosya:** `components/gift-exchange-panel.php`
- **Durum:** Düzeltildi
- **Açıklama:** Erken return eklendi

**✅ Sorun 6: Gift Exchange Script Enqueue'ları**
- **Dosya:** `functions.php`
- **Durum:** Düzeltildi
- **Açıklama:** Enqueue'lar yorum satırına alındı

### 12.2 Kontrol Edilmesi Gerekenler

**⚠️ Potansiyel Sorun 1: Footer.php Gift Exchange Panel**
- **Dosya:** `footer.php` (satır 79-81)
- **Durum:** Kontrol edildi - Yorum satırında, güvenli
- **Açıklama:** `hdh_render_gift_exchange_panel()` çağrısı yorum satırında

**⚠️ Potansiyel Sorun 2: Header.php Completed Gift Count**
- **Dosya:** `header.php` (satır 25)
- **Durum:** Güvenli - function_exists kontrolü var
- **Açıklama:** `hdh_get_completed_gift_count()` fonksiyonu `inc/trade-offers.php`'de tanımlı ve aktif

**✅ Kontrol 3: Tüm Devre Dışı Sistem Referansları**
- **Durum:** Kontrol edildi
- **Sonuç:** Tüm referanslar güvenli hale getirildi

**🔧 Düzeltme 4: Duplicate Function Name Conflict (2024-12-19)**
- **Sorun:** `hdh_get_unread_count` fonksiyonu iki farklı dosyada tanımlı:
  - `inc/notification-system.php` line 206: `hdh_get_unread_count($user_id)` - 1 parametre
  - `inc/gift-exchange-system.php` line 718: `hdh_get_unread_count($exchange_id, $user_id)` - 2 parametre
- **Kök Sebep:** Fonksiyon adı çakışması. `inc/gift-exchange-system.php` disabled olsa bile, eğer aktif edilirse "Cannot redeclare function" hatası verir.
- **Çözüm:** `inc/gift-exchange-system.php` içindeki fonksiyon adı `hdh_get_gift_exchange_unread_count` olarak değiştirildi ve `function_exists` kontrolü eklendi.
- **Dosyalar:** `inc/gift-exchange-system.php` (line 718, 386)

**🔧 Düzeltme 5: Syntax Error - Fazladan Kapanış Parantezi (2024-12-19)**
- **Sorun:** `functions.php` line 397-415 arası yanlış girintilenmiş kod ve line 415'te fazladan `}` parantezi.
- **Kök Sebep:** Line 396'da `if (is_singular('hayday_trade'))` bloğu kapanıyor, ama line 397-414 arası kodlar `if` bloğunun dışında ve line 415'te fazladan `}` var. Bu "Parse error: syntax error, unexpected '}'" hatasına sebep olur.
- **Çözüm:** Line 397-414 arası kodlar düzgün bir `if` bloğu içine alındı ve fazladan `}` kaldırıldı.
- **Dosyalar:** `functions.php` (line 397-415)

