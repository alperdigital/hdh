# HDH Architecture Decision Document

## A) Global Architecture Decisions
- Event-sourced state model: all rewards, trust changes, and user actions logged to `wp_hdh_events` table with 90-day retention for auditability and KVKK compliance
- WordPress custom post types for entities (trades, offers, messages); user_meta for derived state; database table for immutable event log

## B) Data & State Model

### User progression model (level/XP/tickets)
- Level 1-100 via XP formula `100 * level^1.5`; auto-level on XP gain; level-up grants `min(level, 10)` tickets
- Composite state: computed fields (trust_rating 0-5 stars, level_progress_percent) cached in user_meta; derived on read from trust_plus/trust_minus counters

### Event/ledger model for rewards
- Immutable append-only `wp_hdh_events` table (user_id, event_type, event_data JSON, ip_address, created_at); last 50 cached in user_meta for fast reads
- Dual-write pattern: update state in user_meta + log event atomically; events auto-logged by wrapper functions (hdh_add_bilet, hdh_add_xp, hdh_update_trust_score)

### Listing & trade lifecycle objects
- Custom post type `hayday_trade` with meta `_hdh_trade_status` (open/accepted/completed/closed/expired); soft delete via status change (not wp_trash)
- Offer object: `hayday_offer` CPT linked to trade; messaging: `hayday_message` CPT linked to trade+offer; all transitions logged as events

## C) Security & KVKK Alignment

### Password hashing standard
- Use WordPress core `wp_hash_password()` (bcrypt with 10 rounds); no custom implementation

### CSRF/session safety
- WordPress nonces for all AJAX actions (15-minute TTL); verify capability + ownership on state-changing operations
- Session: WordPress auth cookies (httpOnly, secure in production); no custom session handling

### Logging scope + retention policy
- Log: all rewards, trust changes, bans, state changes to `wp_hdh_events` with IP hash; 90-day auto-purge via weekly cron
- Do NOT log: passwords, raw IPs (hash via `hdh_hash_ip()`), PII beyond user_id; GDPR export via `hdh_get_audit_trail()`

### Cookie categories + consent storage
- Essential: WordPress auth cookies (always on); analytics/marketing: separate consent flag in user_meta (`hdh_cookie_consent` JSON: {analytics: bool, marketing: bool, timestamp})
- Consent banner: show once; choice stored in user_meta (logged) + localStorage (UX persistence); re-prompt on policy version change

### Data deletion/anonymization rules
- Soft delete: user requests → anonymize name/email → set `hdh_deleted_user` flag → keep trades/events with anonymized user_id for 90 days → hard purge
- Right to forget: export via `hdh_get_audit_trail()` + delete events + anonymize user_meta; listings marked as "deleted user"

## D) Trust & Moderation System

### Block/report/dispute boundaries
- Block: user-level (stored array in user_meta `hdh_blocked_users`); affects listing visibility only (client-side filter)
- Report: creates `hayday_report` CPT (reporter_id, target_id, type, reason, status); admin review queue; auto-flag if 3+ reports
- Dispute: available post-acceptance if either party clicks "Issue with exchange"; opens dispute CPT (immutable); both parties + admin can comment; resolution updates trust scores

### Trade/dispute lifecycle outcomes
- Completed (both confirm) → +5 tickets each + trust_plus +1 → trade status = completed
- Disputed → admin resolves → trust_plus/trust_minus adjusted per ruling → tickets refunded/kept per resolution
- Expired (30 days inactive) → auto-close via cron → no trust impact → tickets refunded if pre-paid

### Reputation/level transparency (tooltip semantics)
- Level badge: tooltip shows "Level X • Y successful exchanges • Z% trust rating • Member since DATE"
- Trust stars (0-5): tooltip shows "X positive • Y negative • Based on Z completed exchanges"
- Show level/trust everywhere user appears (listings, comments, profile); no hiding for "fairness"

## E) Listing Lifecycle & SEO/Sharing

### Active/inactive/filled/expired handling
- Active (status=open) → visible in feed; Filled (status=completed) → 404 redirect to alternatives (3 similar listings via `hdh_get_alternative_trades()`)
- Expired (30+ days) → cron sets status=expired → show "Expired listing" + alternatives; owner can reactivate (reset date)
- Inactive (owner deactivated) → status=closed → only owner sees; others → "Listing inactive" + alternatives

### "Closed listing" user experience strategy
- Soft 404: no actual 404 status code (bad for SEO); show contextual page with reason + 3 alternative listings (same wanted_item first, then recent)
- CTA: "View similar listings" button + "Create your own listing" secondary button

### SEO tags strategy (canonical/meta/OG/Twitter)
- Canonical: `<link rel="canonical" href="https://hayday.help/hayday_trade/{id}/">` (trailing slash for consistency)
- OG: title = "{offered_items} ↔ {wanted_items} | hayday.help", description = first 150 chars, image = share image (below), type = website
- Twitter: card = summary_large_image, use same OG tags

### Structured data choice
- Schema.org/Offer: itemOffered (wanted item), price = 0 TRY, priceCurrency = TRY, availability = InStock/OutOfStock per status, seller = user name + trust rating
- Add to head as JSON-LD; index via Google Rich Results; update availability on status change

### Share-image strategy (post/story)
- Generate 1200x630 OG image on-the-fly (cached 7 days): show offered items (emoji + name) ↔ wanted item + user level badge + trust stars
- Story format (1080x1920): vertical layout with QR code to listing + "Scan to trade" CTA; pre-generate on listing create; store in uploads/hdh-shares/

## F) Gamification Economy Safety

### Main vs side quests model
- Main quests: tied to core flow (create listing → 2 tickets + 10 XP, complete exchange → 5 tickets + 50 XP); always visible; auto-tracked
- Side quests: daily tasks (claim daily ticket, rate 3 exchanges, share listing); separate UI panel; reset at midnight Turkey time; optional

### Reward issuance rules + safety
- Rewards via `hdh_add_bilet()` / `hdh_add_xp()` only (never direct meta update); all logged to events table
- Anti-abuse: max 1 exchange reward per user pair per day (checked via event log query); rate limit: max 5 tickets/hour per user (sliding window); auto-ban if risk_score >= 80

### Level-up ticket bonus strategy
- Formula: `min(level, 10)` tickets on level-up (max 10 tickets at level 10+)
- Bonus announced via toast notification + event log; visible in profile XP progress bar; incentivizes early progression

## G) Phased Delivery Plan

**Phase 1 – KVKK & Security Hardening**
- Password hashing audit, CSRF nonce audit, cookie consent banner, GDPR export/delete endpoints, IP hashing in events

**Phase 2 – Trust & Moderation Layer**
- Block/report CPT, dispute flow, admin review queue, trust score tooltips, level badge display

**Phase 3 – Listing Lifecycle & SEO**
- Soft 404 + alternatives, expired/inactive handling, canonical/OG/Twitter tags, Schema.org structured data

**Phase 4 – Social Sharing**
- OG image generation (1200x630), story image generation (1080x1920), share buttons on listing detail, pre-cache on create

**Phase 5 – Quests & Gamification**
- Main quest auto-tracking, side quest panel (daily tasks), quest completion UI, persistent task icon on home, progress tracking in profile

