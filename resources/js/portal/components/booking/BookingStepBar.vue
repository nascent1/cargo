<template>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-4">
    <div class="flex items-center justify-between">
      <div v-for="(s, idx) in steps" :key="idx" class="flex items-center" :class="idx < steps.length - 1 ? 'flex-1' : ''">
        <!-- Step circle -->
        <div class="flex items-center gap-2.5">
          <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-extrabold shrink-0 transition-all duration-300"
            :class="stepClass(idx)">
            <i v-if="idx < currentStep - 1" class="fas fa-check"></i>
            <span v-else>{{ idx + 1 }}</span>
          </div>
          <span class="text-xs font-semibold hidden sm:block transition-colors duration-300"
            :class="idx < currentStep ? 'text-slate-800' : 'text-slate-400'">
            {{ s }}
          </span>
        </div>
        <!-- Connector line -->
        <div v-if="idx < steps.length - 1" class="flex-1 mx-3">
          <div class="h-0.5 rounded-full transition-all duration-500"
            :class="idx < currentStep - 1 ? 'bg-brand-500' : 'bg-slate-200'">
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  steps: { type: Array, required: true },
  currentStep: { type: Number, required: true },
});

function stepClass(idx) {
  if (idx < props.currentStep - 1) {
    return 'bg-brand-600 text-white shadow-sm shadow-brand-200';
  } else if (idx === props.currentStep - 1) {
    return 'bg-brand-600 text-white ring-4 ring-brand-100 shadow-sm shadow-brand-200';
  }
  return 'bg-slate-100 text-slate-400 border border-slate-200';
}
</script>
