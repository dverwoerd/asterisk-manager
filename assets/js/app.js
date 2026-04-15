/* ============================================================
   Asterisk Manager - JavaScript
   ============================================================ */

'use strict';

// ---- Clock ----
function updateClock() {
    const el = document.getElementById('topbarClock');
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(updateClock, 1000);
updateClock();

// ---- Mobile Sidebar Toggle ----
document.addEventListener('DOMContentLoaded', function () {
    const sidebar       = document.getElementById('sidebar');
    const mobileToggle  = document.getElementById('mobileToggle');
    const sidebarToggle = document.getElementById('sidebarToggle');

    function openSidebar() { sidebar?.classList.add('open'); }
    function closeSidebar() { sidebar?.classList.remove('open'); }

    mobileToggle?.addEventListener('click', function () {
        sidebar?.classList.toggle('open');
    });

    sidebarToggle?.addEventListener('click', closeSidebar);

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (e) {
        if (window.innerWidth <= 900 && sidebar?.classList.contains('open')) {
            if (!sidebar.contains(e.target) && e.target !== mobileToggle) {
                closeSidebar();
            }
        }
    });

    // ---- Auto-dismiss alerts ----
    document.querySelectorAll('.alert-success').forEach(function (el) {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 4000);
    });

    // ---- Confirm delete on all delete buttons ----
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });

    // ---- Dynamic member add/remove in forms (queues, ring groups) ----
    const addMemberBtn = document.getElementById('addMember');
    if (addMemberBtn) {
        addMemberBtn.addEventListener('click', function () {
            const container  = document.getElementById('membersContainer');
            const template   = container.querySelector('.member-row');
            if (!template) return;
            const clone = template.cloneNode(true);
            // Clear values
            clone.querySelectorAll('input, select').forEach(el => {
                if (el.tagName === 'SELECT') el.selectedIndex = 0;
                else el.value = '';
            });
            container.appendChild(clone);
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-member')) {
                const container = document.getElementById('membersContainer');
                if (container.querySelectorAll('.member-row').length > 1) {
                    e.target.closest('.member-row').remove();
                }
            }
        });
    }

    // ---- Add dial pattern rows (outbound routes) ----
    const addPatternBtn = document.getElementById('addPattern');
    if (addPatternBtn) {
        addPatternBtn.addEventListener('click', function () {
            const container = document.getElementById('patternsContainer');
            const template  = container.querySelector('.pattern-row');
            if (!template) return;
            const clone = template.cloneNode(true);
            clone.querySelectorAll('input').forEach(el => el.value = '');
            container.appendChild(clone);
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-pattern')) {
                const container = document.getElementById('patternsContainer');
                if (container.querySelectorAll('.pattern-row').length > 1) {
                    e.target.closest('.pattern-row').remove();
                }
            }
        });
    }

    // ---- Password strength indicator ----
    document.querySelectorAll('input[name="secret"], input[name="password"]').forEach(function (input) {
        const indicator = document.createElement('div');
        indicator.className = 'password-strength';
        input.parentNode.insertBefore(indicator, input.nextSibling);

        input.addEventListener('input', function () {
            const val = input.value;
            let strength = 0;
            if (val.length >= 8)  strength++;
            if (val.length >= 12) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            const colors = ['', '#ef4444', '#f59e0b', '#f59e0b', '#22c55e', '#00d4aa'];
            const labels = ['', 'Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            indicator.textContent = val.length > 0 ? labels[strength] : '';
            indicator.style.color = colors[strength];
            indicator.style.fontSize = '11px';
            indicator.style.marginTop = '3px';
        });
    });

    // ---- Number format preview in rate plans ----
    const rateInput = document.getElementById('rate_per_minute');
    if (rateInput) {
        rateInput.addEventListener('input', function () {
            const preview = document.getElementById('ratePreview');
            if (!preview) return;
            const rate = parseFloat(rateInput.value.replace(',', '.')) || 0;
            preview.textContent = '1 min = € ' + (rate).toFixed(4) + ' | 5 min = € ' + (rate * 5).toFixed(4);
        });
    }

    // ---- Queue/Ring Group real-time member count ----
    updateMemberCount();
    document.addEventListener('change', updateMemberCount);

    function updateMemberCount() {
        const badge = document.getElementById('memberCountBadge');
        if (!badge) return;
        const rows = document.querySelectorAll('#membersContainer .member-row');
        badge.textContent = rows.length + ' member' + (rows.length !== 1 ? 's' : '');
    }

    // ---- Reload warning ----
    let formDirty = false;
    document.querySelectorAll('form input, form select, form textarea').forEach(function (el) {
        el.addEventListener('change', () => { formDirty = true; });
    });
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', () => { formDirty = false; });
    });
    window.addEventListener('beforeunload', function (e) {
        if (formDirty) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes.';
        }
    });
});

// ---- Global Utilities ----
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard');
    });
}

function showToast(msg, type = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        background: var(--bg-card); border: 1px solid var(--border);
        padding: 10px 16px; border-radius: 4px;
        font-family: var(--font-mono); font-size: 12px;
        color: ${type === 'success' ? 'var(--success)' : 'var(--danger)'};
        box-shadow: var(--shadow-lg);
        animation: slideIn 0.2s ease;
    `;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// ---- API Helpers ----
async function apiPost(url, data) {
    const form = new FormData();
    Object.entries(data).forEach(([k, v]) => form.append(k, v));
    // Add CSRF token
    const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value;
    if (csrfToken) form.append('_csrf_token', csrfToken);
    const r = await fetch(url, { method: 'POST', body: form });
    return r.json();
}
