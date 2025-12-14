# HDH User State & Event System

## Overview

Comprehensive user state management and event tracking system for auditable rewards, leveling, trust scoring, and user actions.

## Architecture

```
┌─────────────────────────────────────────┐
│         User State System               │
│  (inc/user-state-system.php)           │
│                                         │
│  • Level & XP                          │
│  • Bilet Balance                       │
│  • Trust & Risk Scores                 │
│  • Verification Status                 │
│  • Badges & Achievements               │
│  • Ban System                          │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│         Event System                    │
│  (inc/event-system.php)                │
│                                         │
│  • Event Logging (DB)                  │
│  • Reward Tracking                     │
│  • Action Tracking                     │
│  • Audit Trail                         │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│         Database                        │
│  (wp_hdh_events table)                 │
│                                         │
│  • Permanent event storage             │
│  • Queryable audit logs                │
│  • 90-day retention                    │
└─────────────────────────────────────────┘
```

## User State Fields

### Core Stats
- `level` (int): User level (1-100)
- `xp` (int): Total experience points
- `bilet_balance` (int): Current bilet count

### Trust & Risk
- `trust_score` (int 0-100): Overall trust percentage
- `trust_plus` (int): Positive ratings received
- `trust_minus` (int): Negative ratings received
- `risk_score` (int 0-100): Risk assessment (higher = riskier)

### Verification
- `email_verified` (bool): Email verified
- `phone_verified` (bool): Phone verified
- `email_verified_at` (datetime): Verification timestamp
- `phone_verified_at` (datetime): Verification timestamp
- `verification_level` (int 0-3): Combined verification level

### Activity
- `total_trades` (int): Total trades created
- `completed_exchanges` (int): Successful exchanges
- `active_listings` (int): Current active listings
- `last_active` (datetime): Last activity timestamp
- `last_trade` (datetime): Last trade timestamp

### Achievements
- `badges` (array): Earned badges
- `achievements` (array): Unlocked achievements

### Restrictions
- `is_banned` (bool): Ban status
- `ban_reason` (string): Ban reason
- `ban_until` (datetime|'permanent'): Ban expiry

## API Reference

### Get User State

```php
$state = hdh_get_user_state($user_id);

// Returns:
array(
    'user_id' => 123,
    'level' => 5,
    'xp' => 850,
    'bilet_balance' => 15,
    'trust_score' => 85,
    'trust_plus' => 20,
    'trust_minus' => 3,
    'risk_score' => 10,
    'email_verified' => true,
    'phone_verified' => false,
    'verification_level' => 1,
    'is_verified' => true,
    'is_fully_verified' => false,
    'total_trades' => 12,
    'completed_exchanges' => 8,
    'badges' => array('level_5', 'first_trade'),
    'is_banned' => false,
    // ... more fields
)
```

### Update User State

```php
hdh_update_user_state($user_id, 'level', 6, 'Level up reward');
hdh_update_user_state($user_id, 'risk_score', 15, 'Suspicious activity');
```

### XP & Leveling

```php
// Add XP (auto-levels up)
hdh_add_xp($user_id, 100, 'completed_trade', array('trade_id' => 456));

// Calculate XP for level
$xp_needed = hdh_calculate_xp_for_level(10); // 316 XP

// XP Formula: 100 * level^1.5
Level 1: 0 XP
Level 2: 141 XP
Level 3: 245 XP
Level 5: 500 XP
Level 10: 1000 XP
Level 20: 2828 XP
```

### Trust & Risk

```php
// Update trust
hdh_update_trust_score($user_id, true, 'positive_review'); // +1 trust_plus
hdh_update_trust_score($user_id, false, 'negative_review'); // +1 trust_minus

// Update risk
hdh_update_risk_score($user_id, +10, 'failed_verification');
hdh_update_risk_score($user_id, -5, 'successful_trade');

// Auto-ban at risk_score >= 80
```

### Verification

```php
// Verify email (+1 bilet)
hdh_verify_email($user_id);

// Verify phone (+4 bilet)
hdh_verify_phone($user_id);

// Check verification
$state = hdh_get_user_state($user_id);
if ($state['is_fully_verified']) {
    // Both email and phone verified
}
```

### Badges & Achievements

```php
// Award badge
hdh_award_badge($user_id, 'first_trade', array('trade_id' => 123));
hdh_award_badge($user_id, 'level_10');

// Milestone badges auto-awarded at levels: 5, 10, 25, 50, 100
```

### Ban System

```php
// Ban user (7 days)
hdh_ban_user($user_id, 'Spam detected', 7);

// Permanent ban
hdh_ban_user($user_id, 'Terms violation', 0);

// Unban
hdh_unban_user($user_id, 'Appeal approved');

// Check ban status
if (hdh_is_user_banned($user_id)) {
    // User is banned
}
```

## Event System

### Event Types

| Event Type | Description | Data Fields |
|------------|-------------|-------------|
| `reward_earned` | Bilet/XP/Badge earned | reward_type, amount, reason |
| `xp_gain` | XP added | amount, reason, old_xp, new_xp |
| `level_up` | Level increased | old_level, new_level, xp |
| `trust_plus` | Positive rating | reason, new_plus, trust_score |
| `trust_minus` | Negative rating | reason, new_minus, trust_score |
| `risk_change` | Risk score changed | change, old_score, new_score |
| `email_verified` | Email verified | verified_at |
| `phone_verified` | Phone verified | verified_at |
| `badge_awarded` | Badge earned | badge_id |
| `user_action` | Generic action | action, context |
| `state_change` | State field changed | field, old_value, new_value |
| `user_banned` | User banned | reason, days, ban_until |
| `user_unbanned` | Ban removed | reason |

### Log Event

```php
hdh_log_event($user_id, 'user_action', array(
    'action' => 'created_listing',
    'listing_id' => 789,
    'item' => 'civata',
));

// Auto-captures:
// - IP address
// - User agent
// - Timestamp
```

### Track Reward

```php
hdh_track_reward($user_id, 'bilet', 5, 'completed_exchange', array(
    'other_user_id' => 456,
    'trade_id' => 789,
));
```

### Track Action

```php
hdh_track_action($user_id, 'viewed_listing', array(
    'listing_id' => 789,
));

// Auto-updates:
// - last_active timestamp
// - action count (hdh_action_count_viewed_listing)
```

### Get Events

```php
// Get recent events
$events = hdh_get_user_events($user_id, array(
    'event_type' => 'reward_earned',
    'limit' => 50,
    'order' => 'DESC',
    'date_from' => '2025-12-01 00:00:00',
));

// Get event statistics
$stats = hdh_get_event_stats($user_id, null, 30); // Last 30 days
```

### Audit Trail

```php
// Get formatted audit trail
$audit = hdh_get_audit_trail($user_id, 30); // Last 30 days

// Returns:
array(
    array(
        'timestamp' => '2025-12-15 10:30:00',
        'event_type' => 'reward_earned',
        'description' => 'Ödül kazanıldı: 5 bilet (completed_exchange)',
        'data' => array(...),
        'ip_address' => '192.168.1.1',
    ),
    // ... more entries
)
```

## Database Schema

### wp_hdh_events Table

```sql
CREATE TABLE wp_hdh_events (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id bigint(20) unsigned NOT NULL,
    event_type varchar(50) NOT NULL,
    event_data longtext,
    ip_address varchar(45),
    user_agent varchar(255),
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY event_type (event_type),
    KEY created_at (created_at),
    KEY user_event (user_id, event_type)
);
```

### Indexes
- `user_id`: Fast user queries
- `event_type`: Filter by type
- `created_at`: Date range queries
- `user_event`: Composite for user + type

### Retention
- Events older than 90 days are auto-deleted (weekly cron)
- Recent 50 events cached in user meta for quick access

## Integration Examples

### Award Bilet with Event Tracking

```php
// Old way (still works)
hdh_add_jeton($user_id, 5, 'completed_exchange');

// New way (auto-tracked)
hdh_add_bilet($user_id, 5, 'completed_exchange', array(
    'trade_id' => 789,
    'other_user_id' => 456,
));

// Events logged:
// 1. reward_earned (bilet)
// 2. Transaction in hdh_jeton_transactions
```

### Complete Trade with Full Tracking

```php
// Award XP
hdh_add_xp($user_id, 50, 'completed_trade', array(
    'trade_id' => 789,
));

// Award bilet
hdh_add_bilet($user_id, 5, 'completed_exchange');

// Update trust
hdh_update_trust_score($user_id, true, 'successful_exchange');

// Update stats
hdh_update_user_state($user_id, 'completed_exchanges', 
    $state['completed_exchanges'] + 1, 
    'Trade completed'
);

// All actions logged in events table!
```

### Check User Eligibility

```php
$state = hdh_get_user_state($user_id);

// Check verification
if (!$state['is_verified']) {
    return 'Please verify your email or phone';
}

// Check ban status
if ($state['is_banned']) {
    return 'Account suspended';
}

// Check risk score
if ($state['risk_score'] > 50) {
    return 'Account under review';
}

// Check level requirement
if ($state['level'] < 5) {
    return 'Minimum level 5 required';
}

// All good!
```

## Performance

### Caching
- User state: Cached in user meta
- Recent events: Last 50 in user meta
- Full events: Database query

### Query Optimization
- Indexed columns for fast lookups
- Composite indexes for common queries
- Pagination support for large result sets

### Cleanup
- Weekly cron job removes events > 90 days
- Keeps database size manageable
- Maintains audit trail for compliance

## Privacy & Security

### IP Address Handling
- Captured for audit purposes
- Can be hashed: `hdh_hash_ip($ip)`
- Respects privacy regulations

### Data Access
- Only user's own data accessible
- Admin can view all (capability check)
- Event data JSON-encoded

### GDPR Compliance
- User can request event history
- Data export available
- Deletion removes all events

## Hooks & Filters

### Actions
```php
// After event logged
do_action('hdh_event_logged', $event_id, $user_id, $event_type, $data);

// After level up
// (triggered in hdh_add_xp)

// After ban
// (triggered in hdh_ban_user)
```

### Filters
```php
// Modify user state before return
apply_filters('hdh_user_state', $state, $user_id);

// Modify XP formula
apply_filters('hdh_xp_for_level', $xp, $level);

// Modify level up rewards
apply_filters('hdh_level_up_bilet', $amount, $level);
```

## Migration from Old System

### Backward Compatibility
- All old `hdh_add_jeton` calls still work
- New `hdh_add_bilet` wrapper added
- Existing user meta preserved
- Events added on top (non-breaking)

### Gradual Adoption
1. System auto-creates events table
2. Old code continues working
3. New features use event system
4. Gradually migrate to new APIs

## Monitoring & Debugging

### Debug Logging
```php
// Enable in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Events logged to debug.log:
// [HDH Events] Cleaned up 150 old events
```

### Check Event Stats
```php
// Admin dashboard
$stats = hdh_get_event_stats($user_id, null, 7); // Last week

foreach ($stats as $stat) {
    echo "{$stat['event_type']}: {$stat['count']} times\n";
}
```

### Audit User Activity
```php
// Get full audit trail
$audit = hdh_get_audit_trail($user_id, 30);

// Export to CSV for analysis
// Review suspicious patterns
// Investigate abuse reports
```

## Future Enhancements

- [ ] Real-time event streaming
- [ ] Advanced analytics dashboard
- [ ] Machine learning risk detection
- [ ] Automated badge system
- [ ] Leaderboards
- [ ] Achievement notifications
- [ ] Event webhooks
- [ ] API endpoints for mobile apps

