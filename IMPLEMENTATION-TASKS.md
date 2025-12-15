# HDH Implementation Tasks by Phase

## Phase 1: KVKK & Security Hardening

### 1.1 Password Hashing Audit
- [ ] Verify all password operations use `wp_hash_password()` / `wp_check_password()`
- [ ] Audit `inc/registration-handler.php` for password handling
- [ ] Ensure no plaintext passwords in logs or database

### 1.2 CSRF Nonce Audit
- [ ] Review all AJAX handlers in `inc/ajax-handlers.php`
- [ ] Verify nonce verification in `inc/offers-handler.php`, `inc/tasks-handler.php`, `inc/lottery-handler.php`
- [ ] Add nonce to any missing AJAX endpoints

### 1.3 Cookie Consent System
- [ ] Create `inc/kvkk-compliance.php`
- [ ] Implement `hdh_show_cookie_banner()` component
- [ ] Create `hdh_save_cookie_consent()` handler (user_meta + localStorage)
- [ ] Add version tracking for policy changes
- [ ] Create cookie banner UI component

### 1.4 GDPR Export/Delete Endpoints
- [ ] Implement `hdh_gdpr_export()` - export all user data (events, trades, profile)
- [ ] Implement `hdh_gdpr_delete()` - anonymize user data
- [ ] Create admin UI for GDPR requests
- [ ] Add anonymization logic (name → "Deleted User", email → hash)

### 1.5 IP Hashing in Events
- [ ] Modify `hdh_log_event()` in `inc/event-system.php` to hash IPs
- [ ] Use `hdh_hash_ip()` before storing in database
- [ ] Keep raw IP in memory only (not persisted)

**Files to create:**
- `inc/kvkk-compliance.php`
- `inc/security-audit.php`
- `components/cookie-banner.php`

**Files to modify:**
- `inc/event-system.php` (IP hashing)

---

## Phase 2: Trust & Moderation Layer

### 2.1 Block System
- [ ] Create `hdh_block_user()` function (add to user_meta array)
- [ ] Create `hdh_unblock_user()` function
- [ ] Modify listing queries to exclude blocked users (server-side)
- [ ] Add client-side UI hiding for blocked users

### 2.2 Report System
- [ ] Register `hayday_report` CPT in `inc/moderation-system.php`
- [ ] Meta fields: `_hdh_reporter_id`, `_hdh_target_id`, `_hdh_report_type`, `_hdh_report_reason`, `_hdh_report_status`
- [ ] Create `hdh_create_report()` function
- [ ] Auto-flag logic: if user has 3+ reports, set `hdh_risk_score` += 20
- [ ] Admin review queue UI

### 2.3 Dispute System
- [ ] Register `hayday_dispute` CPT
- [ ] Meta fields: `_hdh_trade_id`, `_hdh_offer_id`, `_hdh_initiator_id`, `_hdh_dispute_status`, `_hdh_resolution`
- [ ] Create "Issue with exchange" button on completed trades
- [ ] Dispute creation flow (both parties can comment)
- [ ] Admin resolution interface
- [ ] Auto-update trust scores on resolution

### 2.4 Trust/Level Display
- [ ] Create `inc/trust-display.php` with tooltip generation
- [ ] Create `components/user-badge.php` component
- [ ] Implement `hdh_get_user_tooltip_data()` function
- [ ] Add level badge to all user displays (listings, comments, profile)
- [ ] Add trust stars display with tooltip

**Files to create:**
- `inc/moderation-system.php`
- `inc/trust-display.php`
- `components/user-badge.php`

**Files to modify:**
- `inc/trade-integrity.php` (block filtering)
- `single-hayday_trade.php` (dispute button)
- `components/trade-card.php` (level badge)

---

## Phase 3: Listing Lifecycle & SEO

### 3.1 Soft 404 Enhancements
- [ ] Enhance `hdh_get_alternative_trades()` to prioritize same wanted_item
- [ ] Update `single-hayday_trade.php` to show alternatives for completed/expired
- [ ] Ensure no actual 404 HTTP status (use 200 with contextual message)

### 3.2 Expired/Inactive Handling
- [ ] Verify cron job in `inc/trade-integrity.php` marks expired trades
- [ ] Add reactivation UI for owners
- [ ] Update listing queries to exclude expired (unless owner)

### 3.3 SEO Meta Tags
- [ ] Create `inc/seo-handler.php`
- [ ] Implement `hdh_output_listing_seo()` function
- [ ] Add canonical URL to `single-hayday_trade.php`
- [ ] Generate OG tags (title, description, image)
- [ ] Generate Twitter card tags
- [ ] Add to `wp_head` hook

### 3.4 Structured Data (Schema.org)
- [ ] Implement `hdh_generate_schema_offer()` function
- [ ] Output JSON-LD in `single-hayday_trade.php`
- [ ] Update availability based on trade status
- [ ] Include seller trust rating in schema

**Files to create:**
- `inc/seo-handler.php`

**Files to modify:**
- `single-hayday_trade.php` (SEO tags)
- `inc/trade-integrity.php` (alternatives enhancement)

---

## Phase 4: Social Sharing

### 4.1 OG Image Generation
- [ ] Create `inc/share-image-generator.php`
- [ ] Implement `hdh_generate_og_image()` - 1200x630px
- [ ] Design template: offered items ↔ wanted item + level badge + trust stars
- [ ] Cache logic: check if exists, generate if missing, 7-day cache
- [ ] Store in `wp-content/uploads/hdh-shares/{listing_id}-og.jpg`

### 4.2 Story Image Generation
- [ ] Implement `hdh_generate_story_image()` - 1080x1920px
- [ ] Vertical layout with QR code
- [ ] "Scan to trade" CTA text
- [ ] Pre-generate on listing create
- [ ] Store in `wp-content/uploads/hdh-shares/{listing_id}-story.jpg`

### 4.3 Share Buttons
- [ ] Create `inc/share-handler.php`
- [ ] Implement `hdh_get_share_urls()` - generate share links
- [ ] Add share buttons to `single-hayday_trade.php`
- [ ] Support: Facebook, Twitter, WhatsApp, Copy Link

**Files to create:**
- `inc/share-image-generator.php`
- `inc/share-handler.php`

**Files to modify:**
- `single-hayday_trade.php` (share buttons)
- `inc/create-trade-handler.php` (pre-generate images)

---

## Phase 5: Quests & Gamification

### 5.1 Quest System Foundation
- [ ] Create `inc/quest-system.php`
- [ ] Define quest structure (id, type, title, description, reward, progress)
- [ ] Implement `hdh_get_main_quests()` - core flow quests
- [ ] Implement `hdh_get_side_quests()` - daily tasks

### 5.2 Main Quest Auto-Tracking
- [ ] Hook into `hdh_add_bilet()` for "create listing" quest
- [ ] Hook into exchange completion for "complete exchange" quest
- [ ] Auto-check quest completion on relevant actions
- [ ] Award rewards automatically

### 5.3 Side Quest System
- [ ] Daily quest definitions (claim daily ticket, rate 3 exchanges, share listing)
- [ ] Implement `hdh_reset_daily_quests()` cron (midnight Turkey time)
- [ ] Track quest progress in user_meta
- [ ] Manual completion check (user-initiated)

### 5.4 Quest UI
- [ ] Create `components/quest-panel.php` component
- [ ] Persistent task icon on homepage
- [ ] Quest completion UI (toast notifications)
- [ ] Progress tracking in profile page

**Files to create:**
- `inc/quest-system.php`
- `inc/quest-handler.php`
- `components/quest-panel.php`

**Files to modify:**
- `front-page.php` (quest icon)
- `page-profil.php` (quest progress)
- `inc/create-trade-handler.php` (quest hooks)
- `inc/offers-handler.php` (quest hooks)

---

## Cross-Phase Dependencies

**Phase 1 → Phase 2:** Event logging needed for report/dispute audit trail
**Phase 1 → Phase 5:** Event system needed for quest auto-tracking
**Phase 2 → Phase 3:** Trust scores needed for SEO structured data
**Phase 3 → Phase 4:** Listing data needed for share images
**Phase 4 → Phase 5:** Share action needed for side quest

## Implementation Order Recommendation

1. **Phase 1** (Foundation - no dependencies)
2. **Phase 2** (Moderation - depends on Phase 1 events)
3. **Phase 3** (SEO - independent)
4. **Phase 4** (Sharing - can parallel with Phase 3)
5. **Phase 5** (Quests - depends on Phase 1 events + Phase 4 share action)

