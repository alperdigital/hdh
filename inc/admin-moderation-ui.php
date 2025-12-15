<?php
/**
 * HDH: Admin Moderation UI
 */

if (!defined('ABSPATH')) exit;

/**
 * Add custom columns to Reports list
 */
function hdh_reports_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = 'Report';
    $new_columns['reporter'] = 'Reporter';
    $new_columns['target'] = 'Target';
    $new_columns['type'] = 'Type';
    $new_columns['status'] = 'Status';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter('manage_hayday_report_posts_columns', 'hdh_reports_columns');

/**
 * Populate custom columns for Reports
 */
function hdh_reports_column_content($column, $post_id) {
    switch ($column) {
        case 'reporter':
            $reporter_id = get_post_meta($post_id, '_hdh_reporter_id', true);
            if ($reporter_id) {
                $user = get_userdata($reporter_id);
                echo $user ? esc_html($user->display_name) : 'User #' . $reporter_id;
            }
            break;
        case 'target':
            $target_id = get_post_meta($post_id, '_hdh_target_id', true);
            if ($target_id) {
                $user = get_userdata($target_id);
                echo $user ? esc_html($user->display_name) : 'User #' . $target_id;
                echo ' <a href="' . admin_url('user-edit.php?user_id=' . $target_id) . '">(Edit)</a>';
            }
            break;
        case 'type':
            $type = get_post_meta($post_id, '_hdh_report_type', true);
            echo esc_html($type ?: 'N/A');
            break;
        case 'status':
            $status = get_post_meta($post_id, '_hdh_report_status', true);
            $status_class = $status === 'resolved' ? 'resolved' : 'pending';
            echo '<span class="hdh-status-badge hdh-status-' . esc_attr($status_class) . '">' . esc_html(ucfirst($status ?: 'pending')) . '</span>';
            break;
    }
}
add_action('manage_hayday_report_posts_custom_column', 'hdh_reports_column_content', 10, 2);

/**
 * Add custom columns to Disputes list
 */
function hdh_disputes_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = 'Dispute';
    $new_columns['trade'] = 'Trade';
    $new_columns['initiator'] = 'Initiator';
    $new_columns['status'] = 'Status';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter('manage_hayday_dispute_posts_columns', 'hdh_disputes_columns');

/**
 * Populate custom columns for Disputes
 */
function hdh_disputes_column_content($column, $post_id) {
    switch ($column) {
        case 'trade':
            $trade_id = get_post_meta($post_id, '_hdh_trade_id', true);
            if ($trade_id) {
                $trade = get_post($trade_id);
                if ($trade) {
                    echo '<a href="' . get_edit_post_link($trade_id) . '">Trade #' . $trade_id . '</a>';
                } else {
                    echo 'Trade #' . $trade_id;
                }
            }
            break;
        case 'initiator':
            $initiator_id = get_post_meta($post_id, '_hdh_initiator_id', true);
            if ($initiator_id) {
                $user = get_userdata($initiator_id);
                echo $user ? esc_html($user->display_name) : 'User #' . $initiator_id;
            }
            break;
        case 'status':
            $status = get_post_meta($post_id, '_hdh_dispute_status', true);
            $status_class = $status === 'resolved' ? 'resolved' : 'open';
            echo '<span class="hdh-status-badge hdh-status-' . esc_attr($status_class) . '">' . esc_html(ucfirst($status ?: 'open')) . '</span>';
            break;
    }
}
add_action('manage_hayday_dispute_posts_custom_column', 'hdh_disputes_column_content', 10, 2);

/**
 * Add meta box for Report details
 */
function hdh_add_report_meta_box() {
    add_meta_box(
        'hdh_report_details',
        'Report Details',
        'hdh_render_report_meta_box',
        'hayday_report',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'hdh_add_report_meta_box');

/**
 * Render Report meta box
 */
function hdh_render_report_meta_box($post) {
    wp_nonce_field('hdh_save_report_meta', 'hdh_report_meta_nonce');
    
    $reporter_id = get_post_meta($post->ID, '_hdh_reporter_id', true);
    $target_id = get_post_meta($post->ID, '_hdh_target_id', true);
    $type = get_post_meta($post->ID, '_hdh_report_type', true);
    $reason = get_post_meta($post->ID, '_hdh_report_reason', true);
    $status = get_post_meta($post->ID, '_hdh_report_status', true);
    
    ?>
    <table class="form-table">
        <tr>
            <th><label>Reporter</label></th>
            <td>
                <?php if ($reporter_id) : 
                    $reporter = get_userdata($reporter_id);
                ?>
                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $reporter_id); ?>">
                        <?php echo esc_html($reporter ? $reporter->display_name : 'User #' . $reporter_id); ?>
                    </a>
                <?php else : ?>
                    N/A
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label>Target User</label></th>
            <td>
                <?php if ($target_id) : 
                    $target = get_userdata($target_id);
                ?>
                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $target_id); ?>">
                        <?php echo esc_html($target ? $target->display_name : 'User #' . $target_id); ?>
                    </a>
                <?php else : ?>
                    N/A
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label>Type</label></th>
            <td><?php echo esc_html($type ?: 'N/A'); ?></td>
        </tr>
        <tr>
            <th><label>Reason</label></th>
            <td><?php echo esc_html($reason ?: 'N/A'); ?></td>
        </tr>
        <tr>
            <th><label>Status</label></th>
            <td>
                <select name="hdh_report_status">
                    <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
                    <option value="reviewed" <?php selected($status, 'reviewed'); ?>>Reviewed</option>
                    <option value="resolved" <?php selected($status, 'resolved'); ?>>Resolved</option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save Report meta box
 */
function hdh_save_report_meta_box($post_id) {
    if (!isset($_POST['hdh_report_meta_nonce']) || !wp_verify_nonce($_POST['hdh_report_meta_nonce'], 'hdh_save_report_meta')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['hdh_report_status'])) {
        update_post_meta($post_id, '_hdh_report_status', sanitize_text_field($_POST['hdh_report_status']));
    }
}
add_action('save_post_hayday_report', 'hdh_save_report_meta_box');

/**
 * Add meta box for Dispute details
 */
function hdh_add_dispute_meta_box() {
    add_meta_box(
        'hdh_dispute_details',
        'Dispute Details',
        'hdh_render_dispute_meta_box',
        'hayday_dispute',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'hdh_add_dispute_meta_box');

/**
 * Render Dispute meta box
 */
function hdh_render_dispute_meta_box($post) {
    wp_nonce_field('hdh_save_dispute_meta', 'hdh_dispute_meta_nonce');
    
    $trade_id = get_post_meta($post->ID, '_hdh_trade_id', true);
    $offer_id = get_post_meta($post->ID, '_hdh_offer_id', true);
    $initiator_id = get_post_meta($post->ID, '_hdh_initiator_id', true);
    $other_party_id = get_post_meta($post->ID, '_hdh_other_party_id', true);
    $status = get_post_meta($post->ID, '_hdh_dispute_status', true);
    $resolution = get_post_meta($post->ID, '_hdh_resolution', true);
    
    ?>
    <table class="form-table">
        <tr>
            <th><label>Trade</label></th>
            <td>
                <?php if ($trade_id) : 
                    $trade = get_post($trade_id);
                ?>
                    <a href="<?php echo get_edit_post_link($trade_id); ?>">
                        Trade #<?php echo esc_html($trade_id); ?>
                    </a>
                <?php else : ?>
                    N/A
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label>Initiator</label></th>
            <td>
                <?php if ($initiator_id) : 
                    $initiator = get_userdata($initiator_id);
                ?>
                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $initiator_id); ?>">
                        <?php echo esc_html($initiator ? $initiator->display_name : 'User #' . $initiator_id); ?>
                    </a>
                <?php else : ?>
                    N/A
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label>Other Party</label></th>
            <td>
                <?php if ($other_party_id) : 
                    $other = get_userdata($other_party_id);
                ?>
                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $other_party_id); ?>">
                        <?php echo esc_html($other ? $other->display_name : 'User #' . $other_party_id); ?>
                    </a>
                <?php else : ?>
                    N/A
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label>Status</label></th>
            <td>
                <select name="hdh_dispute_status">
                    <option value="open" <?php selected($status, 'open'); ?>>Open</option>
                    <option value="resolved" <?php selected($status, 'resolved'); ?>>Resolved</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label>Resolution</label></th>
            <td>
                <textarea name="hdh_resolution" rows="4" style="width: 100%;"><?php echo esc_textarea($resolution); ?></textarea>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save Dispute meta box
 */
function hdh_save_dispute_meta_box($post_id) {
    if (!isset($_POST['hdh_dispute_meta_nonce']) || !wp_verify_nonce($_POST['hdh_dispute_meta_nonce'], 'hdh_save_dispute_meta')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['hdh_dispute_status'])) {
        $old_status = get_post_meta($post_id, '_hdh_dispute_status', true);
        $new_status = sanitize_text_field($_POST['hdh_dispute_status']);
        update_post_meta($post_id, '_hdh_dispute_status', $new_status);
        
        // If resolving, trigger resolution logic
        if ($old_status !== 'resolved' && $new_status === 'resolved' && isset($_POST['hdh_resolution'])) {
            update_post_meta($post_id, '_hdh_resolution', sanitize_textarea_field($_POST['hdh_resolution']));
        }
    }
    
    if (isset($_POST['hdh_resolution'])) {
        update_post_meta($post_id, '_hdh_resolution', sanitize_textarea_field($_POST['hdh_resolution']));
    }
}
add_action('save_post_hayday_dispute', 'hdh_save_dispute_meta_box');

/**
 * Add moderation dashboard widget
 */
function hdh_moderation_dashboard_widget() {
    wp_add_dashboard_widget(
        'hdh_moderation_queue',
        'Moderation Queue',
        'hdh_render_moderation_dashboard_widget'
    );
}
add_action('wp_dashboard_setup', 'hdh_moderation_dashboard_widget');

/**
 * Render moderation dashboard widget
 */
function hdh_render_moderation_dashboard_widget() {
    global $wpdb;
    
    // Pending reports count
    $pending_reports = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'hayday_report'
        AND p.post_status = 'publish'
        AND pm.meta_key = '_hdh_report_status'
        AND pm.meta_value = 'pending'"
    );
    
    // Open disputes count
    $open_disputes = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'hayday_dispute'
        AND p.post_status = 'publish'
        AND pm.meta_key = '_hdh_dispute_status'
        AND pm.meta_value = 'open'"
    );
    
    ?>
    <div class="hdh-moderation-widget">
        <p>
            <strong>Pending Reports:</strong> 
            <a href="<?php echo admin_url('edit.php?post_type=hayday_report&hdh_status=pending'); ?>">
                <?php echo esc_html($pending_reports); ?>
            </a>
        </p>
        <p>
            <strong>Open Disputes:</strong> 
            <a href="<?php echo admin_url('edit.php?post_type=hayday_dispute&hdh_status=open'); ?>">
                <?php echo esc_html($open_disputes); ?>
            </a>
        </p>
        <p>
            <a href="<?php echo admin_url('edit.php?post_type=hayday_report'); ?>" class="button">View All Reports</a>
            <a href="<?php echo admin_url('edit.php?post_type=hayday_dispute'); ?>" class="button">View All Disputes</a>
        </p>
    </div>
    <?php
}

/**
 * Enqueue admin styles
 */
function hdh_admin_moderation_styles() {
    wp_add_inline_style('wp-admin', '
        .hdh-status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .hdh-status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .hdh-status-resolved {
            background: #d4edda;
            color: #155724;
        }
        .hdh-status-open {
            background: #f8d7da;
            color: #721c24;
        }
        .hdh-moderation-widget p {
            margin: 10px 0;
        }
    ');
}
add_action('admin_enqueue_scripts', 'hdh_admin_moderation_styles');

