(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        const countdownEl = document.getElementById('lottery-countdown');
        if (!countdownEl) return;
        const targetDate = new Date('2025-12-21T17:00:00Z');
        function updateCountdown() {
            const now = new Date();
            const diff = targetDate - now;
            if (diff <= 0) {
                document.getElementById('countdown-days').textContent = '0';
                document.getElementById('countdown-hours').textContent = '0';
                document.getElementById('countdown-minutes').textContent = '0';
                return;
            }
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            document.getElementById('countdown-days').textContent = days;
            document.getElementById('countdown-hours').textContent = hours;
            document.getElementById('countdown-minutes').textContent = minutes;
        }
        updateCountdown();
        setInterval(updateCountdown, 60000);
    });
})();
