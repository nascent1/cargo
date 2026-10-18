// Helpers shared by the sales portal's cargo screens. They mirror window.CargoUI on the admin
// booking page, so both portals format, validate and label things the same way.

export const num = (v, dp = 0) => Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: dp, maximumFractionDigits: dp });
export const money = (v, currency = 'TZS') => `${currency} ${num(v, currency === 'TZS' ? 0 : 2)}`;
export const pct = v => Number(v || 0).toFixed(2).replace(/\.?0+$/, '');

// Local calendar date (YYYY-MM-DD) — toISOString() would give the UTC date
export const isoDate = (d = new Date()) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

// "Today · Tue 6 Oct", "Tomorrow · Wed 7 Oct", "Thu 8 Oct" (short: "Today" / "Tomorrow" / "Thu 8 Oct")
export function dayLabel(iso, short = false) {
    if (!iso) return '';
    const [y, m, d] = String(iso).slice(0, 10).split('-').map(Number);
    const day = new Date(y, m - 1, d);
    const now = new Date();
    const diff = Math.round((day - new Date(now.getFullYear(), now.getMonth(), now.getDate())) / 864e5);
    const pretty = day.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
    if (diff === 0) return short ? 'Today' : `Today · ${pretty}`;
    if (diff === 1) return short ? 'Tomorrow' : `Tomorrow · ${pretty}`;
    return pretty;
}

export const dateTime = d => d ? new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—';

export function timeAgo(d) {
    const mins = Math.max(0, Math.round((Date.now() - new Date(d).getTime()) / 60000));
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins} min ago`;
    const h = Math.round(mins / 60);
    return h < 24 ? `${h} h ago` : dateTime(d);
}

// Tanzanian mobiles become 0XXXXXXXXX; foreign numbers +<digits>; anything else null (same rule as the server)
export function normalisePhone(v) {
    const raw = String(v || '').trim(), d = raw.replace(/\D/g, '');
    const local = d.startsWith('255') && d.length === 12 ? d.slice(3) : d.startsWith('0') && d.length === 10 ? d.slice(1) : d.length === 9 ? d : null;
    if (local && /^[67]\d{8}$/.test(local)) return '0' + local;
    return raw.startsWith('+') && d.length >= 8 && d.length <= 15 ? '+' + d : null;
}

// One line about a departure's space: the fullest of weight / volume / deck
export function spaceInfo(space) {
    if (!space) return { pct: 0, text: 'No cargo space set', cls: 'text-amber-800', bar: 'bg-slate-300' };
    if (space.full) return { pct: 100, text: 'Full', cls: 'text-slate-500', bar: 'bg-slate-400' };
    const unit = { weight_kg: 'kg', volume_cbm: 'm³', lane_meters: 'm of deck' }[space.dimension];
    const free = `${num(space.free, space.free % 1 ? 1 : 0)} ${unit}`;
    if (space.pct >= 85) return { pct: space.pct, text: `${space.pct}% full · only ${free} left`, cls: 'text-red-700 font-semibold', bar: 'bg-red-600' };
    return { pct: space.pct, text: `${space.pct}% full · ${free} free`, cls: 'text-slate-600', bar: 'bg-brand-500' };
}

// Status / payment badge from the server's vocabulary (App\Support\CargoUi, passed in by portal/spa)
const VOCAB = window.CargoUiConfig || { status: {}, payment: {} };
export function badge(kind, key) {
    return VOCAB[kind]?.[key] || { label: String(key || '—').replace(/_/g, ' '), cls: 'bg-slate-100 text-slate-600 border-slate-200' };
}

// Axios error → { message, errors: { field: 'first message' } }
export function apiError(err, fallback = 'Something went wrong. Please try again.') {
    const data = err?.response?.data || {};
    const errors = Object.fromEntries(Object.entries(data.errors || {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
    return { status: err?.response?.status, message: data.message || data.error || fallback, errors };
}

export const brand = window.PortalBrand || { name: 'Cargo', platform: 'Cargo', logo: '/images/logo.png' };
