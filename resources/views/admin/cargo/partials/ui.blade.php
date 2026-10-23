{{-- Shared helpers for the admin cargo screens. Include before the page's own @push('scripts'). --}}
@once
@push('scripts')
<script>
window.CargoUI = (() => {
    // Same vocabulary as the sales portal (App\Support\CargoUi)
    const STATUS = @js(\App\Support\CargoUi::STATUS);
    const PAYMENT = @js(\App\Support\CargoUi::PAYMENT);

    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const num = (v, dp = 0) => Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: dp, maximumFractionDigits: dp });
    const money = (v, currency = 'TZS') => `${currency} ${num(v, currency === 'TZS' ? 0 : 2)}`;
    const date = d => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
    const dateTime = d => d ? new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—';
    // Local calendar date (YYYY-MM-DD) — toISOString() would give the UTC date
    const isoDate = (d = new Date()) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

    const badge = (map, key) => {
        const s = map[key] || { label: String(key || '—').replace(/_/g, ' '), cls: 'bg-surface-100 text-surface-600 border-surface-200' };
        return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border whitespace-nowrap ${s.cls}">${s.icon ? `<i class="fas ${s.icon} text-[9px]"></i>` : ''}${esc(s.label)}</span>`;
    };

    async function api(url, { method = 'GET', body, signal } = {}) {
        const res = await fetch(url, {
            method,
            signal,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: body === undefined ? undefined : JSON.stringify(body),
        });
        // A redirect or an HTML page (e.g. the sign-in page) is not an API answer
        if (res.redirected || !(res.headers.get('content-type') || '').includes('json')) {
            const err = new Error('The server asked for something first — reload the page.');
            err.status = res.status;
            err.errors = {};
            throw err;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            // The exchange rate is due: open the rate prompt (the request can be retried once it is set)
            if (res.status === 423 && data.exchange_rate_due) window.dispatchEvent(new CustomEvent('exchange-rate-due'));
            const err = new Error(data.message || `Request failed (${res.status})`);
            err.status = res.status;
            err.errors = data.errors || {};
            err.data = data; // e.g. the updated fare plan on 409 / 422
            throw err;
        }
        return data;
    }

    // Dual control answers: applied (200) or scheduled / awaiting approval (202) — tell the user which
    const notifyChange = res => window.notify?.(res?.message || 'Saved.', res?.applied === false ? 'info' : 'success');

    function debounce(fn, ms = 350) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    }

    return {
        STATUS, PAYMENT, esc, num, money, date, dateTime, isoDate, api, debounce, notifyChange,
        statusBadge: s => badge(STATUS, s),
        paymentBadge: s => badge(PAYMENT, s),
    };
})();
</script>
@endpush
@endonce
