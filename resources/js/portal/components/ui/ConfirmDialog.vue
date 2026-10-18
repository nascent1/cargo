<template>
  <!-- Confirm dialog: opened with `await uiStore.confirm({...})` (same look as the admin portal's) -->
  <transition name="cd">
    <div v-if="o" class="fixed inset-0 z-[1100] flex items-end sm:items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]" aria-hidden="true" @click="answer(false)"></div>

      <div ref="panel" role="alertdialog" aria-modal="true" aria-labelledby="confirm-dialog-title"
        :aria-describedby="o.message ? 'confirm-dialog-message' : undefined" @keydown.tab="trap"
        class="cd-panel relative w-full max-w-md bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden text-left">

        <div class="px-5 pt-5 sm:px-6 sm:pt-6 flex items-start gap-4">
          <div class="shrink-0 h-11 w-11 rounded-xl ring-1 ring-inset flex items-center justify-center text-lg" :class="tone.tile" aria-hidden="true">
            <i class="fas" :class="o.icon || tone.icon"></i>
          </div>
          <div class="min-w-0 flex-1 pt-0.5">
            <h2 id="confirm-dialog-title" class="text-base font-semibold text-slate-900 leading-snug break-words">{{ o.title || 'Are you sure?' }}</h2>
            <p v-if="o.message" id="confirm-dialog-message" class="mt-1 text-sm text-slate-600 leading-relaxed break-words">{{ o.message }}</p>
          </div>
        </div>

        <p v-if="o.note" class="px-5 sm:px-6 mt-3 text-xs text-slate-500 leading-relaxed">{{ o.note }}</p>

        <div class="mt-5 px-5 sm:px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
          <button ref="cancelBtn" type="button" @click="answer(false)"
            class="h-10 px-4 rounded-xl text-sm font-semibold border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-300">
            {{ o.cancelText }}
          </button>
          <button ref="okBtn" type="button" @click="answer(true)" :class="tone.button"
            class="h-10 px-4 rounded-xl text-sm font-semibold text-white shadow-sm inline-flex items-center justify-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2">
            <i v-if="o.confirmIcon" class="fas text-xs" :class="o.confirmIcon" aria-hidden="true"></i>
            {{ o.confirmText }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, watch, nextTick, onUnmounted } from 'vue';
import { useUiStore } from '../../stores/ui';

const TONES = {
  primary: { tile: 'bg-brand-50 text-brand-600 ring-brand-100', button: 'bg-brand-600 hover:bg-brand-700 focus-visible:ring-brand-400', icon: 'fa-circle-info', safeFocus: false },
  success: { tile: 'bg-emerald-50 text-emerald-600 ring-emerald-100', button: 'bg-emerald-700 hover:bg-emerald-800 focus-visible:ring-emerald-400', icon: 'fa-circle-check', safeFocus: false },
  warning: { tile: 'bg-amber-50 text-amber-600 ring-amber-100', button: 'bg-amber-700 hover:bg-amber-800 focus-visible:ring-amber-400', icon: 'fa-triangle-exclamation', safeFocus: true },
  danger: { tile: 'bg-rose-50 text-rose-600 ring-rose-100', button: 'bg-rose-600 hover:bg-rose-700 focus-visible:ring-rose-400', icon: 'fa-trash-can', safeFocus: true },
};

const uiStore = useUiStore();
const o = computed(() => uiStore.confirmDialog);
const tone = computed(() => TONES[o.value?.tone] || TONES.primary);
const panel = ref(null);
const okBtn = ref(null);
const cancelBtn = ref(null);
let opener = null;

const answer = (value) => uiStore.answerConfirm(value);
const onKey = (e) => { if (e.key === 'Escape') { e.preventDefault(); answer(false); } };

// Keep Tab inside the dialog
function trap(e) {
  const f = [...panel.value.querySelectorAll('button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')];
  if (!f.length) return;
  const first = f[0], last = f[f.length - 1];
  if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
  else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
}

watch(o, async (now, before) => {
  if (now && !before) {
    opener = document.activeElement;
    document.documentElement.style.overflow = 'hidden';
    window.addEventListener('keydown', onKey);
  }
  if (now) {
    // Destructive questions start on the safe button, so a stray Enter can't do harm
    await nextTick();
    (tone.value.safeFocus ? cancelBtn.value : okBtn.value)?.focus();
  } else if (before) {
    document.documentElement.style.overflow = '';
    window.removeEventListener('keydown', onKey);
    opener?.focus?.({ preventScroll: true });
    opener = null;
  }
});

onUnmounted(() => {
  window.removeEventListener('keydown', onKey);
  document.documentElement.style.overflow = '';
});
</script>

<style scoped>
.cd-enter-active, .cd-leave-active { transition: opacity 0.15s ease; }
.cd-enter-active .cd-panel, .cd-leave-active .cd-panel { transition: transform 0.15s ease; }
.cd-enter-from, .cd-leave-to { opacity: 0; }
.cd-enter-from .cd-panel, .cd-leave-to .cd-panel { transform: translateY(12px); }
@media (min-width: 640px) {
  .cd-enter-from .cd-panel, .cd-leave-to .cd-panel { transform: scale(0.95); }
}
@media (prefers-reduced-motion: reduce) {
  .cd-enter-active, .cd-leave-active, .cd-enter-active .cd-panel, .cd-leave-active .cd-panel { transition: none; }
}
</style>
