{{--
    The admin portal's confirm dialog — use it instead of the browser's confirm(). Included once by the admin layout.

    From JavaScript (resolves true / false):
        if (await confirmDialog({ title: 'Delete this rate?', message: '…', tone: 'danger', confirmText: 'Delete' })) …
        Options: title, message, tone (primary|success|warning|danger), icon, confirmText, confirmIcon, cancelText, note,
                 items: [{ title, meta, value, tags: [{ text, tone }] }], total: { title, value }, maxItems (default 8)

    From a form (no JavaScript) — put the attributes on the <form>, or on the submit button:
        <form method="POST" … data-confirm="Message" data-confirm-title="Title" data-confirm-button="Delete"
              data-confirm-tone="danger" data-confirm-icon="fa-trash-can" data-confirm-cancel="Keep it">
--}}
<script>
(() => {
    const TONES = {
        primary: { tile: 'bg-brand-50 text-brand-600 ring-brand-100', button: 'bg-brand-600 hover:bg-brand-700 focus-visible:ring-brand-400', icon: 'fa-circle-info', safeFocus: false },
        success: { tile: 'bg-emerald-50 text-emerald-600 ring-emerald-100', button: 'bg-emerald-700 hover:bg-emerald-800 focus-visible:ring-emerald-400', icon: 'fa-circle-check', safeFocus: false },
        warning: { tile: 'bg-amber-50 text-amber-600 ring-amber-100', button: 'bg-amber-700 hover:bg-amber-800 focus-visible:ring-amber-400', icon: 'fa-triangle-exclamation', safeFocus: true },
        danger: { tile: 'bg-rose-50 text-rose-600 ring-rose-100', button: 'bg-rose-600 hover:bg-rose-700 focus-visible:ring-rose-400', icon: 'fa-trash-can', safeFocus: true },
    };
    const TAGS = {
        danger: 'bg-red-50 text-red-700 border-red-200',
        warning: 'bg-amber-50 text-amber-800 border-amber-200',
        info: 'bg-brand-50 text-brand-700 border-brand-200',
    };

    window.confirmDialogComponent = () => ({
        open: false,
        o: {},
        resolve: null,
        opener: null,
        get t() { return TONES[this.o.tone] || TONES.primary; },
        get shown() { return (this.o.items || []).slice(0, this.o.maxItems || 8); },
        get hidden() { return (this.o.items || []).length - this.shown.length; },
        tagCls(tone) { return TAGS[tone] || TAGS.info; },

        init() { window.__confirmDialogReady = true; },

        show({ opts, resolve }) {
            this.resolve?.(false); // only one at a time: a new question cancels the old one
            // Options left out (e.g. no data-confirm-cancel) keep the defaults
            const given = Object.fromEntries(Object.entries(opts || {}).filter(([, v]) => v !== undefined && v !== null && v !== ''));
            this.o = { confirmText: 'Confirm', cancelText: 'Cancel', ...given };
            this.resolve = resolve;
            this.opener = document.activeElement;
            this.open = true;
            document.documentElement.style.overflow = 'hidden';
            // Destructive questions start on the safe button, so a stray Enter can't delete anything
            this.$nextTick(() => requestAnimationFrame(() => (this.t.safeFocus ? this.$refs.cancel : this.$refs.ok)?.focus()));
        },

        close(answer) {
            if (!this.open) return;
            this.open = false;
            document.documentElement.style.overflow = '';
            const resolve = this.resolve;
            this.resolve = null;
            this.opener?.focus?.({ preventScroll: true });
            resolve?.(answer);
        },

        // Keep Tab inside the dialog
        trap(e) {
            const f = [...this.$refs.panel.querySelectorAll('button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')];
            if (!f.length) return;
            const [first, last] = [f[0], f[f.length - 1]];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        },
    });

    window.confirmDialog = (opts = {}) => {
        if (typeof opts === 'string') opts = { message: opts };
        // Before the page has started, still ask — never skip the check
        if (!window.__confirmDialogReady) {
            return Promise.resolve(window.confirm([opts.title, opts.message].filter(Boolean).join('\n\n')));
        }
        return new Promise(resolve => window.dispatchEvent(new CustomEvent('confirm-dialog', { detail: { opts, resolve } })));
    };

    // <form data-confirm="…"> (or a submit button with data-confirm): ask first, then submit as normal
    document.addEventListener('submit', async e => {
        const form = e.target;
        const src = e.submitter?.hasAttribute('data-confirm') ? e.submitter : (form.hasAttribute?.('data-confirm') ? form : null);
        if (!src || form.__confirmed) return;
        e.preventDefault();
        e.stopImmediatePropagation();

        const d = src.dataset;
        const ok = await window.confirmDialog({
            title: d.confirmTitle, message: d.confirm, tone: d.confirmTone, icon: d.confirmIcon,
            confirmText: d.confirmButton, cancelText: d.confirmCancel,
        });
        if (!ok) return;

        form.__confirmed = true;
        try {
            // requestSubmit keeps the clicked button's name/value and any @submit handlers
            form.requestSubmit ? form.requestSubmit(e.submitter || undefined) : form.submit();
        } finally {
            form.__confirmed = false;
        }
    }, true);
})();
</script>

<div x-data="confirmDialogComponent()" @confirm-dialog.window="show($event.detail)">
    <div x-show="open" x-cloak class="fixed inset-0 z-[9000] flex items-end sm:items-center justify-center p-4"
         x-transition:enter="ease-out duration-150 motion-reduce:duration-0" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-100 motion-reduce:duration-0" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]" @click="close(false)" aria-hidden="true"></div>

        <div x-ref="panel" x-show="open" role="alertdialog" aria-modal="true" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message"
             @keydown.escape.prevent.stop="close(false)" @keydown.tab="trap($event)"
             x-transition:enter="ease-out duration-150 motion-reduce:duration-0" x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-100 motion-reduce:duration-0" x-transition:leave-start="opacity-100 sm:scale-100" x-transition:leave-end="opacity-0 sm:scale-95"
             class="relative w-full max-w-md bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden text-left font-sans">

            <div class="px-5 pt-5 sm:px-6 sm:pt-6 flex items-start gap-4">
                <div class="shrink-0 h-11 w-11 rounded-xl ring-1 ring-inset flex items-center justify-center text-lg" :class="t.tile" aria-hidden="true">
                    <i class="fas" :class="o.icon || t.icon"></i>
                </div>
                <div class="min-w-0 flex-1 pt-0.5">
                    <h2 id="confirm-dialog-title" class="text-base font-semibold text-slate-900 leading-snug break-words" x-text="o.title || 'Are you sure?'"></h2>
                    <p id="confirm-dialog-message" x-show="o.message" class="mt-1 text-sm text-slate-600 leading-relaxed break-words" x-text="o.message"></p>
                </div>
            </div>

            <template x-if="shown.length">
                <div class="px-5 sm:px-6 mt-4">
                    <ul class="rounded-xl border border-slate-200 bg-slate-50 divide-y divide-slate-200 max-h-64 overflow-y-auto">
                        <template x-for="(it, i) in shown" :key="i">
                            <li class="px-3 py-2 flex items-center gap-3 text-xs">
                                {{-- The number never truncates; tags wrap under it when the row is narrow --}}
                                <div class="min-w-0 flex-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="font-mono font-semibold text-slate-900 whitespace-nowrap" x-text="it.title"></span>
                                    <template x-for="tag in (it.tags || [])">
                                        <span class="px-1.5 py-px rounded border text-[10px] font-bold uppercase tracking-wide" :class="tagCls(tag.tone)" x-text="tag.text"></span>
                                    </template>
                                </div>
                                <span class="shrink-0 text-slate-500 whitespace-nowrap" x-text="it.meta"></span>
                                <span class="shrink-0 w-16 text-right font-semibold text-slate-800 tabular-nums whitespace-nowrap" x-show="it.value" x-text="it.value"></span>
                            </li>
                        </template>
                        <li x-show="hidden > 0" class="px-3 py-2 text-xs text-slate-500" x-text="`+ ${hidden} more`"></li>
                    </ul>
                    <div x-show="o.total" class="mt-2 px-3 flex items-center gap-2 text-xs font-semibold text-slate-800">
                        <span x-text="o.total?.title"></span>
                        <span class="ml-auto tabular-nums" x-text="o.total?.value"></span>
                    </div>
                </div>
            </template>

            <p x-show="o.note" class="px-5 sm:px-6 mt-3 text-xs text-slate-500 leading-relaxed" x-text="o.note"></p>

            <div class="mt-5 px-5 sm:px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" x-ref="cancel" @click="close(false)" x-text="o.cancelText"
                        class="h-10 px-4 rounded-xl text-sm font-semibold border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-300"></button>
                <button type="button" x-ref="ok" @click="close(true)" :class="t.button"
                        class="h-10 px-4 rounded-xl text-sm font-semibold text-white shadow-sm inline-flex items-center justify-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2">
                    <i x-show="o.confirmIcon" class="fas text-xs" :class="o.confirmIcon" aria-hidden="true"></i>
                    <span x-text="o.confirmText"></span>
                </button>
            </div>
        </div>
    </div>
</div>
