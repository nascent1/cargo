<template>
  <span class="block">
    <span class="block h-1.5 rounded-full bg-slate-200 overflow-hidden">
      <span class="block h-full rounded-full" :class="info.bar" :style="{ width: Math.max(info.pct, 2) + '%' }"></span>
    </span>
    <span class="block text-[11px] mt-1" :class="info.cls">{{ info.text }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { spaceInfo } from '../../utils/cargoUi';

// How full a departure is: one bar for its fullest dimension (weight, volume or deck)
const props = defineProps({ space: { type: Object, default: null } });
const info = computed(() => spaceInfo(props.space));
</script>
