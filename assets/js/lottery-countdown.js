/**
 * HDH: Lottery Countdown Timer
 * Uses server-provided UTC time to prevent timezone issues
 */
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const countdownEl = document.getElementById('lottery-countdown');
        if (!countdownEl) return;
        
        // Get dates from data attributes (ISO 8601 UTC format)
        const lotteryDateISO = countdownEl.getAttribute('data-lottery-date');
        const serverTimeISO = countdownEl.getAttribute('data-server-time');
        
        if (!lotteryDateISO || !serverTimeISO) {
            console.error('HDH Countdown: Missing date data');
            return;
        }
        
        // Parse ISO dates (browser handles timezone conversion automatically)
        let targetDate, serverTime;
        try {
            targetDate = new Date(lotteryDateISO);
            serverTime = new Date(serverTimeISO);
        } catch (e) {
            console.error('HDH Countdown: Invalid date format', e);
            return;
        }
        
        // Validate dates
        if (isNaN(targetDate.getTime()) || isNaN(serverTime.getTime())) {
            console.error('HDH Countdown: Invalid date values');
            return;
        }
        
        // Calculate client-server time offset (in milliseconds)
        const clientTime = new Date();
        const timeOffset = clientTime.getTime() - serverTime.getTime();
        
        // Log for debugging
        console.log('HDH Countdown initialized:', {
            targetDate: targetDate.toISOString(),
            serverTime: serverTime.toISOString(),
            clientTime: clientTime.toISOString(),
            offset: timeOffset + 'ms'
        });
        
        /**
         * Update countdown display
         */
        function updateCountdown() {
            // Get current time adjusted for server offset
            const now = new Date();
            const adjustedNow = new Date(now.getTime() - timeOffset);
            
            // Calculate difference
            const diff = targetDate.getTime() - adjustedNow.getTime();
            
            // Get DOM elements
            const daysEl = document.getElementById('countdown-days');
            const hoursEl = document.getElementById('countdown-hours');
            const minutesEl = document.getElementById('countdown-minutes');
            
            if (!daysEl || !hoursEl || !minutesEl) return;
            
            // If countdown finished
            if (diff <= 0) {
                daysEl.textContent = '0';
                hoursEl.textContent = '0';
                minutesEl.textContent = '0';
                
                // Show "Çekiliş Tamamlandı" message
                const targetDateEl = document.getElementById('countdown-target-date');
                if (targetDateEl) {
                    targetDateEl.textContent = 'Çekiliş Tamamlandı!';
                    targetDateEl.style.color = 'var(--farm-green)';
                    targetDateEl.style.fontWeight = '700';
                }
                
                return;
            }
            
            // Calculate time units
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            // Update display with padding
            daysEl.textContent = days;
            hoursEl.textContent = hours;
            minutesEl.textContent = minutes;
        }
        
        // Initial update
        updateCountdown();
        
        // Update every minute (60 seconds)
        setInterval(updateCountdown, 60000);
        
        // Also update every 10 seconds for first minute (for testing/accuracy)
        let quickUpdateCount = 0;
        const quickInterval = setInterval(function() {
            updateCountdown();
            quickUpdateCount++;
            if (quickUpdateCount >= 6) { // 6 * 10s = 60s
                clearInterval(quickInterval);
            }
        }, 10000);
    });
})();
