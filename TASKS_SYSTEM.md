# HDH Görev & Ödül Sistemi Dokümantasyonu

## Genel Bakış

HDH görev sistemi, kullanıcıların belirli eylemleri tamamlayarak ödül kazanabilecekleri bir sistemdir. Sistem **claim-only** prensibiyle çalışır: Ödüller sadece kullanıcı "Ödülünü Al" butonuna bastığında verilir. Otomatik ödül verme yoktur.

## Mimari

### Veri Modeli

#### 1. `wp_hdh_task_progress` Tablosu
Progress tracking için kullanılır. Her kullanıcı/görev/periyot kombinasyonu için tek bir kayıt tutar.

**Alanlar:**
- `id` (PK, auto_increment)
- `user_id` (bigint, index)
- `task_id` (varchar, index)
- `period_key` (varchar: '2025-12-19' günlük, 'lifetime' tek seferlik)
- `completed_count` (int) → Kullanıcının kaç kez görevi tamamladığı
- `claimed_count` (int) → Kaç ödül claim'lediği
- `updated_at` (datetime)
- **UNIQUE KEY** `(user_id, task_id, period_key)`

#### 2. `wp_hdh_reward_ledger` Tablosu
Reward claim'lerini immutable olarak saklar. Her claim için ayrı bir kayıt tutar.

**Alanlar:**
- `ledger_id` (PK, auto_increment)
- `user_id` (bigint, index)
- `task_id` (varchar, index)
- `period_key` (varchar)
- `claim_index` (int: 1..N) → Tekrar eden görevlerde her claim'i tekilleştirir
- `reward_type` (enum: 'bilet', 'xp')
- `reward_amount` (int)
- `status` (enum: 'applied', 'reverted', default 'applied')
- `created_at` (datetime)
- **UNIQUE KEY** `(user_id, task_id, period_key, claim_index)` → Çift ödül imkansız

#### 3. WordPress Options
Görev tanımları WordPress options'da saklanır:
- `hdh_one_time_tasks` → Tek seferlik görevler
- `hdh_daily_tasks` → Günlük görevler

## Claim Flow

### Atomic Claim İşlemi

1. **Auth & Validation:** Kullanıcı kimlik doğrulaması ve görev kontrolü
2. **START TRANSACTION:** MySQL transaction başlatılır
3. **SELECT ... FOR UPDATE:** Progress kaydı row-level lock ile kilitlenir
4. **Claimable Hesaplama:** `claimable = completed_count - claimed_count`
5. **Kontrol:** Eğer `claimable <= 0` → ROLLBACK + hata döner
6. **claimed_count Artışı:** `claimed_count += 1` (UPDATE)
7. **Ledger Kaydı:** `claim_index = claimed_count` ile ledger'a INSERT
8. **Ödül Uygulama:** `hdh_add_bilet()` / `hdh_add_xp()` çağrısı
9. **COMMIT:** Transaction commit edilir
10. **Event Logging:** `wp_hdh_events` tablosuna log yazılır

### Race Condition Koruması

- **Row-level locking:** `SELECT ... FOR UPDATE` ile aynı anda iki istek gelse bile sadece biri işlem yapabilir
- **Unique constraint:** Database seviyesinde çift ödül imkansız
- **Transaction:** Herhangi bir hata durumunda rollback garantisi

## Progress Increment Sistemi

### Fonksiyon: `hdh_increment_task_progress()`

Görev tamamlandığında (ilan verildi, hediyeleşme tamamlandı, email doğrulandı vb.) bu fonksiyon çağrılır.

**Önemli:** Bu fonksiyon **SADECE** `completed_count`'u artırır. **ÖDÜL VERMEZ.**

**Kullanım:**
```php
hdh_increment_task_progress($user_id, 'create_listings', date('Y-m-d')); // Daily task
hdh_increment_task_progress($user_id, 'verify_email', 'lifetime'); // One-time task
```

### Hook Entegrasyonları

- **Email Verify:** `inc/user-state-system.php` → `hdh_verify_email()` içinde
- **Listing Created:** `inc/tasks-system.php` → `hdh_track_listing_creation()` içinde
- **Exchange Completed:** `inc/tasks-system.php` → `hdh_track_exchange_completion()` içinde

## Otomatik Ödüllerin Kaldırılması

### Değişiklik Raporu

#### 1. Email Verification (`inc/user-state-system.php:400`)
**Önceki:** Email doğrulandığında otomatik +1 bilet veriliyordu
**Şimdi:** Sadece `hdh_increment_task_progress()` çağrılıyor, ödül yok

#### 2. Listing Creation (`inc/create-trade-handler.php:210-230`)
**Önceki:** İlan oluşturulduğunda otomatik +2 bilet ve ilk ilan için +1 level veriliyordu
**Şimdi:** Tüm otomatik ödüller kaldırıldı, sadece `do_action('hdh_listing_created')` çağrılıyor

#### 3. Quest System (`inc/quest-system.php`)
Quest sistemi kullanılmıyor gibi görünüyor, ancak yine de `hdh_complete_quest()` fonksiyonu otomatik ödül veriyor. Bu fonksiyon şu anda kullanılmıyor.

## API Sözleşmesi

### GET Tasks
**Endpoint:** `wp_ajax_hdh_get_tasks`

**Response:**
```json
{
  "success": true,
  "data": {
    "one_time_tasks": [
      {
        "id": "verify_email",
        "title": "Doğrulama",
        "description": "E-posta adresinizi doğrulayın",
        "reward_bilet": 1,
        "reward_level": 1,
        "progress": 1,
        "max_progress": 1,
        "completed": true,
        "claimed": false,
        "claimed_count": 0,
        "claimable_count": 1,
        "can_claim": true,
        "cta_state": "claim"
      }
    ],
    "daily_tasks": [...]
  }
}
```

### POST Claim Reward
**Endpoint:** `wp_ajax_hdh_claim_task_reward`

**Request:**
- `task_id` (string)
- `is_daily` (boolean)
- `nonce` (string)

**Response:**
```json
{
  "success": true,
  "data": {
    "bilet": 1,
    "level": 1,
    "new_bilet": 5,
    "new_level": 2,
    "claimable_remaining": 0
  }
}
```

**Hata Kodları:**
- `401` - Auth yok
- `409` - Claimable yok (already_claimed)
- `400` - Task pasif/hatalı

## Migration Rehberi

### Migration Script

Migration script admin panelinden çalıştırılır:
1. Admin panel → Görevler → "Migrasyonu Çalıştır" butonu
2. Test modu ile önce simüle edin
3. Test modu kapalı olarak gerçek migration'ı çalıştırın

### Migration Stratejisi

1. Tüm kullanıcıları döngüye al
2. Her kullanıcı için mevcut user_meta'ları oku:
   - `hdh_task_progress_{task_id}` → `completed_count`
   - `hdh_task_claimed_{task_id}` → `claimed_count` (one-time için boolean→int, daily için sayı)
3. `wp_hdh_task_progress` tablosuna INSERT/UPDATE
4. Eski user_meta'ları silme (backup olarak kalabilir)

### Backward Compatibility

- Eski user_meta verileri migration'a kadar korunur
- Yeni sistem eski sistemle paralel çalışabilir
- Migration sonrası eski sistem devre dışı kalır (yeni tablolar kullanılır)

## Test Senaryoları

### 1. Tek Seferlik Görev
- 1 completion → claim → ✅ Başarılı
- 2. claim → ❌ Reddedildi (already_claimed)

### 2. Daily 5 Görev
- 2 completion → 2 claim mümkün → ✅ Her biri başarılı
- 3. claim → ❌ Reddedildi (claimable yok)

### 3. Race Condition
- Aynı anda 2 claim isteği → ✅ Sadece 1 ödül verildi (atomic)

### 4. Admin Reward Değişikliği
- Admin reward_amount değiştirse bile eski claim'ler tekrar edemiyor → ✅ Unique constraint koruması

### 5. Badge
- claimable > 0 iken badge açık → ✅
- Claim sonrası badge kapanıyor → ✅

### 6. Otomatik Ödül Kontrolü
- Email verify artık otomatik ödül vermiyor → ✅
- Listing create artık otomatik ödül vermiyor → ✅

## Dosya Yapısı

### Yeni Dosyalar
- `inc/tasks-database.php` - Tablo oluşturma
- `inc/tasks-progress.php` - Progress increment fonksiyonları
- `inc/tasks-claim-atomic.php` - Atomic claim engine
- `inc/tasks-migration.php` - Migration script
- `TASKS_SYSTEM.md` - Bu dokümantasyon

### Güncellenen Dosyalar
- `inc/user-state-system.php` - Email verify otomatik ödülü kaldırıldı
- `inc/create-trade-handler.php` - Listing create otomatik ödülleri kaldırıldı
- `inc/tasks-system.php` - Claim engine wrapper + task list API güncellemesi
- `inc/tasks-handler.php` - AJAX handler güncellemesi
- `components/tasks-panel.php` - Badge hesaplaması güncellemesi
- `assets/js/tasks-panel.js` - UI güncellemesi
- `inc/tasks-admin.php` - Migration butonu eklendi
- `functions.php` - Yeni dosyalar include edildi

## Kritik Notlar

1. **Ödül Veren Tek Fonksiyon:** `hdh_claim_task_reward_atomic()` (wrapper: `hdh_claim_task_reward()`)
2. **Progress Artışı:** `hdh_increment_task_progress()` - ÖDÜL VERMEZ
3. **Transaction Safety:** Her claim işlemi transaction içinde, rollback garantisi var
4. **Unique Constraint:** Database seviyesinde çift ödül imkansız
5. **Event Logging:** Tüm claim'ler `wp_hdh_events` tablosuna da loglanır

## Sorun Giderme

### Claim başarısız oluyor
- Progress kaydı var mı kontrol edin
- `completed_count > claimed_count` mi kontrol edin
- Transaction loglarını kontrol edin

### Badge yanlış gösteriyor
- `claimable_count` hesaplamasını kontrol edin
- Frontend'te badge güncelleme fonksiyonunu kontrol edin

### Migration hataları
- Test modu ile önce simüle edin
- Eski user_meta verilerini kontrol edin
- Database tablolarının oluşturulduğundan emin olun

