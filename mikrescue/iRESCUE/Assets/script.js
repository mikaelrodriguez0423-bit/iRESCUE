/* ================================================
   iRescue v2.0 — Main Application Script
   ================================================ */

'use strict';

// =============================================
// 1. THEME (Dark / Light Mode)
// =============================================
const Theme = (() => {
    const KEY = 'irescue-theme';
    const root = document.documentElement;

    function apply(theme) {
        root.setAttribute('data-theme', theme);
        localStorage.setItem(KEY, theme);
        document.querySelectorAll('.theme-icon-sun, .theme-icon-moon').forEach(el => {
            el.style.display = 'none';
        });
        const icon = document.querySelector(theme === 'dark' ? '.theme-icon-sun' : '.theme-icon-moon');
        if (icon) icon.style.display = 'block';
    }

    function toggle() {
        const current = root.getAttribute('data-theme') || 'light';
        apply(current === 'dark' ? 'light' : 'dark');
    }

    function init() {
        const saved = localStorage.getItem(KEY) || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        apply(saved);
        document.getElementById('theme-toggle')?.addEventListener('click', toggle);
    }

    return { init, toggle, apply };
})();

// =============================================
// 2. TOAST NOTIFICATIONS
// =============================================
const Toast = (() => {
    let container;

    function getContainer() {
        if (!container) {
            container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                document.body.appendChild(container);
            }
        }
        return container;
    }

    const ICONS = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️',
    };

    function show(message, type = 'info', duration = 4000) {
        const c = getContainer();
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${ICONS[type] || ICONS.info}</span>
            <span class="toast-msg">${message}</span>
        `;
        c.appendChild(toast);

        const dismiss = () => {
            toast.classList.add('hiding');
            toast.addEventListener('animationend', () => toast.remove(), { once: true });
        };
        toast.addEventListener('click', dismiss);
        setTimeout(dismiss, duration);
        return toast;
    }

    return {
        success: (m, d) => show(m, 'success', d),
        error: (m, d) => show(m, 'error', d),
        warning: (m, d) => show(m, 'warning', d),
        info: (m, d) => show(m, 'info', d),
    };
})();

// =============================================
// 3. MOBILE SIDEBAR
// =============================================
const Sidebar = (() => {
    function init() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const toggleBtn = document.getElementById('mobile-toggle');

        function open() {
            sidebar?.classList.add('open');
            overlay?.classList.add('open');
        }
        function close() {
            sidebar?.classList.remove('open');
            overlay?.classList.remove('open');
        }

        toggleBtn?.addEventListener('click', () => {
            sidebar?.classList.contains('open') ? close() : open();
        });
        overlay?.addEventListener('click', close);

        // Close on nav link click (mobile)
        sidebar?.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', () => { if (window.innerWidth < 769) close(); });
        });
    }
    return { init };
})();

// =============================================
// 4. MODAL SYSTEM
// =============================================
const Modal = (() => {
    function open(id) {
        const overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.classList.add('open');
        overlay.querySelector('.modal')?.querySelector('input, select, textarea')?.focus();
        document.body.style.overflow = 'hidden';
    }

    function close(id) {
        const overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    function init() {
        // Open triggers
        document.querySelectorAll('[data-modal-open]').forEach(btn => {
            btn.addEventListener('click', () => open(btn.dataset.modalOpen));
        });

        // Close triggers (button inside modal)
        document.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => close(btn.dataset.modalClose || btn.closest('.modal-overlay')?.id));
        });

        // Click outside
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) close(overlay.id);
            });
        });

        // ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.open').forEach(o => close(o.id));
            }
        });
    }

    return { open, close, init };
})();

// =============================================
// 5. CRUD MODAL (Manage Hotlines / Locations)
// =============================================
const CrudModal = (() => {
    function init() {
        // Edit button triggers
        document.querySelectorAll('.btn-edit-row').forEach(btn => {
            btn.addEventListener('click', () => {
                const data = JSON.parse(btn.dataset.entity || '{}');
                const type = btn.dataset.type || '';
                const form = document.getElementById('crud-form');
                const title = document.getElementById('crud-modal-title');
                const action = form?.getAttribute('data-base-action') || '';

                if (title) title.textContent = `Edit ${type}`;
                if (form) form.action = `${action}?action=edit`;

                // Populate fields
                Object.keys(data).forEach(key => {
                    const field = form?.querySelector(`[name="${key}"]`);
                    if (field) field.value = data[key];
                });

                Modal.open('crud-modal-overlay');
            });
        });

        // Add button
        document.getElementById('btn-add-new')?.addEventListener('click', () => {
            const form = document.getElementById('crud-form');
            const title = document.getElementById('crud-modal-title');
            const type = document.getElementById('btn-add-new')?.dataset.type || '';
            const action = form?.getAttribute('data-base-action') || '';

            if (title) title.textContent = `Add New ${type}`;
            if (form) {
                form.action = `${action}?action=add`;
                form.reset();
                const idField = form.querySelector('[name="id"]');
                if (idField) idField.value = '';
            }

            Modal.open('crud-modal-overlay');
        });
    }
    return { init };
})();

// =============================================
// 6. TABLE SEARCH, FILTER & SORT
// =============================================
const DataTable = (() => {
    function init(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const rows = () => Array.from(table.querySelectorAll('tbody tr'));
        const searchInput = document.querySelector(`[data-table="${tableId}"][data-search]`);
        const filterSelects = document.querySelectorAll(`[data-table="${tableId}"][data-filter-col]`);
        const sortHeaders = table.querySelectorAll('th[data-sort]');
        let sortState = { col: -1, dir: 'asc' };

        function applyFilters() {
            const q = (searchInput?.value || '').toLowerCase().trim();
            const filters = [];
            filterSelects.forEach(sel => {
                if (sel.value) filters.push({ col: parseInt(sel.dataset.filterCol), val: sel.value.toLowerCase() });
            });

            rows().forEach(row => {
                const cells = Array.from(row.querySelectorAll('td'));
                const text = cells.map(c => c.textContent.toLowerCase()).join(' ');
                const matchQ = !q || text.includes(q);
                const matchF = filters.every(f => (cells[f.col]?.textContent.toLowerCase().trim() || '') === f.val);
                row.style.display = (matchQ && matchF) ? '' : 'none';
            });
        }

        searchInput?.addEventListener('input', applyFilters);
        filterSelects.forEach(s => s.addEventListener('change', applyFilters));

        sortHeaders.forEach(th => {
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => {
                const colIdx = parseInt(th.dataset.sort);
                if (sortState.col === colIdx) {
                    sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    sortState = { col: colIdx, dir: 'asc' };
                }
                sortHeaders.forEach(h => h.removeAttribute('aria-sort'));
                th.setAttribute('aria-sort', sortState.dir);

                const tbody = table.querySelector('tbody');
                const sorted = rows().sort((a, b) => {
                    const av = a.querySelectorAll('td')[colIdx]?.textContent.trim().toLowerCase() || '';
                    const bv = b.querySelectorAll('td')[colIdx]?.textContent.trim().toLowerCase() || '';
                    return sortState.dir === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
                });
                sorted.forEach(r => tbody.appendChild(r));
            });
        });
    }

    return { init };
})();

// =============================================
// 7. AJAX STATUS UPDATE (Responder Hub)
// =============================================
const StatusUpdater = (() => {
    function init() {
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', async function () {
                const reportId = this.dataset.reportId;
                const newStatus = this.value;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                try {
                    const res = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: new URLSearchParams({ action: 'update_status', report_id: reportId, status: newStatus, csrf_token: csrf }),
                    });
                    const data = await res.json();
                    if (data.success) {
                        Toast.success(`Status updated to <strong>${newStatus}</strong>`);
                        // Update badge in same row
                        const row = this.closest('tr');
                        const badge = row?.querySelector('.badge');
                        if (badge) {
                            badge.className = `badge badge-${newStatus.toLowerCase()}`;
                            badge.textContent = newStatus;
                        }
                    } else {
                        Toast.error(data.message || 'Update failed.');
                    }
                } catch (err) {
                    Toast.error('Network error. Please try again.');
                }
            });
        });
    }
    return { init };
})();

// =============================================
// 8. PASSWORD STRENGTH CHECKER
// =============================================
const PasswordStrength = (() => {
    function score(pw) {
        let s = 0;
        if (pw.length >= 8) s++;
        if (pw.length >= 12) s++;
        if (/[A-Z]/.test(pw)) s++;
        if (/[0-9]/.test(pw)) s++;
        if (/[^A-Za-z0-9]/.test(pw)) s++;
        return s; // 0-5
    }

    const LEVELS = [
        { label: 'Very Weak', color: '#e63946', width: '10%' },
        { label: 'Weak', color: '#e63946', width: '25%' },
        { label: 'Fair', color: '#fb8500', width: '50%' },
        { label: 'Good', color: '#3a86ff', width: '75%' },
        { label: 'Strong', color: '#2dc653', width: '100%' },
    ];

    function init() {
        const pwInput = document.getElementById('password');
        const bar = document.getElementById('strength-bar');
        const label = document.getElementById('strength-label');
        if (!pwInput || !bar) return;

        pwInput.addEventListener('input', () => {
            const s = Math.min(score(pwInput.value), 4);
            const lvl = LEVELS[s];
            bar.style.width = lvl.width;
            bar.style.background = lvl.color;
            if (label) { label.textContent = lvl.label; label.style.color = lvl.color; }
        });
    }
    return { init };
})();

// =============================================
// 9. CONFIRM DELETE
// =============================================
function confirmDelete(e) {
    if (!confirm('Are you sure you want to delete this? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
    return true;
}

// =============================================
// 10. REAL-TIME DASHBOARD REFRESH (optional)
// =============================================
const LiveStats = (() => {
    async function refresh() {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch('api.php?action=stats', {
                headers: { 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (!data) return;
            ['pending', 'responding', 'resolved', 'total'].forEach(key => {
                const el = document.getElementById(`stat-${key}`);
                if (el && data[key] !== undefined) el.textContent = data[key];
            });
        } catch (_) { /* silent */ }
    }

    function init(intervalMs = 30000) {
        if (!document.getElementById('stat-pending')) return;
        refresh();
        setInterval(refresh, intervalMs);
    }

    return { init, refresh };
})();

// =============================================
// 11. GEOLOCATION (Report Form)
// =============================================
const Geo = (() => {
    function init() {
        const latField = document.getElementById('lat');
        const lngField = document.getElementById('lng');
        const warning = document.getElementById('location-warning');
        if (!latField || !lngField) return;

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                pos => {
                    latField.value = pos.coords.latitude.toFixed(6);
                    lngField.value = pos.coords.longitude.toFixed(6);
                },
                () => { if (warning) warning.style.display = 'block'; }
            );
        } else {
            if (warning) warning.style.display = 'block';
        }
    }
    return { init };
})();

// =============================================
// 12. CHART.JS DASHBOARD ANALYTICS
// =============================================
const DashboardCharts = (() => {
    const DEFAULTS = {
        font: { family: "'DM Sans', sans-serif", size: 12 },
        color: '#94a3b8',
    };

    function categoryChart(id, data) {
        const ctx = document.getElementById(id);
        if (!ctx || !window.Chart) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: ['#e63946', '#3a86ff', '#2dc653', '#fb8500', '#a855f7', '#64748b'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: DEFAULTS.color, font: DEFAULTS.font, padding: 14, usePointStyle: true, pointStyleWidth: 8 }
                    },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} reports` } }
                }
            }
        });
    }

    function trendChart(id, data) {
        const ctx = document.getElementById(id);
        if (!ctx || !window.Chart) return;

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Reports',
                    data: data.values,
                    borderColor: '#e63946',
                    backgroundColor: 'rgba(230,57,70,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#e63946',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: DEFAULTS.color, font: DEFAULTS.font } },
                    y: { grid: { color: gridColor }, ticks: { color: DEFAULTS.color, font: DEFAULTS.font, stepSize: 1 }, beginAtZero: true }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#1e293b', titleColor: '#f8fafc', bodyColor: '#94a3b8', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1 }
                }
            }
        });
    }

    return { categoryChart, trendChart };
})();

// =============================================
// 13. FORM VALIDATION
// =============================================
const FormValidator = (() => {
    function init(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', (e) => {
            let valid = true;
            form.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    valid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            // Email validation
            form.querySelectorAll('[type="email"]').forEach(field => {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (field.value && !re.test(field.value)) {
                    field.classList.add('is-invalid');
                    valid = false;
                }
            });
            // Password match
            const pw1 = form.querySelector('[name="password"]');
            const pw2 = form.querySelector('[name="confirm_password"]');
            if (pw1 && pw2 && pw1.value !== pw2.value) {
                pw2.classList.add('is-invalid');
                Toast.error('Passwords do not match.');
                valid = false;
            }
            if (!valid) {
                e.preventDefault();
                Toast.error('Please fix the highlighted fields.');
            }
        });

        // Live clear on input
        form.querySelectorAll('.form-control').forEach(field => {
            field.addEventListener('input', () => field.classList.remove('is-invalid'));
        });
    }
    return { init };
})();

// =============================================
// 14. EXPORT HELPERS (CSV download via link)
// =============================================
function triggerExport(format) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    window.location.href = `export.php?format=${format}&csrf_token=${encodeURIComponent(csrf)}`;
}

// =============================================
// 15. INITIALIZE ON DOM READY
// =============================================
document.addEventListener('DOMContentLoaded', () => {
    Theme.init();
    Sidebar.init();
    Modal.init();
    CrudModal.init();
    PasswordStrength.init();
    StatusUpdater.init();
    LiveStats.init();
    Geo.init();
    FormValidator.init('login-form');
    FormValidator.init('register-form');
    FormValidator.init('report-form');
    FormValidator.init('post-alert-form');
    DataTable.init('incidents-table');
    DataTable.init('users-table');
    DataTable.init('hotlines-table');
    DataTable.init('locations-table');
    DataTable.init('logs-table');

    // Delete confirm
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', confirmDelete);
    });

    // Flash messages from PHP → Toast
    document.querySelectorAll('[data-toast]').forEach(el => {
        const type = el.dataset.toast;
        const msg = el.dataset.message;
        if (type && msg) Toast[type]?.(msg);
        el.remove();
    });
});