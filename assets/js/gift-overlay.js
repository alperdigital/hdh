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
     * Render all trades with full detail view
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
            
            const currentStep = session.current_step || 1;
            const isStarter = session.is_starter || false;
            const isOwner = session.is_owner || false;
            
            // Determine which steps are done
            const step1Done = !!session.step1_starter_done_at;
            const step2Done = !!session.step2_owner_done_at;
            const step3Done = !!session.step3_starter_done_at;
            const step4Done = !!session.step4_owner_done_at;
            const step5Done = !!session.step5_starter_done_at;
            
            // Determine which step can be completed
            let canCompleteStep = null;
            if (currentStep === 1 && isStarter && !step1Done) {
                canCompleteStep = 1;
            } else if (currentStep === 2 && isOwner && !step2Done) {
                canCompleteStep = 2;
            } else if (currentStep === 3 && isStarter && !step3Done) {
                canCompleteStep = 3;
            } else if (currentStep === 4 && isOwner && !step4Done) {
                canCompleteStep = 4;
            } else if (currentStep === 5 && isStarter && !step5Done) {
                canCompleteStep = 5;
            }
            
            const ownerFarmCode = session.owner_farm_code || '';
            const starterFarmCode = session.starter_farm_code || '';
            const levelDigits = String(trade.counterpart_level || 1).length;
            const levelClass = `lvl-d${levelDigits}`;
            const actionBadge = trade.requires_action ? '<span class="gift-trade-action-badge">Aksiyon Gerekli</span>' : '';
            
            html += `
                <div class="gift-trade-detailed-item" data-session-id="${session.id}">
                    <div class="gift-trade-detailed-header">
                        <h4 class="gift-trade-detailed-title">${escapeHtml(listing.title || 'İlan')}</h4>
                        ${actionBadge}
                    </div>
                    
                    <div class="gift-trade-detailed-counterpart">
                        <a href="/profil?user=${trade.counterpart_id}" class="gift-trade-user">
                            <div class="hdh-level-badge ${levelClass}" aria-label="Seviye ${trade.counterpart_level}">
                                ${trade.counterpart_level || 1}
                            </div>
                            <span class="gift-trade-farm-name">${escapeHtml(trade.counterpart_name || 'Bilinmeyen')}</span>
                            <span class="gift-trade-presence">${escapeHtml(trade.counterpart_presence || '3+ gün önce')}</span>
                        </a>
                    </div>
                    
                    <div class="gift-trade-detailed-summary">
                        <div class="summary-item">
                            <span class="summary-label">İstediği:</span>
                            <span class="summary-value">${escapeHtml(listing.wanted_item || 'N/A')}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Verebileceği:</span>
                            <span class="summary-value">${escapeHtml(listing.offer_items || 'N/A')}</span>
                        </div>
                    </div>
                    
                    <div class="gift-trade-detailed-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${(currentStep / 5) * 100}%"></div>
                        </div>
                        <span class="progress-text">${currentStep} / 5 adım tamamlandı</span>
                    </div>
                    
                    <div class="gift-trade-detailed-steps">
                        ${renderStep(1, '👥', 'Arkadaş olarak ekle', step1Done, currentStep === 1 && !step1Done, canCompleteStep === 1, isStarter, ownerFarmCode, session.id)}
                        ${renderStep(2, '✅', 'Arkadaşlık isteğini kabul edin', step2Done, currentStep === 2 && !step2Done, canCompleteStep === 2, isOwner, starterFarmCode, session.id)}
                        ${renderStep(3, '🎁', 'Vereceğiniz hediyeyi hazırlayın', step3Done, currentStep === 3 && !step3Done, canCompleteStep === 3, isStarter, '', session.id)}
                        ${renderStep(4, '📦', 'Hediyeni al ve hediyeni hazırla', step4Done, currentStep === 4 && !step4Done, canCompleteStep === 4, isOwner, '', session.id)}
                        ${renderStep(5, '🎉', 'Hediyeni al', step5Done, currentStep === 5 && !step5Done, canCompleteStep === 5, isStarter, '', session.id)}
                    </div>
                    
                    ${session.status === 'COMPLETED' ? '<div class="gift-trade-detailed-completed">✅ Hediyeleşme tamamlandı!</div>' : ''}
                    
                    <div class="gift-trade-detailed-actions">
                        <button type="button" class="btn-ping-trade" data-session-id="${session.id}" id="btn-ping-${session.id}">
                            📨 Ping / Kontrol Et
                        </button>
                        <button type="button" class="btn-report-issue" data-session-id="${session.id}" id="btn-report-${session.id}">
                            ⚠️ Sorun Bildir
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        content.innerHTML = html;
        
        // Add event listeners for all trades
        document.querySelectorAll('.btn-step-complete').forEach(btn => {
            btn.addEventListener('click', function() {
                const step = parseInt(this.getAttribute('data-step'));
                const sessionId = parseInt(this.getAttribute('data-session-id'));
                completeStep(sessionId, step);
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
     * Render trade detail content
     */
    function renderTradeDetailContent(session, listing) {
        const content = document.getElementById('hdh-gift-overlay-content');
        if (!content) {
            return;
        }
        
        const currentStep = session.current_step || 1;
        const status = session.status || 'ACTIVE';
        const isStarter = session.is_starter || false;
        const isOwner = session.is_owner || false;
        
        // Determine which steps are done
        const step1Done = !!session.step1_starter_done_at;
        const step2Done = !!session.step2_owner_done_at;
        const step3Done = !!session.step3_starter_done_at;
        const step4Done = !!session.step4_owner_done_at;
        const step5Done = !!session.step5_starter_done_at;
        
        // Determine which step can be completed
        let canCompleteStep = null;
        if (currentStep === 1 && isStarter && !step1Done) {
            canCompleteStep = 1;
        } else if (currentStep === 2 && isOwner && !step2Done) {
            canCompleteStep = 2;
        } else if (currentStep === 3 && isStarter && !step3Done) {
            canCompleteStep = 3;
        } else if (currentStep === 4 && isOwner && !step4Done) {
            canCompleteStep = 4;
        } else if (currentStep === 5 && isStarter && !step5Done) {
            canCompleteStep = 5;
        }
        
        // Get farm codes
        const ownerFarmCode = session.owner_farm_code || '';
        const starterFarmCode = session.starter_farm_code || '';
        
        let html = `
            <div class="gift-trade-detail">
                <div class="gift-detail-header">
                    <button type="button" class="btn-back-to-list" onclick="hdhGiftOverlayBackToList()">
                        ← Listeye Dön
                    </button>
                    <h3 class="gift-detail-title">${escapeHtml(listing.title || 'İlan')}</h3>
                </div>
                
                <div class="gift-detail-summary">
                    <div class="summary-item">
                        <span class="summary-label">İstediği:</span>
                        <span class="summary-value">${escapeHtml(listing.wanted_item || 'N/A')}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Verebileceği:</span>
                        <span class="summary-value">${escapeHtml(listing.offer_items || 'N/A')}</span>
                    </div>
                </div>
                
                <div class="gift-detail-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${(currentStep / 5) * 100}%"></div>
                    </div>
                    <span class="progress-text">${currentStep} / 5 adım tamamlandı</span>
                </div>
                
                <div class="gift-detail-steps">
                    ${renderStep(1, '👥', 'Arkadaş olarak ekle', step1Done, currentStep === 1 && !step1Done, canCompleteStep === 1, isStarter, ownerFarmCode)}
                    ${renderStep(2, '✅', 'Arkadaşlık isteğini kabul edin', step2Done, currentStep === 2 && !step2Done, canCompleteStep === 2, isOwner, starterFarmCode)}
                    ${renderStep(3, '🎁', 'Vereceğiniz hediyeyi hazırlayın', step3Done, currentStep === 3 && !step3Done, canCompleteStep === 3, isStarter, '')}
                    ${renderStep(4, '📦', 'Hediyeni al ve hediyeni hazırla', step4Done, currentStep === 4 && !step4Done, canCompleteStep === 4, isOwner, '')}
                    ${renderStep(5, '🎉', 'Hediyeni al', step5Done, currentStep === 5 && !step5Done, canCompleteStep === 5, isStarter, '')}
                </div>
                
                ${status === 'COMPLETED' ? '<div class="gift-detail-completed">✅ Hediyeleşme tamamlandı!</div>' : ''}
                
                <div class="gift-detail-actions">
                    <button type="button" class="btn-ping-trade" data-session-id="${session.id}" id="btn-ping-${session.id}">
                        📨 Ping / Kontrol Et
                    </button>
                    <button type="button" class="btn-report-issue" data-session-id="${session.id}" id="btn-report-${session.id}">
                        ⚠️ Sorun Bildir
                    </button>
                </div>
            </div>
        `;
        
        content.innerHTML = html;
        
        // Add event listeners
        document.querySelectorAll('.btn-step-complete').forEach(btn => {
            btn.addEventListener('click', function() {
                const step = parseInt(this.getAttribute('data-step'));
                completeStep(session.id, step);
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
        
        // Start polling for this detail view
        startDetailPolling(session.id);
    }
    
    /**
     * Render a step
     */
    function renderStep(stepNum, icon, title, done, current, canComplete, isUserTurn, farmCode, sessionId = null) {
        let statusClass = 'locked';
        if (done) {
            statusClass = 'completed';
        } else if (current) {
            statusClass = 'current';
        }
        
        let actionHtml = '';
        if (canComplete) {
            const sessionAttr = sessionId ? `data-session-id="${sessionId}"` : '';
            actionHtml = `<button type="button" class="btn-step-complete" data-step="${stepNum}" ${sessionAttr}>Tamamla</button>`;
        } else if (current && !canComplete) {
            actionHtml = '<div class="step-waiting">⏳ Karşı tarafın işlemi bekleniyor...</div>';
        } else if (done) {
            actionHtml = '<div class="step-done">✅ Tamamlandı</div>';
        } else {
            actionHtml = '<div class="step-locked">🔒 Kilitli</div>';
        }
        
        const farmCodeHtml = (stepNum === 1 && current && canComplete && farmCode) 
            ? `<div class="step-farm-code">Çiftlik Kodu: <strong>${escapeHtml(farmCode)}</strong> <button type="button" class="btn-copy-code" data-code="${escapeHtml(farmCode)}">📋</button></div>`
            : (stepNum === 2 && current && canComplete && farmCode)
            ? `<div class="step-farm-code">Çiftlik Kodu: <strong>${escapeHtml(farmCode)}</strong></div>`
            : '';
        
        return `
            <div class="gift-detail-step step-${statusClass}" data-step="${stepNum}">
                <div class="step-header">
                    <span class="step-icon">${icon}</span>
                    <span class="step-title">${escapeHtml(title)}</span>
                    ${done ? '<span class="step-check">✅</span>' : ''}
                </div>
                ${farmCodeHtml}
                <div class="step-action">
                    ${actionHtml}
                </div>
            </div>
        `;
    }
    
    /**
     * Complete a step
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

