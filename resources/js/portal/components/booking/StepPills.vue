<template>
  <ol class="flex items-center gap-1" aria-label="Booking steps">
    <li v-for="(s, i) in steps" :key="s" class="flex items-center gap-1">
      <button type="button" class="ui-step" :disabled="i + 1 > step || locked" @click="emit('go', i + 1)"
        :class="step === i + 1 ? 'bg-brand-600 text-white' : (step > i + 1 ? 'text-emerald-700 hover:bg-emerald-50' : 'text-slate-500')"
        :aria-current="step === i + 1 ? 'step' : null">
        <span class="ui-step-dot" :class="step === i + 1 ? 'bg-white text-brand-700' : (step > i + 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600')">
          <i v-if="step > i + 1" class="fas fa-check text-[9px]"></i><template v-else>{{ i + 1 }}</template>
        </span>
        <span class="hidden sm:inline">{{ s }}</span>
      </button>
      <span v-if="i < steps.length - 1" class="w-4 h-px bg-slate-300 hidden sm:block"></span>
    </li>
  </ol>
</template>

<script setup>
// Same step pills as the admin booking page
defineProps({
  steps: { type: Array, required: true },
  step: { type: Number, required: true },
  locked: { type: Boolean, default: false },
});
const emit = defineEmits(['go']);
</script>
