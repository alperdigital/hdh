<?php
if (!function_exists('hdh_render_tasks_panel')) {
    function hdh_render_tasks_panel($user_id) {
        if (!$user_id) return;
        $can_claim_daily = function_exists('hdh_can_claim_daily_jeton') ? hdh_can_claim_daily_jeton($user_id) : false;
        $created_listing_today = false;
        $today_start = strtotime('today');
        $today_end = strtotime('tomorrow') - 1;
        $today_listings = new WP_Query(array('post_type' => 'hayday_trade', 'author' => $user_id, 'post_status' => 'publish', 'date_query' => array(array('after' => date('Y-m-d H:i:s', $today_start), 'before' => date('Y-m-d H:i:s', $today_end))), 'posts_per_page' => 1, 'fields' => 'ids'));
        if ($today_listings->have_posts()) $created_listing_today = true;
        wp_reset_postdata();
        $completed_exchange_today = false;
        $transactions = function_exists('hdh_get_jeton_transactions') ? hdh_get_jeton_transactions($user_id, 20) : array();
        foreach ($transactions as $transaction) {
            if (isset($transaction['reason']) && $transaction['reason'] === 'completed_exchange') {
                if (date('Y-m-d', strtotime($transaction['timestamp'])) === date('Y-m-d')) { $completed_exchange_today = true; break; }
            }
        }
        ?>
        <div class="tasks-panel">
            <h3 class="tasks-panel-title">Görevler</h3>
            <div class="tasks-list">
                <div class="task-item <?php echo $can_claim_daily ? '' : 'task-completed'; ?>">
                    <div class="task-info">
                        <span class="task-icon">🎟️</span>
                        <div class="task-details"><span class="task-name">Günlük Bilet Al</span><span class="task-reward">+1 Bilet</span></div>
                    </div>
                    <?php if ($can_claim_daily) : ?><button class="btn-claim-daily" data-user-id="<?php echo esc_attr($user_id); ?>">Al</button><?php else : ?><span class="task-status">✅ Tamamlandı</span><?php endif; ?>
                </div>
                <div class="task-item <?php echo $created_listing_today ? 'task-completed' : ''; ?>">
                    <div class="task-info">
                        <span class="task-icon">📝</span>
                        <div class="task-details"><span class="task-name">İlan Oluştur</span><span class="task-reward">+2 Bilet</span></div>
                    </div>
                    <?php if ($created_listing_today) : ?><span class="task-status">✅ Tamamlandı</span><?php else : ?><a href="<?php echo esc_url(home_url('/ilan-ver')); ?>" class="btn-do-task">Yap</a><?php endif; ?>
                </div>
                <div class="task-item <?php echo $completed_exchange_today ? 'task-completed' : ''; ?>">
                    <div class="task-info">
                        <span class="task-icon">🎁</span>
                        <div class="task-details"><span class="task-name">Hediyeleşmeyi Tamamla</span><span class="task-reward">+5 Bilet</span></div>
                    </div>
                    <?php if ($completed_exchange_today) : ?><span class="task-status">✅ Tamamlandı</span><?php else : ?><span class="task-status">Beklemede</span><?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
