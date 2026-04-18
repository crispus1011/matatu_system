// ============================================
// EC Matatu System - Main JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', function () {

    // ── Start live polling if applicable ──
    startNotificationPolling(30000);

    // ── Auto-hide alerts ──
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.5s';
            setTimeout(() => el.remove(), 500);
        }, 5000);
    });

    // ── Hamburger sidebar toggle ──
    const ham = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    if (ham && sidebar) {
        ham.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !ham.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ── Modal system ──
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.modal;
            openModal(id);
        });
    });

    document.querySelectorAll('.modal-close, [data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) closeModal(modal.id);
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay.id);
        });
    });

    // ── Tabs ──
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.closest('.tab-group') || btn.parentElement;
            const target = btn.dataset.tab;
            group.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const container = btn.closest('.tabs').nextElementSibling || document;
            container.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(target)?.classList.add('active');
        });
    });

    // ── Rating stars ──
    document.querySelectorAll('.stars').forEach(starContainer => {
        const stars = starContainer.querySelectorAll('.star');
        const input = starContainer.parentElement.querySelector('input[name="rating"]');
        stars.forEach((star, i) => {
            star.addEventListener('click', () => {
                if (input) input.value = i + 1;
                stars.forEach((s, j) => s.classList.toggle('active', j <= i));
            });
        });
    });

    // ── Confirm delete ──
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm(btn.dataset.confirm || 'Are you sure?')) e.preventDefault();
        });
    });

    // ── Table search filter ──
    const tableSearch = document.getElementById('tableSearch');
    if (tableSearch) {
        tableSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.searchable-table tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    // ── Form loading state ──
    document.querySelectorAll('form.loading-form').forEach(form => {
        form.addEventListener('submit', function () {
            const btn = this.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;"></span> Processing...';
            }
        });
    });

    // ── Seat selection ──
    initSeatMap();

    // ── Payment method selection ──
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.addEventListener('click', () => {
            document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            const method = opt.dataset.method;
            const input = document.getElementById('paymentMethod');
            if (input) input.value = method;
            // Toggle Mpesa code field
            const mpesaField = document.getElementById('mpesaCodeField');
            if (mpesaField) mpesaField.style.display = method === 'mpesa' ? 'block' : 'none';
        });
    });

    // ── Counter animation ──
    document.querySelectorAll('.counter').forEach(el => {
        const target = parseInt(el.dataset.target);
        let current = 0;
        const step = Math.ceil(target / 50);
        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = current.toLocaleString();
            if (current >= target) clearInterval(timer);
        }, 30);
    });

});

// ── Modal helpers ──
function openModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('show'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('show'); document.body.style.overflow = ''; }
}

// ── Seat map init ──
function initSeatMap() {
    const map = document.getElementById('seatMap');
    if (!map) return;
    const selectedInput = document.getElementById('selectedSeat');
    const bookBtn = document.getElementById('bookBtn');

    map.querySelectorAll('.seat.available').forEach(seat => {
        seat.addEventListener('click', () => {
            map.querySelectorAll('.seat.available').forEach(s => s.classList.remove('selected'));
            seat.classList.add('selected');
            if (selectedInput) selectedInput.value = seat.dataset.seat;
            if (bookBtn) {
                bookBtn.disabled = false;
                bookBtn.textContent = 'Book Seat ' + seat.dataset.seat;
            }
        });
    });
}

// ── Toast notifications ──
function showToast(msg, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = `position:fixed;top:80px;right:20px;z-index:9999;max-width:320px;animation:slideIn .3s ease;box-shadow:0 4px 20px rgba(0,0,0,0.15);`;
    toast.innerHTML = `<span>${msg}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 4000);
}

// ── AJAX booking ──
async function processBooking(formData) {
    try {
        const res = await fetch('api/book.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.href = 'my_bookings.php', 1500);
        } else {
            showToast(data.message || 'Booking failed', 'error');
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
    }
}

// ── Chart helper ──
function renderBarChart(canvasId, labels, data, label = 'Data') {
    const ctx = document.getElementById(canvasId);
    if (!ctx || typeof Chart === 'undefined') return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label,
                data,
                backgroundColor: '#1a6b3c',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

function renderLineChart(canvasId, labels, data, label = 'Data') {
    const ctx = document.getElementById(canvasId);
    if (!ctx || typeof Chart === 'undefined') return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label,
                data,
                borderColor: '#1a6b3c',
                backgroundColor: 'rgba(26,107,60,0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#1a6b3c',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

// ── Live notification badge polling ──
function startNotificationPolling(intervalMs = 30000) {
    if (!document.querySelector('.badge-notif') && !document.querySelector('[data-notif-badge]')) return;
    setInterval(async () => {
        try {
            const res = await fetch('../api/notifications.php');
            if (!res.ok) return;
            const data = await res.json();
            const badge = document.querySelector('.badge-notif, [data-notif-badge]');
            if (badge && data.unread_count !== undefined) {
                badge.textContent = data.unread_count;
                badge.style.display = data.unread_count > 0 ? 'inline' : 'none';
            }
        } catch (e) { /* silent */ }
    }, intervalMs);
}

// ── Live seat availability refresh ──
function startSeatRefresh(tripId, intervalMs = 15000) {
    if (!tripId || !document.getElementById('seatMap')) return;
    setInterval(async () => {
        try {
            const res = await fetch(`../api/seats.php?trip_id=${tripId}`);
            const data = await res.json();
            if (!data.success) return;
            data.booked_seats.forEach(seat => {
                const el = document.querySelector(`.seat[data-seat="${seat}"]`);
                if (el && el.classList.contains('available') && !el.classList.contains('selected')) {
                    el.classList.remove('available');
                    el.classList.add('booked');
                }
            });
            const info = document.getElementById('seatAvailInfo');
            if (info) info.textContent = `${data.available_seats} / ${data.total_seats} seats available`;
        } catch (e) { /* silent */ }
    }, intervalMs);
}

function renderDoughnutChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx || typeof Chart === 'undefined') return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: ['#1a6b3c','#f5a623','#3b5bdb','#e74c3c','#28a869'],
                borderWidth: 2,
            }]
        },
        options: { responsive: true, cutout: '65%' }
    });
}
