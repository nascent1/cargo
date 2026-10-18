{{--
    Shared look for the cargo booking screens: the admin booking page and the sales portal (Vue).
    Both pages run the Tailwind CDN with the same brand scale, so these classes render identically.
    Greys are Tailwind's slate (the two portals define "surface" differently).
--}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style type="text/tailwindcss">
    @layer components {
        .ui-scope { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        .ui-card { @apply bg-white rounded-2xl border border-slate-200; }
        .ui-card-head { @apply px-5 py-3 border-b border-slate-100 flex items-center justify-between gap-3; }
        .ui-btn { @apply inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl text-sm font-semibold border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed; }
        .ui-btn-primary { @apply inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl text-sm font-semibold bg-brand-600 text-white hover:bg-brand-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed; }
        .ui-btn-success { @apply inline-flex items-center justify-center gap-2 h-10 px-5 rounded-xl text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed; }
        .ui-btn-sm { @apply h-8 px-3 text-xs; }
        .ui-link { @apply text-sm font-semibold text-brand-700 hover:underline; }
        .ui-label { @apply block text-xs font-semibold text-slate-700 mb-1; }
        .ui-hint { @apply text-[11px] text-slate-500 mt-1; }
        .ui-error { @apply text-xs text-red-700 mt-1; }
        .ui-field { @apply block w-full h-10 px-3 rounded-xl border border-slate-300 bg-white text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-200 focus:border-brand-500 transition-colors; }
        textarea.ui-field { @apply h-auto py-2; }
        .ui-field-invalid { @apply border-red-400 focus:ring-red-100 focus:border-red-500; }
        .ui-badge { @apply inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border whitespace-nowrap; }
        .ui-chip { @apply inline-flex items-center gap-1.5 h-9 px-3.5 rounded-full text-sm font-semibold border border-slate-300 bg-white text-slate-700 hover:border-slate-400 transition-colors; }
        .ui-chip-on { @apply border-brand-600 bg-brand-600 text-white hover:border-brand-600; }
        .ui-overline { @apply text-[11px] font-semibold uppercase tracking-wide text-slate-500; }
        .ui-day { @apply px-4 pt-4 pb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500; }
        .ui-step { @apply flex items-center gap-2 px-2.5 h-8 rounded-xl text-xs font-semibold transition-colors; }
        .ui-step-dot { @apply h-5 w-5 rounded-full flex items-center justify-center text-[10px] font-bold; }
        .ui-departure { @apply w-full px-3 py-2.5 rounded-xl flex items-center gap-4 text-left transition-colors hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent; }
    }
</style>
