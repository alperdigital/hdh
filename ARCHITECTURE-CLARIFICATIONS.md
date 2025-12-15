# Architecture Clarifications

## Unclear Sections - Resolved Decisions

### 1. Dispute Resolution Flow

**Question:** How does admin resolution update trust scores? Manual action or automated?

**Decision:**
- Admin manually resolves dispute via admin UI
- Resolution triggers `hdh_resolve_dispute($dispute_id, $resolution, $winner_user_id)`
- Function automatically:
  - Updates winner: `hdh_update_trust_score($winner_id, true, 'dispute_resolved')`
  - Updates loser: `hdh_update_trust_score($loser_id, false, 'dispute_resolved')`
  - Refunds/keeps tickets based on resolution type
  - Logs event: `dispute_resolved`

**Implementation:**
```php
// Admin clicks "Resolve" → calls hdh_resolve_dispute()
// Function handles all trust/risk updates automatically
// No manual trust score manipulation needed
```

---

### 2. Quest Auto-Tracking

**Question:** How to detect "create listing" vs "complete exchange" automatically?

**Decision:**
- Use WordPress action hooks (`do_action()`)
- Hook into existing functions:
  - `inc/create-trade-handler.php`: After listing created → `do_action('hdh_listing_created', $user_id, $post_id)`
  - `inc/offers-handler.php`: After exchange completed → `do_action('hdh_exchange_completed', $user_id, $trade_id)`
- Quest system listens to these hooks and auto-updates progress

**Implementation:**
```php
// In create-trade-handler.php (after wp_insert_post success):
do_action('hdh_listing_created', $user_id, $post_id);

// In quest-system.php:
add_action('hdh_listing_created', 'hdh_track_create_listing_quest', 10, 2);
// Auto-updates quest progress, checks completion, awards rewards
```

**No manual tracking needed** - hooks handle it automatically.

---

### 3. Share Image Caching

**Question:** Where to store generated images?

**Decision:**
- Path: `wp-content/uploads/hdh-shares/`
- Filenames: `{listing_id}-og.jpg` and `{listing_id}-story.jpg`
- Cache check: File exists + modification time < 7 days = use cached
- Pre-generation: On listing create (async via `wp_schedule_single_event`)
- Cleanup: Delete images when listing deleted (or after 90 days)

**Implementation:**
```php
$upload_dir = wp_upload_dir();
$share_dir = $upload_dir['basedir'] . '/hdh-shares/';
wp_mkdir_p($share_dir); // Create if doesn't exist

$og_path = $share_dir . $listing_id . '-og.jpg';
$story_path = $share_dir . $listing_id . '-story.jpg';
```

---

### 4. Blocked Users Filtering

**Question:** Client-side only or server-side?

**Decision:**
- **Server-side filtering** in listing queries (primary)
- **Client-side hiding** for UI polish (secondary)
- Blocked users array stored in `user_meta: hdh_blocked_users` (array of user IDs)
- Modify `WP_Query` in `inc/ajax-handlers.php` and `page-ara.php` to exclude listings from blocked users

**Implementation:**
```php
// In listing queries:
$blocked_users = get_user_meta($current_user_id, 'hdh_blocked_users', true) ?: array();
if (!empty($blocked_users)) {
    $args['author__not_in'] = $blocked_users; // Exclude blocked users' listings
}
```

**Client-side:** Hide already-loaded listings from blocked users (UX enhancement only).

---

### 5. Rate Limiting for Tickets

**Question:** Sliding window implementation?

**Decision:**
- Query `wp_hdh_events` table for `reward_earned` events in last hour
- Count total tickets awarded in that window
- Reject if >= 5 tickets/hour
- Use database query (not in-memory) for accuracy across requests

**Implementation:**
```php
function hdh_check_ticket_rate_limit($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'hdh_events';
    $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    $events = $wpdb->get_results($wpdb->prepare(
        "SELECT event_data FROM {$table}
        WHERE user_id = %d
        AND event_type = 'reward_earned'
        AND created_at >= %s",
        $user_id, $one_hour_ago
    ));
    
    $total_tickets = 0;
    foreach ($events as $event) {
        $data = json_decode($event->event_data, true);
        if ($data['reward_type'] === 'bilet') {
            $total_tickets += (int) $data['amount'];
        }
    }
    
    return $total_tickets < 5; // Allow if < 5 tickets in last hour
}
```

**Called before:** `hdh_add_bilet()` - rejects if limit exceeded.

---

### 6. Quest Reset Timing

**Question:** Midnight Turkey time - how to handle timezone?

**Decision:**
- Use WordPress `current_time('Y-m-d')` with timezone set to `Europe/Istanbul`
- Cron job: `wp_schedule_event()` with daily recurrence
- Reset function: `hdh_reset_daily_quests()` called for all users
- Check: Compare `hdh_quest_reset_date` (user_meta) with today's date

**Implementation:**
```php
// In functions.php or quest-system.php:
if (!wp_next_scheduled('hdh_reset_daily_quests')) {
    // Schedule for 00:00 Turkey time (convert to server time)
    $turkey_tz = new DateTimeZone('Europe/Istanbul');
    $midnight = new DateTime('tomorrow midnight', $turkey_tz);
    wp_schedule_event($midnight->getTimestamp(), 'daily', 'hdh_reset_daily_quests');
}

add_action('hdh_reset_daily_quests', function() {
    // Get all users, reset their daily quests
    $users = get_users();
    foreach ($users as $user) {
        hdh_reset_daily_quests($user->ID);
    }
});
```

---

### 7. IP Hashing in Events

**Question:** Hash before or after storage?

**Decision:**
- **Hash before storage** - never store raw IP
- Use `hdh_hash_ip()` function (already exists in `inc/event-system.php`)
- Hash in `hdh_log_event()` before database insert
- Raw IP only exists in memory during request (not persisted)

**Implementation:**
```php
// In hdh_log_event():
$ip_address = hdh_get_client_ip(); // Get raw IP
$ip_hash = hdh_hash_ip($ip_address); // Hash it
$event['ip_address'] = $ip_hash; // Store hash only
```

**GDPR compliant:** Cannot reverse hash to get original IP.

---

### 8. Dispute Comment System

**Question:** How do both parties comment on dispute?

**Decision:**
- Use WordPress native comments system on `hayday_dispute` CPT
- Enable comments on dispute CPT: `'supports' => array('comments')`
- Only dispute parties + admin can comment (capability check)
- Comments are immutable (no edit/delete after submission)

**Implementation:**
```php
// In hdh_register_dispute_cpt():
'supports' => array('title', 'editor', 'comments'),

// In comment form:
if (current_user_can('administrator') || 
    $user_id == $dispute->initiator_id || 
    $user_id == $dispute->other_party_id) {
    // Show comment form
}
```

---

### 9. Share Image Generation Library

**Question:** GD or Imagick?

**Decision:**
- **Try Imagick first** (better quality, text rendering)
- **Fallback to GD** if Imagick not available
- Check: `extension_loaded('imagick')`
- Both support required: image creation, text rendering, QR code generation

**Implementation:**
```php
function hdh_generate_image($width, $height) {
    if (extension_loaded('imagick')) {
        return new Imagick();
    } elseif (function_exists('imagecreatetruecolor')) {
        return imagecreatetruecolor($width, $height);
    } else {
        return new WP_Error('no_image_lib', 'No image library available');
    }
}
```

---

### 10. Auto-Flag on 3+ Reports

**Question:** When does auto-flag trigger?

**Decision:**
- Trigger: Immediately when 3rd report is created
- Action: `hdh_update_risk_score($target_id, +20, 'multiple_reports')`
- Notification: Email admin about flagged user
- Review: Admin reviews all reports, can ban if needed

**Implementation:**
```php
// In hdh_create_report():
$report_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type = 'hayday_report'
    AND pm.meta_key = '_hdh_target_id'
    AND pm.meta_value = %d
    AND p.post_status = 'publish'",
    $target_id
));

if ($report_count >= 3) {
    hdh_update_risk_score($target_id, +20, 'multiple_reports');
    // Send admin notification
}
```

---

## Summary of Clarifications

1. **Dispute resolution:** Admin action → automatic trust updates
2. **Quest tracking:** WordPress hooks → auto-detection
3. **Image storage:** `wp-content/uploads/hdh-shares/` with 7-day cache
4. **Blocked users:** Server-side query filtering (primary)
5. **Rate limiting:** Database query for sliding window
6. **Quest reset:** Cron at midnight Turkey time
7. **IP hashing:** Hash before storage (never raw IP)
8. **Dispute comments:** WordPress native comments with capability checks
9. **Image library:** Imagick preferred, GD fallback
10. **Auto-flag:** Trigger on 3rd report creation

All decisions align with existing architecture and WordPress best practices.

