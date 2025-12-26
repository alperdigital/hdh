/**
 * HDH: Gift Overlay JavaScript
 * Handles gift overlay interactions and trade detail view
 */

(function() {
    'use strict';
    
    // Configuration
    const config = {
        ajaxUrl: hdhGiftOverlay?.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: hdhGiftOverlay?.nonce || '',
        pollInterval: 10000, // 10 seconds
    };
    
    // State
    let pollTimer = null;
    let isPanelOpen = false;
    let currentDetailSessionId = null;
    
    /**
     * Initialize gift overlay
     */
    function init() {
        const button = document.getElementById('hdh-gift-overlay-button');
        const panel = document.getElementById('hdh-gift-overlay-panel');
        const closeBtn = document.getElementById('hdh-gift-overlay-close');
        
        if (!button || !panel) {
            return;
        }
        
        // Toggle panel
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            togglePanel();
        });
        
        // Close button
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                closePanel();
            });
        }
        
        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (isPanelOpen && !button.contains(e.target) && !panel.contains(e.target)) {
                closePanel();
            }
        });
        
        // Open trade buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-open-trade')) {
                const sessionId = e.target.getAttribute('data-session-id');
                if (sessionId) {
                    openTradeDetail(parseInt(sessionId));
                }
            }
        });
        
        // Start polling for updates
        startPolling();
    }
    
    /**
     * Toggle panel
     */
    function togglePanel() {
        const panel = document.getElementById('hdh-gift-overlay-panel');
        const button = document.getElementById('hdh-gift-overlay-button');
        
        if (!panel || !button) {
            return;
        }
        
        isPanelOpen = !isPanelOpen;
        
        if (isPanelOpen) {
            panel.style.display = 'flex';
            button.setAttribute('aria-expanded', 'true');
            loadActiveTrades();
        } else {
            closePanel();
        }
    }
    
    /**
     * Close panel
     */
    function closePanel() {
        const panel = document.getElementById('hdh-gift-overlay-panel');
        const button = document.getElementById('hdh-gift-overlay-button');
        
        if (!panel || !button) {
            return;
        }
        
        isPanelOpen = false;
        currentDetailSessionId = null;
        panel.style.display = 'none';
        button.setAttribute('aria-expanded', 'false');
        
        // Reset to list view
        const content = document.getElementById('hdh-gift-overlay-content');
        if (content) {
            loadActiveTrades();
        }
    }
    
    /**
     * Load active trades
     */
    function loadActiveTrades() {
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_get_active_trades',
                nonce: config.nonce,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.trades) {
                renderTradesList(data.data.trades);
                updateBadgeCount(data.data.action_required_count || 0);
            } else {
                renderEmptyState();
            }
        })
        .catch(error => {
            console.error('Error loading active trades:', error);
        });
    }
    
    /**
     * Render trades list with full details (direct view, no "Aç" button needed)
     */
    function renderTradesList(trades) {
        const content = document.getElementById('hdh-gift-overlay-content');
        if (!content) {
            return;
        }
        
        if (trades.length === 0) {
            renderEmptyState();
            return;
        }
        
        // Load all trade details in parallel
        const tradePromises = trades.map(trade => {
            return fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hdh_get_trade_session',
                    nonce: config.nonce,
                    session_id: trade.id,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.session) {
                    return {
                        session: data.data.session,
                        listing: null, // Will be loaded separately
                        trade: trade
                    };
                }
                return null;
            })
            .catch(error => {
                console.error('Error loading trade detail:', error);
                return null;
            });
        });
        
        // Wait for all session data
        Promise.all(tradePromises).then(results => {
            const validResults = results.filter(r => r !== null);
            
            // Load listing data for each trade
            const listingPromises = validResults.map(result => {
                return fetch(config.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'hdh_get_listing_data',
                        nonce: config.nonce,
                        listing_id: result.session.listing_id,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.listing) {
                        result.listing = data.data.listing;
                    }
                    return result;
                })
                .catch(error => {
                    console.error('Error loading listing data:', error);
                    return result;
                });
            });
            
            Promise.all(listingPromises).then(finalResults => {
                renderTradesWithDetails(finalResults);
            });
        });
    }
    
    /**
     * Render all trades with full detail view (NEW 3-STEP SYSTEM)
     */
    function renderTradesWithDetails(tradeDetails) {
        const content = document.getElementById('hdh-gift-overlay-content');
        if (!content) {
            return;
        }
        
        if (tradeDetails.length === 0) {
            renderEmptyState();
            return;
        }
        
        let html = '<div class="gift-overlay-trades-detailed" id="hdh-gift-trades-detailed">';
        
        tradeDetails.forEach(({session, listing, trade}) => {
            if (!session || !listing) {
                return;
            }
            
            const isStarter = session.is_starter || false;
            const isOwner = session.is_owner || false;
            const levelDigits = String(trade.counterpart_level || 1).length;
            const levelClass = `lvl-d${levelDigits}`;
            const actionBadge = trade.requires_action ? '<span class="gift-trade-action-badge">Aksiyon Gerekli</span>' : '';
            
            // Determine if trade is completed
            const isCompleted = session.status === 'COMPLETED';
            const completedClass = isCompleted ? 'trade-completed' : '';
            
            // Get timeline events
            const timelineEvents = session.timeline_events || [];
            
            // Get status label
            const statusLabel = session.status_label || 'Aktif';
            const statusBadge = renderStatusBadge(session);
            
            html += `
                <div class="gift-trade-detailed-item ${completedClass}" data-session-id="${session.id}">
                    <div class="gift-trade-detailed-info">
                        <div class="gift-trade-detailed-icon">🎁</div>
                        <div class="gift-trade-detailed-details">
                            <div class="gift-trade-detailed-name">
                                ${escapeHtml(listing.title || 'İlan')}
                                ${statusBadge}
                                ${actionBadge}
                            </div>
                            <div class="gift-trade-detailed-description">
                                <a href="/profil?user=${trade.counterpart_id}" class="gift-trade-user">
                                    <div class="hdh-level-badge ${levelClass}" aria-label="Seviye ${trade.counterpart_level}">
                                        ${trade.counterpart_level || 1}
                                    </div>
                                    <span class="gift-trade-farm-name">${escapeHtml(trade.counterpart_name || 'Bilinmeyen')}</span>
                                    <span class="gift-trade-presence">${escapeHtml(trade.counterpart_presence || '3+ gün önce')}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="gift-trade-detailed-expandable">
                        ${renderChatTimeline(session, timelineEvents)}
                        <div class="gift-trade-detailed-steps">
                            ${renderStep1(session)}
                            ${renderStep2(session)}
                            ${renderStep3(session)}
                        </div>
                        
                        ${session.status !== 'COMPLETED' ? `
                            <div class="gift-trade-complete-section">
                                <button type="button" class="btn-complete-trade" data-session-id="${session.id}">
                                    Hediyeleşme tamamlandı
                                </button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        content.innerHTML = html;
        
        // Add event listeners for all trades
        document.querySelectorAll('.btn-share-farm-code').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                const input = document.getElementById(`offerer-farm-code-${sessionId}`);
                const farmCode = input ? input.value.trim() : '';
                if (farmCode) {
                    handleShareFarmCode(sessionId, farmCode);
                } else {
                    showToast('Lütfen çiftlik kodunuzu girin', 'error');
                }
            });
        });
        
        document.querySelectorAll('.btn-send-friend-request').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                handleSendFriendRequest(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-accept-friend-request').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                handleAcceptFriendRequest(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-mark-gift-ready').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                handleMarkGiftReady(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-mark-gift-collected').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                handleMarkGiftCollected(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-complete-trade').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                handleCompleteTrade(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-ping-trade').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                sendPing(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-report-issue').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                openReportModal(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-copy-code').forEach(btn => {
            btn.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                navigator.clipboard.writeText(code).then(() => {
                    showToast('Çiftlik kodu kopyalandı!', 'success');
                });
            });
        });
        
        // Update report button timers
        tradeDetails.forEach(({session}) => {
            if (session) {
                updateReportButtonTimer(session);
            }
        });
        
        // Start polling for all trades
        startPolling();
    }
    
    /**
     * Render empty state
     */
    function renderEmptyState() {
        const content = document.getElementById('hdh-gift-overlay-content');
        if (!content) {
            return;
        }
        
        content.innerHTML = `
            <div class="gift-overlay-empty">
                <span class="empty-icon">🎁</span>
                <p class="empty-text">Aktif hediyeleşme yok</p>
            </div>
        `;
    }
    
    /**
     * Open trade detail view
     */
    function openTradeDetail(sessionId) {
        currentDetailSessionId = sessionId;
        
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_get_trade_session',
                nonce: config.nonce,
                session_id: sessionId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.session) {
                renderTradeDetail(data.data.session);
            } else {
                showToast('Hediyeleşme detayları yüklenemedi', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading trade detail:', error);
            showToast('Bir hata oluştu', 'error');
        });
    }
    
    /**
     * Render trade detail view
     */
    function renderTradeDetail(session) {
        const content = document.getElementById('hdh-gift-overlay-content');
        if (!content) {
            return;
        }
        
        const listingId = session.listing_id;
        const currentStep = session.current_step || 1;
        const status = session.status || 'ACTIVE';
        const isStarter = session.is_starter || false;
        const isOwner = session.is_owner || false;
        
        // Get listing data via AJAX
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_get_listing_data',
                nonce: config.nonce,
                listing_id: listingId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.listing) {
                const listing = data.data.listing;
                renderTradeDetailContent(session, listing);
            } else {
                content.innerHTML = `
                    <div class="gift-trade-detail-error">
                        <p>İlan bilgileri yüklenemedi</p>
                        <button type="button" class="btn-back-to-list" onclick="hdhGiftOverlayBackToList()">
                            ← Listeye Dön
                        </button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading listing data:', error);
            content.innerHTML = `
                <div class="gift-trade-detail-error">
                    <p>Bir hata oluştu</p>
                    <button type="button" class="btn-back-to-list" onclick="hdhGiftOverlayBackToList()">
                        ← Listeye Dön
                    </button>
                </div>
            `;
        });
    }
    
    /**
     * Render trade detail content (NEW 3-STEP SYSTEM)
     */
    function renderTradeDetailContent(session, listing) {
        const content = document.getElementById('hdh-gift-overlay-content');
        if (!content) {
            return;
        }
        
        const status = session.status || 'ACTIVE';
        const isCompleted = status === 'COMPLETED';
        
        // Get timeline events
        const timelineEvents = session.timeline_events || [];
        
        // Get status label
        const statusBadge = renderStatusBadge(session);
        
        let html = `
            <div class="gift-trade-detail">
                <div class="gift-detail-header">
                    <button type="button" class="btn-back-to-list" onclick="hdhGiftOverlayBackToList()">
                        ← Listeye Dön
                    </button>
                    <h3 class="gift-detail-title">${escapeHtml(listing.title || 'İlan')}</h3>
                    ${statusBadge}
                </div>
                
                <div class="gift-detail-expandable">
                    ${renderChatTimeline(session, timelineEvents)}
                    <div class="gift-detail-steps">
                        ${renderStep1(session)}
                        ${renderStep2(session)}
                        ${renderStep3(session)}
                    </div>
                    
                    ${!isCompleted ? `
                        <div class="gift-trade-complete-section">
                            <button type="button" class="btn-complete-trade" data-session-id="${session.id}">
                                Hediyeleşme tamamlandı
                            </button>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        content.innerHTML = html;
        
        // Add event listeners (same as renderTradesWithDetails)
        document.querySelectorAll('.btn-share-farm-code').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                const input = document.getElementById(`offerer-farm-code-${sessionId}`);
                const farmCode = input ? input.value.trim() : '';
                if (farmCode) {
                    handleShareFarmCode(sessionId, farmCode);
                } else {
                    showToast('Lütfen çiftlik kodunuzu girin', 'error');
                }
            });
        });
        
        document.querySelectorAll('.btn-send-friend-request').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                handleSendFriendRequest(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-accept-friend-request').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                handleAcceptFriendRequest(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-mark-gift-ready').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                handleMarkGiftReady(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-mark-gift-collected').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                handleMarkGiftCollected(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-complete-trade').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                handleCompleteTrade(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-ping-trade').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                sendPing(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-report-issue').forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                openReportModal(sessionId);
            });
        });
        
        document.querySelectorAll('.btn-copy-code').forEach(btn => {
            btn.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                navigator.clipboard.writeText(code).then(() => {
                    showToast('Çiftlik kodu kopyalandı!', 'success');
                });
            });
        });
        
        // Update report button timer
        updateReportButtonTimer(session);
        
        // Start timer interval for report button
        if (session.report_unlock_at) {
            const timerInterval = setInterval(() => {
                updateReportButtonTimer(session);
                // Stop interval if timer expired
                const unlockTime = new Date(session.report_unlock_at).getTime();
                const currentTime = new Date().getTime();
                if (currentTime >= unlockTime) {
                    clearInterval(timerInterval);
                }
            }, 1000);
        }
        
        // Start polling for this detail view
        startDetailPolling(session.id);
    }
    
    /**
     * Render a step as mini task card (DEPRECATED - OLD 5-STEP SYSTEM)
     * This function is kept for backward compatibility but should not be used in new code.
     * Use renderStep1, renderStep2, renderStep3 instead.
     * 
     * Rol bazlı sağ/sol hizalama:
     * - Initiator (isStarter=true): Adımlar 2,4 sağda (kullanıcı), 1,3,5 solda (karşı taraf)
     * - Offerer (isStarter=false): Adımlar 1,3,5 sağda (kullanıcı), 2,4 solda (karşı taraf)
     * 
     * @deprecated Use renderStep1, renderStep2, renderStep3 instead
     */
    function renderStep(stepNum, icon, title, done, current, canComplete, isStarter, farmCode, sessionId = null) {
        let statusClass = 'locked';
        if (done) {
            statusClass = 'completed';
        } else if (current) {
            statusClass = 'current';
        }
        
        // Rol bazlı sağ/sol hizalama
        let isUserStep = false;
        if (isStarter) {
            // Initiator: Adımlar 2, 4 kullanıcıya yakın (sağ)
            isUserStep = (stepNum === 2 || stepNum === 4);
        } else {
            // Offerer: Adımlar 1, 3, 5 kullanıcıya yakın (sağ)
            isUserStep = (stepNum === 1 || stepNum === 3 || stepNum === 5);
        }
        const alignmentClass = isUserStep ? 'step-user' : 'step-counterpart';
        
        let actionHtml = '';
        if (canComplete) {
            const sessionAttr = sessionId ? `data-session-id="${sessionId}"` : '';
            actionHtml = `<button type="button" class="btn-step-complete" data-step="${stepNum}" ${sessionAttr}>Tamamla</button>`;
        } else if (current && !canComplete) {
            actionHtml = '<div class="step-waiting">⏳ Bekleniyor</div>';
        } else if (done) {
            actionHtml = '<div class="step-done">✅ Tamamlandı</div>';
        } else {
            actionHtml = '<div class="step-locked">🔒 Kilitli</div>';
        }
        
        // Çiftlik kodu görünürlüğü
        const farmCodeHtml = (stepNum === 1 && current && canComplete && farmCode && !isStarter) 
            ? `<div class="step-farm-code">Çiftlik Kodu: <strong>${escapeHtml(farmCode)}</strong> <button type="button" class="btn-copy-code" data-code="${escapeHtml(farmCode)}">📋</button></div>`
            : (stepNum === 2 && current && canComplete && farmCode && isStarter)
            ? `<div class="step-farm-code">Çiftlik Kodu: <strong>${escapeHtml(farmCode)}</strong> <button type="button" class="btn-copy-code" data-code="${escapeHtml(farmCode)}">📋</button></div>`
            : '';
        
        return `
            <div class="gift-detail-step step-${statusClass} ${alignmentClass}" data-step="${stepNum}">
                <div class="step-info">
                    <span class="step-icon">${icon}</span>
                    <div class="step-details">
                        <div class="step-label">Adım ${stepNum}/5</div>
                        <div class="step-title">${escapeHtml(title)}</div>
                        ${farmCodeHtml}
                    </div>
                    ${done ? '<span class="step-check">✅</span>' : ''}
                </div>
                <div class="step-action">
                    ${actionHtml}
                </div>
            </div>
        `;
    }
    
    /**
     * Complete a step
     */
    /**
     * Complete step (DEPRECATED - OLD 5-STEP SYSTEM)
     * This function is kept for backward compatibility but should not be used in new code.
     * Use handleShareFarmCode, handleSendFriendRequest, handleAcceptFriendRequest,
     * handleMarkGiftReady, handleMarkGiftCollected, handleCompleteTrade instead.
     * 
     * @deprecated Use new 3-step system handlers instead
     */
    function completeStep(sessionId, step) {
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_complete_trade_step',
                nonce: config.nonce,
                session_id: sessionId,
                step: step,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Adım tamamlandı!', 'success');
                // Reload all trades (since we show all trades directly now)
                loadActiveTrades();
            } else {
                showToast(data.data?.message || 'Adım tamamlanamadı', 'error');
            }
        })
        .catch(error => {
            console.error('Error completing step:', error);
            showToast('Bir hata oluştu', 'error');
        });
    }
    
    /**
     * ============================================
     * NEW 3-STEP SYSTEM EVENT HANDLERS
     * ============================================
     */
    
    /**
     * Handle share farm code
     */
    function handleShareFarmCode(sessionId, farmCode) {
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_share_farm_code',
                nonce: config.nonce,
                session_id: sessionId,
                farm_code: farmCode,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Çiftlik kodu paylaşıldı!', 'success');
                loadActiveTrades();
            } else {
                showToast(data.data?.message || 'Çiftlik kodu paylaşılamadı', 'error');
            }
        })
        .catch(error => {
            console.error('Error sharing farm code:', error);
            showToast('Bir hata oluştu', 'error');
        });
    }
    
    /**
     * Handle send friend request
     */
    function handleSendFriendRequest(sessionId) {
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_send_friend_request',
                nonce: config.nonce,
                session_id: sessionId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('İstek gönderildi!', 'success');
                loadActiveTrades();
            } else {
                showToast(data.data?.message || 'İstek gönderilemedi', 'error');
            }
        })
        .catch(error => {
            console.error('Error sending friend request:', error);
            showToast('Bir hata oluştu', 'error');
        });
    }
    
    /**
     * Handle accept friend request
     */
    function handleAcceptFriendRequest(sessionId) {
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_accept_friend_request',
                nonce: config.nonce,
                session_id: sessionId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('İstek kabul edildi!', 'success');
                loadActiveTrades();
            } else {
                showToast(data.data?.message || 'İstek kabul edilemedi', 'error');
            }
        })
        .catch(error => {
            console.error('Error accepting friend request:', error);
            showToast('Bir hata oluştu', 'error');
        });
    }
    
    /**
     * Handle mark gift ready
     */
    function handleMarkGiftReady(sessionId) {
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_mark_gift_ready',
                nonce: config.nonce,
                session_id: sessionId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Hediye hazır olarak işaretlendi!', 'success');
                loadActiveTrades();
            } else {
                showToast(data.data?.message || 'İşaretlenemedi', 'error');
            }
        })
        .catch(error => {
            console.error('Error marking gift ready:', error);
            showToast('Bir hata oluştu', 'error');
        });
    }
    
    /**
     * Handle mark gift collected
     */
    function handleMarkGiftCollected(sessionId) {
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_mark_gift_collected',
                nonce: config.nonce,
                session_id: sessionId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Hediye alındı olarak işaretlendi!', 'success');
                loadActiveTrades();
            } else {
                showToast(data.data?.message || 'İşaretlenemedi', 'error');
            }
        })
        .catch(error => {
            console.error('Error marking gift collected:', error);
            showToast('Bir hata oluştu', 'error');
        });
    }
    
    /**
     * Handle complete trade
     */
    function handleCompleteTrade(sessionId) {
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_complete_trade_new',
                nonce: config.nonce,
                session_id: sessionId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.data?.message || 'Hediyeleşme tamamlandı!', 'success');
                loadActiveTrades();
            } else {
                showToast(data.data?.message || 'Tamamlanamadı', 'error');
            }
        })
        .catch(error => {
            console.error('Error completing trade:', error);
            showToast('Bir hata oluştu', 'error');
        });
    }
    
    /**
     * Send ping
     */
    function sendPing(sessionId) {
        const btn = document.getElementById(`btn-ping-${sessionId}`);
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Gönderiliyor...';
        }
        
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_send_trade_ping',
                nonce: config.nonce,
                session_id: sessionId,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Ping gönderildi!', 'success');
            } else {
                showToast(data.data?.message || 'Ping gönderilemedi', 'error');
            }
            
            if (btn) {
                btn.disabled = false;
                btn.textContent = '📨 Ping / Kontrol Et';
            }
        })
        .catch(error => {
            console.error('Error sending ping:', error);
            showToast('Bir hata oluştu', 'error');
            
            if (btn) {
                btn.disabled = false;
                btn.textContent = '📨 Ping / Kontrol Et';
            }
        });
    }
    
    /**
     * Open report modal
     */
    function openReportModal(sessionId) {
        const modal = document.getElementById('trade-report-modal');
        const sessionInput = document.getElementById('trade-report-session-id');
        
        if (!modal || !sessionInput) {
            showToast('Rapor formu yüklenemedi', 'error');
            return;
        }
        
        sessionInput.value = sessionId;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Reset form
        const form = document.getElementById('trade-report-form');
        if (form) {
            form.reset();
            const charCount = document.getElementById('trade-report-char-count');
            if (charCount) charCount.textContent = '0';
        }
    }
    
    /**
     * Close report modal
     */
    function closeReportModal() {
        const modal = document.getElementById('trade-report-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            
            // Reset form
            const form = document.getElementById('trade-report-form');
            if (form) {
                form.reset();
                const charCount = document.getElementById('trade-report-char-count');
                if (charCount) charCount.textContent = '0';
            }
        }
    }
    
    /**
     * Initialize report modal handlers
     */
    function initReportModal() {
        const modal = document.getElementById('trade-report-modal');
        if (!modal) {
            return;
        }
        
        // Close button
        const closeBtn = document.getElementById('trade-report-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeReportModal);
        }
        
        // Cancel button
        const cancelBtn = document.getElementById('btn-cancel-report');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeReportModal);
        }
        
        // Overlay click
        const overlay = modal.querySelector('.report-modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeReportModal);
        }
        
        // Form submission
        const form = document.getElementById('trade-report-form');
        if (form) {
            form.addEventListener('submit', handleReportSubmit);
        }
        
        // Character counter
        const description = document.getElementById('trade-report-description');
        if (description) {
            description.addEventListener('input', function() {
                const charCount = document.getElementById('trade-report-char-count');
                if (charCount) {
                    charCount.textContent = this.value.length;
                }
            });
        }
    }
    
    /**
     * Handle report form submission
     */
    function handleReportSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const sessionId = form.querySelector('[name="session_id"]').value;
        const issueType = form.querySelector('[name="issue_type"]:checked')?.value;
        const description = form.querySelector('[name="description"]').value;
        
        if (!issueType) {
            showToast('Lütfen bir sorun tipi seçin', 'error');
            return;
        }
        
        const submitBtn = form.querySelector('#btn-submit-report');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Gönderiliyor...';
        }
        
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hdh_create_trade_report',
                nonce: config.nonce,
                session_id: sessionId,
                issue_type: issueType,
                description: description,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Rapor gönderildi!', 'success');
                closeReportModal();
            } else {
                showToast(data.data?.message || 'Rapor gönderilemedi', 'error');
            }
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Gönder';
            }
        })
        .catch(error => {
            console.error('Error submitting report:', error);
            showToast('Bir hata oluştu', 'error');
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Gönder';
            }
        });
    }
    
    /**
     * Start polling for detail view
     */
    let detailPollTimer = null;
    function startDetailPolling(sessionId) {
        if (detailPollTimer) {
            clearInterval(detailPollTimer);
        }
        
        detailPollTimer = setInterval(() => {
            if (currentDetailSessionId === sessionId && isPanelOpen) {
                fetch(config.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'hdh_get_trade_session',
                        nonce: config.nonce,
                        session_id: sessionId,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.session) {
                        const session = data.data.session;
                        const newStep = session.current_step || 1;
                        const newStatus = session.status || 'ACTIVE';
                        
                        // Check if step or status changed
                        const currentStepEl = document.querySelector('.gift-detail-step.step-current');
                        const oldStep = currentStepEl ? parseInt(currentStepEl.getAttribute('data-step') || '1') : 1;
                        const oldStatus = document.querySelector('.gift-detail-completed') ? 'COMPLETED' : 'ACTIVE';
                        
                        if (newStep !== oldStep || newStatus !== oldStatus) {
                            // Step or status changed, reload detail
                            renderTradeDetail(session);
                            if (newStep !== oldStep) {
                                showToast('Karşı taraf bir adım tamamladı!', 'success');
                            }
                            if (newStatus === 'COMPLETED' && oldStatus !== 'COMPLETED') {
                                showToast('Hediyeleşme tamamlandı! 🎉', 'success');
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error polling trade detail:', error);
                });
            }
        }, config.pollInterval);
    }
    
    /**
     * Update badge count
     */
    function updateBadgeCount(count) {
        const badge = document.getElementById('hdh-gift-badge');
        const button = document.getElementById('hdh-gift-overlay-button');
        
        if (count > 0) {
            if (badge) {
                badge.textContent = count > 99 ? '99+' : count;
            } else if (button) {
                const newBadge = document.createElement('span');
                newBadge.className = 'gift-badge';
                newBadge.id = 'hdh-gift-badge';
                newBadge.textContent = count > 99 ? '99+' : count;
                button.appendChild(newBadge);
            }
        } else {
            if (badge) {
                badge.remove();
            }
        }
    }
    
    /**
     * Start polling for updates
     */
    function startPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        
        pollTimer = setInterval(() => {
            if (isPanelOpen) {
                loadActiveTrades();
            }
        }, config.pollInterval);
    }
    
    /**
     * Stop polling
     */
    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `gift-toast toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
    
    /**
     * ============================================
     * NEW 3-STEP SYSTEM FUNCTIONS
     * ============================================
     */
    
    /**
     * Build timeline events from session data and timeline events
     */
    function buildTimelineEvents(session, timelineEvents = []) {
        const currentUserId = parseInt(hdhGiftOverlay?.currentUserId || 0);
        const isOwner = session.is_owner || false;
        const isOfferer = session.is_starter || false;
        
        // Process timeline events and determine side
        const processedEvents = timelineEvents.map(event => {
            let side = event.side;
            
            // If side is null, calculate based on user_id
            if (!side && event.user_id) {
                const eventUserId = parseInt(event.user_id);
                if (eventUserId === currentUserId) {
                    side = 'right';
                } else {
                    side = 'left';
                }
            }
            
            // System messages are always centered
            if (event.event_type === 'system' || !event.user_id) {
                side = 'system';
            }
            
            return {
                ...event,
                side: side || 'system',
                text: event.event_data?.text || '',
                farm_code: event.event_data?.farm_code || null,
                role: event.event_data?.role || null,
            };
        });
        
        return processedEvents;
    }
    
    /**
     * Render chat timeline
     */
    function renderChatTimeline(session, timelineEvents = []) {
        const events = buildTimelineEvents(session, timelineEvents);
        
        if (events.length === 0) {
            return '<div class="chat-timeline-empty">Henüz etkinlik yok</div>';
        }
        
        let html = '<div class="chat-timeline">';
        
        events.forEach(event => {
            const sideClass = event.side || 'system';
            const timeStr = event.created_at ? new Date(event.created_at).toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' }) : '';
            
            html += `
                <div class="timeline-event ${sideClass}">
                    <div class="timeline-event-content">
                        ${event.text ? `<div class="timeline-event-text">${escapeHtml(event.text)}</div>` : ''}
                        ${event.farm_code ? `
                            <div class="timeline-event-farm-code">
                                <span>${escapeHtml(event.farm_code)}</span>
                                <button type="button" class="btn-copy-code" data-code="${escapeHtml(event.farm_code)}">Kopyala</button>
                            </div>
                        ` : ''}
                        ${timeStr ? `<div class="timeline-event-time">${timeStr}</div>` : ''}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }
    
    /**
     * Render Step 1: Friend Request
     */
    function renderStep1(session) {
        const isOwner = session.is_owner || false;
        const isOfferer = session.is_starter || false;
        const ownerFarmCode = session.owner_farm_code || '';
        const offererFarmCode = session.offerer_farm_code || '';
        const friendRequestSent = !!session.friend_request_sent_at;
        const friendRequestAccepted = !!session.friend_request_accepted_at;
        const isLocked = false; // Step 1 is always active initially
        const isCompleted = friendRequestAccepted;
        
        let html = '<div class="trade-step step-1' + (isCompleted ? ' completed' : '') + (isLocked ? ' locked' : '') + '">';
        html += '<div class="trade-step-header">';
        html += '<span class="trade-step-icon">👥</span>';
        html += '<span class="trade-step-title">Adım 1: Arkadaş ekleme</span>';
        html += '</div>';
        
        html += '<div class="trade-step-content">';
        
        // Owner farm code display
        if (ownerFarmCode) {
            html += `
                <div class="farm-code-display">
                    <div class="farm-code-text">Beni arkadaş olarak ekle. Çiftlik kodum: ${escapeHtml(ownerFarmCode)}</div>
                    <button type="button" class="btn-copy-code" data-code="${escapeHtml(ownerFarmCode)}">Kopyala</button>
                </div>
            `;
        }
        
        // Offerer farm code sharing
        if (isOfferer && !offererFarmCode) {
            html += `
                <div class="farm-code-input">
                    <input type="text" id="offerer-farm-code-${session.id}" placeholder="Çiftlik kodunuzu girin (örn: #ABC123)" maxlength="20">
                    <button type="button" class="btn-share-farm-code" data-session-id="${session.id}">Paylaş</button>
                </div>
            `;
        } else if (offererFarmCode) {
            html += `
                <div class="farm-code-display">
                    <div class="farm-code-text">Ekliyorum. Çiftlik kodum: ${escapeHtml(offererFarmCode)}</div>
                    <button type="button" class="btn-copy-code" data-code="${escapeHtml(offererFarmCode)}">Kopyala</button>
                </div>
            `;
        }
        
        // Buttons
        html += '<div class="trade-step-actions">';
        if (isOfferer && !friendRequestSent) {
            html += `<button type="button" class="btn-send-friend-request" data-session-id="${session.id}">İstek gönderdim</button>`;
        }
        if (isOwner && friendRequestSent && !friendRequestAccepted) {
            html += `<button type="button" class="btn-accept-friend-request" data-session-id="${session.id}">Kabul ettim</button>`;
        }
        if (isCompleted) {
            html += '<div class="step-completed-badge">✅ Tamamlandı</div>';
        }
        html += '</div>';
        
        // Sub-actions (always visible)
        html += '<div class="trade-step-sub-actions">';
        html += `<button type="button" class="btn-ping-trade" data-session-id="${session.id}">📨 Kullanıcıya titreşim gönder</button>`;
        html += `<button type="button" class="btn-report-issue" data-session-id="${session.id}" id="btn-report-step1-${session.id}">⚠️ Şikayet et</button>`;
        html += '</div>';
        
        html += '</div>'; // trade-step-content
        html += '</div>'; // trade-step
        
        return html;
    }
    
    /**
     * Render Step 2: Prepare Gift
     */
    function renderStep2(session) {
        const isOwner = session.is_owner || false;
        const isOfferer = session.is_starter || false;
        const friendRequestAccepted = !!session.friend_request_accepted_at;
        const readyOwner = !!session.ready_owner_at;
        const readyOfferer = !!session.ready_offerer_at;
        const isLocked = !friendRequestAccepted;
        const isCompleted = readyOwner && readyOfferer;
        const canMarkReady = friendRequestAccepted && ((isOwner && !readyOwner) || (isOfferer && !readyOfferer));
        
        let html = '<div class="trade-step step-2' + (isCompleted ? ' completed' : '') + (isLocked ? ' locked' : '') + '">';
        html += '<div class="trade-step-header">';
        html += '<span class="trade-step-icon">🎁</span>';
        html += '<span class="trade-step-title">Adım 2: Hediye hazırlama</span>';
        if (isLocked) {
            html += '<span class="trade-step-locked-badge">🔒 Kilitli</span>';
        }
        html += '</div>';
        
        html += '<div class="trade-step-content">';
        html += '<div class="trade-step-text">Hediyenizi hazırlayıp dükkana koyun</div>';
        
        // Buttons
        html += '<div class="trade-step-actions">';
        if (canMarkReady) {
            html += `<button type="button" class="btn-mark-gift-ready" data-session-id="${session.id}">Hediyen hazır</button>`;
        }
        if (isCompleted) {
            html += '<div class="step-completed-badge">✅ Tamamlandı</div>';
        }
        html += '</div>';
        
        // Sub-actions (always visible)
        html += '<div class="trade-step-sub-actions">';
        html += `<button type="button" class="btn-ping-trade" data-session-id="${session.id}">📨 Kullanıcıya titreşim gönder</button>`;
        html += `<button type="button" class="btn-report-issue" data-session-id="${session.id}" id="btn-report-step2-${session.id}">⚠️ Şikayet et</button>`;
        html += '</div>';
        
        html += '</div>'; // trade-step-content
        html += '</div>'; // trade-step
        
        return html;
    }
    
    /**
     * Render Step 3: Collect Gift
     */
    function renderStep3(session) {
        const isOwner = session.is_owner || false;
        const isOfferer = session.is_starter || false;
        const readyOwner = !!session.ready_owner_at;
        const readyOfferer = !!session.ready_offerer_at;
        const collectedOwner = !!session.collected_owner_at;
        const collectedOfferer = !!session.collected_offerer_at;
        const isLocked = !readyOwner || !readyOfferer;
        const isCompleted = collectedOwner && collectedOfferer;
        const canMarkCollected = !isLocked && ((isOwner && !collectedOwner) || (isOfferer && !collectedOfferer));
        
        let html = '<div class="trade-step step-3' + (isCompleted ? ' completed' : '') + (isLocked ? ' locked' : '') + '">';
        html += '<div class="trade-step-header">';
        html += '<span class="trade-step-icon">📦</span>';
        html += '<span class="trade-step-title">Adım 3: Hediyeni al</span>';
        if (isLocked) {
            html += '<span class="trade-step-locked-badge">🔒 Kilitli</span>';
        }
        html += '</div>';
        
        html += '<div class="trade-step-content">';
        html += '<div class="trade-step-text">Senin için hazırlanan hediyeni al</div>';
        
        // Buttons
        html += '<div class="trade-step-actions">';
        if (canMarkCollected) {
            html += `<button type="button" class="btn-mark-gift-collected" data-session-id="${session.id}">Aldım</button>`;
        }
        if (isCompleted) {
            html += '<div class="step-completed-badge">✅ Tamamlandı</div>';
        }
        html += '</div>';
        
        // Sub-actions (always visible)
        html += '<div class="trade-step-sub-actions">';
        html += `<button type="button" class="btn-ping-trade" data-session-id="${session.id}">📨 Kullanıcıya titreşim gönder</button>`;
        html += `<button type="button" class="btn-report-issue" data-session-id="${session.id}" id="btn-report-step3-${session.id}">⚠️ Şikayet et</button>`;
        html += '</div>';
        
        html += '</div>'; // trade-step-content
        html += '</div>'; // trade-step
        
        return html;
    }
    
    /**
     * Render status badge
     */
    function renderStatusBadge(session) {
        const statusLabel = session.status_label || 'Aktif';
        const statusClass = session.status === 'COMPLETED' ? 'status-completed' : 
                           session.status === 'DISPUTED' ? 'status-disputed' : 
                           session.reported_at ? 'status-disputed' : 'status-active';
        
        return `<span class="trade-status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>`;
    }
    
    /**
     * Update report button timer
     */
    function updateReportButtonTimer(session) {
        if (!session.report_unlock_at) {
            return;
        }
        
        const unlockTime = new Date(session.report_unlock_at).getTime();
        const currentTime = new Date().getTime();
        const remaining = Math.max(0, Math.floor((unlockTime - currentTime) / 1000));
        
        // Update all report buttons for this session
        document.querySelectorAll(`[id^="btn-report"][data-session-id="${session.id}"], [id^="btn-report-step"][data-session-id="${session.id}"]`).forEach(btn => {
            if (remaining > 0) {
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                const timeStr = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                btn.disabled = true;
                btn.innerHTML = `⚠️ Şikayet et (${timeStr})`;
            } else {
                btn.disabled = false;
                btn.innerHTML = '⚠️ Şikayet et';
            }
        });
    }
    
    // Global function for back button
    window.hdhGiftOverlayBackToList = function() {
        currentDetailSessionId = null;
        loadActiveTrades();
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            init();
            initReportModal();
        });
    } else {
        init();
        initReportModal();
    }
})();

