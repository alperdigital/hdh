# Code Stubs for Critical Missing Pieces

## 1. Report/Dispute CPTs (`inc/moderation-system.php`)

```php
<?php
/**
 * HDH: Moderation System - Reports & Disputes
 */

if (!defined('ABSPATH')) exit;

/**
 * Register hayday_report CPT
 */
function hdh_register_report_cpt() {
    register_post_type('hayday_report', array(
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'hdh-moderation',
        'capability_type' => 'post',
        'supports' => array('title', 'editor'),
        'labels' => array(
            'name' => 'Reports',
            'singular_name' => 'Report',
        ),
    ));
}
add_action('init', 'hdh_register_report_cpt');

/**
 * Register hayday_dispute CPT
 */
function hdh_register_dispute_cpt() {
    register_post_type('hayday_dispute', array(
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'hdh-moderation',
        'capability_type' => 'post',
        'supports' => array('title', 'editor', 'comments'),
        'labels' => array(
            'name' => 'Disputes',
            'singular_name' => 'Dispute',
        ),
    ));
}
add_action('init', 'hdh_register_dispute_cpt');

/**
 * Create report
 */
function hdh_create_report($reporter_id, $target_id, $type, $reason) {
    // TODO: Create hayday_report CPT
    // TODO: Set meta: _hdh_reporter_id, _hdh_target_id, _hdh_report_type, _hdh_report_reason, _hdh_report_status = 'pending'
    // TODO: Log event: 'report_created'
    // TODO: Check if target has 3+ reports → auto-flag (risk_score += 20)
}

/**
 * Create dispute
 */
function hdh_create_dispute($trade_id, $offer_id, $initiator_id) {
    // TODO: Create hayday_dispute CPT
    // TODO: Set meta: _hdh_trade_id, _hdh_offer_id, _hdh_initiator_id, _hdh_dispute_status = 'open'
    // TODO: Log event: 'dispute_created'
    // TODO: Notify both parties
}

/**
 * Resolve dispute (admin only)
 */
function hdh_resolve_dispute($dispute_id, $resolution, $winner_user_id = null) {
    // TODO: Update dispute status = 'resolved'
    // TODO: Set _hdh_resolution meta
    // TODO: If winner_user_id:
    //   - Update winner: trust_plus +1
    //   - Update loser: trust_minus +1
    // TODO: Refund/keep tickets per resolution
    // TODO: Log event: 'dispute_resolved'
}
```

## 2. Cookie Consent (`inc/kvkk-compliance.php`)

```php
<?php
/**
 * HDH: KVKK Compliance - Cookie Consent
 */

if (!defined('ABSPATH')) exit;

/**
 * Show cookie consent banner
 */
function hdh_show_cookie_banner() {
    // TODO: Check if user has consented (user_meta or localStorage)
    // TODO: Check policy version (re-prompt if changed)
    // TODO: Render banner component
    // TODO: Include: Essential (always), Analytics, Marketing checkboxes
}

/**
 * Save cookie consent
 */
function hdh_save_cookie_consent($user_id, $consent_data) {
    // TODO: Store in user_meta: hdh_cookie_consent = JSON {analytics: bool, marketing: bool, timestamp, version}
    // TODO: Store in localStorage for UX (client-side)
    // TODO: Log event: 'cookie_consent_given'
}

/**
 * Check if consent given
 */
function hdh_has_cookie_consent($user_id, $category = 'all') {
    // TODO: Read from user_meta
    // TODO: Return bool based on category (analytics, marketing, all)
}

/**
 * GDPR Export
 */
function hdh_gdpr_export($user_id) {
    // TODO: Get all user data:
    //   - Profile (user_meta)
    //   - Events (hdh_get_audit_trail)
    //   - Trades (WP_Query)
    //   - Reports/Disputes
    // TODO: Format as JSON
    // TODO: Return downloadable file
}

/**
 * GDPR Delete/Anonymize
 */
function hdh_gdpr_delete($user_id) {
    // TODO: Anonymize name → "Deleted User {hash}"
    // TODO: Anonymize email → hash
    // TODO: Set hdh_deleted_user flag
    // TODO: Keep trades/events with anonymized user_id for 90 days
    // TODO: Schedule hard purge (90 days)
    // TODO: Log event: 'user_deleted'
}
```

## 3. Quest System (`inc/quest-system.php`)

```php
<?php
/**
 * HDH: Quest System - Main & Side Quests
 */

if (!defined('ABSPATH')) exit;

/**
 * Get main quests (always visible, auto-tracked)
 */
function hdh_get_main_quests() {
    return array(
        array(
            'id' => 'create_listing',
            'title' => 'İlan Oluştur',
            'description' => 'İlk ilanınızı oluşturun',
            'reward_tickets' => 2,
            'reward_xp' => 10,
            'progress' => 0,
            'max_progress' => 1,
        ),
        array(
            'id' => 'complete_exchange',
            'title' => 'Takas Tamamla',
            'description' => 'İlk takasınızı tamamlayın',
            'reward_tickets' => 5,
            'reward_xp' => 50,
            'progress' => 0,
            'max_progress' => 1,
        ),
    );
}

/**
 * Get side quests (daily, optional)
 */
function hdh_get_side_quests($user_id) {
    $today = date('Y-m-d');
    $last_reset = get_user_meta($user_id, 'hdh_quest_reset_date', true);
    
    if ($last_reset !== $today) {
        hdh_reset_daily_quests($user_id);
    }
    
    return array(
        array(
            'id' => 'daily_ticket',
            'title' => 'Günlük Bilet',
            'description' => 'Günlük biletinizi alın',
            'reward_tickets' => 1,
            'reward_xp' => 5,
            'progress' => 0,
            'max_progress' => 1,
        ),
        array(
            'id' => 'rate_exchanges',
            'title' => 'Değerlendirme Yap',
            'description' => '3 takası değerlendirin',
            'reward_tickets' => 2,
            'reward_xp' => 15,
            'progress' => 0,
            'max_progress' => 3,
        ),
        array(
            'id' => 'share_listing',
            'title' => 'İlan Paylaş',
            'description' => 'Bir ilanı paylaşın',
            'reward_tickets' => 1,
            'reward_xp' => 10,
            'progress' => 0,
            'max_progress' => 1,
        ),
    );
}

/**
 * Check quest completion
 */
function hdh_check_quest_completion($user_id, $quest_id) {
    // TODO: Get quest definition
    // TODO: Check progress from user_meta: hdh_quest_progress_{quest_id}
    // TODO: If complete, award rewards via hdh_add_bilet() / hdh_add_xp()
    // TODO: Mark as completed in user_meta
    // TODO: Log event: 'quest_completed'
    // TODO: Show toast notification
}

/**
 * Reset daily quests (cron: midnight Turkey time)
 */
function hdh_reset_daily_quests($user_id) {
    // TODO: Reset progress for all side quests
    // TODO: Update hdh_quest_reset_date = today
    // TODO: Log event: 'daily_quests_reset'
}

/**
 * Hook: Auto-track main quests
 */
function hdh_track_create_listing_quest($user_id, $listing_id) {
    // TODO: Called from create-trade-handler.php after listing created
    // TODO: Update progress for 'create_listing' quest
    // TODO: Check completion
}
add_action('hdh_listing_created', 'hdh_track_create_listing_quest', 10, 2);

function hdh_track_complete_exchange_quest($user_id, $trade_id) {
    // TODO: Called from offers-handler.php after exchange completed
    // TODO: Update progress for 'complete_exchange' quest
    // TODO: Check completion
}
add_action('hdh_exchange_completed', 'hdh_track_complete_exchange_quest', 10, 2);
```

## 4. Share Image Generator (`inc/share-image-generator.php`)

```php
<?php
/**
 * HDH: Share Image Generator - OG & Story Images
 */

if (!defined('ABSPATH')) exit;

/**
 * Generate OG image (1200x630)
 */
function hdh_generate_og_image($listing_id) {
    // TODO: Check cache: wp-content/uploads/hdh-shares/{listing_id}-og.jpg
    // TODO: If exists and < 7 days old, return URL
    // TODO: Get listing data (offered items, wanted item, user level, trust)
    // TODO: Create image with GD or Imagick:
    //   - Background: site color (#FFF6D8)
    //   - Left: Offered items (emoji + name)
    //   - Center: ↔ arrow
    //   - Right: Wanted item (emoji + name)
    //   - Bottom: User level badge + trust stars
    // TODO: Save to uploads/hdh-shares/
    // TODO: Return URL
}

/**
 * Generate story image (1080x1920)
 */
function hdh_generate_story_image($listing_id) {
    // TODO: Check cache: wp-content/uploads/hdh-shares/{listing_id}-story.jpg
    // TODO: If exists, return URL
    // TODO: Get listing data
    // TODO: Create vertical image:
    //   - Top: Listing title
    //   - Middle: Items (offered ↔ wanted)
    //   - Bottom: QR code to listing URL
    //   - Text: "Scan to trade"
    // TODO: Save to uploads/hdh-shares/
    // TODO: Return URL
}

/**
 * Pre-generate images on listing create
 */
function hdh_pregenerate_share_images($listing_id) {
    // TODO: Called from create-trade-handler.php
    // TODO: Generate both OG and story images
    // TODO: Run async (wp_schedule_single_event) if possible
}
add_action('hdh_listing_created', 'hdh_pregenerate_share_images', 20, 1);
```

## 5. SEO Handler (`inc/seo-handler.php`)

```php
<?php
/**
 * HDH: SEO Handler - Meta Tags & Structured Data
 */

if (!defined('ABSPATH')) exit;

/**
 * Output all SEO tags for listing
 */
function hdh_output_listing_seo($listing_id) {
    // TODO: Get listing data
    // TODO: Output canonical URL
    // TODO: Output OG tags (title, description, image, type)
    // TODO: Output Twitter card tags
    // TODO: Output Schema.org JSON-LD
}

/**
 * Generate Schema.org Offer structured data
 */
function hdh_generate_schema_offer($listing_id) {
    // TODO: Get listing data
    // TODO: Build JSON-LD:
    //   {
    //     "@context": "https://schema.org",
    //     "@type": "Offer",
    //     "itemOffered": {wanted_item},
    //     "price": "0",
    //     "priceCurrency": "TRY",
    //     "availability": "InStock" or "OutOfStock",
    //     "seller": {
    //       "@type": "Person",
    //       "name": user_name,
    //       "aggregateRating": trust_rating
    //     }
    //   }
    // TODO: Return JSON string
}
```

## Integration Points

### In `inc/create-trade-handler.php`:
```php
// After listing created:
do_action('hdh_listing_created', $user_id, $post_id);
// Triggers: quest tracking + image pre-generation
```

### In `inc/offers-handler.php`:
```php
// After exchange completed:
do_action('hdh_exchange_completed', $user_id, $trade_id);
// Triggers: quest tracking
```

### In `single-hayday_trade.php`:
```php
// In wp_head:
hdh_output_listing_seo(get_the_ID());
```

### In `functions.php`:
```php
require_once get_template_directory() . '/inc/moderation-system.php';
require_once get_template_directory() . '/inc/kvkk-compliance.php';
require_once get_template_directory() . '/inc/quest-system.php';
require_once get_template_directory() . '/inc/share-image-generator.php';
require_once get_template_directory() . '/inc/seo-handler.php';
```

