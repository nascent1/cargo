// Tanzanian mobile numbers. Network prefixes come from each push channel's `prefixes`
// (served by /payment-methods, sourced from App\Support\Msisdn) — not hard-coded here.

export function normalizeMsisdn(value) {
    const d = String(value || '').replace(/\D/g, '');
    const local = d.startsWith('255') && d.length === 12 ? d.slice(3)
        : d.startsWith('0') && d.length === 10 ? d.slice(1)
        : d.length === 9 ? d
        : null;
    return local && /^[67]\d{8}$/.test(local) ? '255' + local : null;
}

// Offered USSD channel whose network owns the number, or null
export function channelForNumber(methods, msisdn) {
    const n = normalizeMsisdn(msisdn);
    if (!n) return null;
    const prefix = n.slice(3, 5);
    return methods.find(m => m.flow === 'ussd_push' && (m.prefixes || []).includes(prefix)) || null;
}

// Guidance under the payer field: { text, tone: 'ok' | 'warn' | 'error' | '' , match }
export function payerHint(methods, chosen, msisdn) {
    if (!msisdn) return { text: '', tone: '', match: null };
    if (!normalizeMsisdn(msisdn)) return { text: 'Enter a valid Tanzanian mobile number.', tone: 'error', match: null };
    const match = channelForNumber(methods, msisdn);
    const chosenIsNetworkSpecific = (chosen?.prefixes || []).length > 0;
    if (!chosenIsNetworkSpecific) {
        return { text: match ? `${match.name} number ✓` : 'Number accepted.', tone: 'ok', match };
    }
    if (match?.slug === chosen.slug) return { text: `${chosen.name} number ✓`, tone: 'ok', match };
    return {
        text: match ? `This is a ${match.name} number — switch to ${match.name}.` : `${chosen.name} can't push to this number — use Selcom Pesa or Lipa Namba.`,
        tone: 'error',
        match,
    };
}
