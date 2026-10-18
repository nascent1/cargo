<template>
  <span class="ui-badge" :class="b.cls">
    <i v-if="b.icon" :class="['fas', b.icon, 'text-[9px]']"></i>{{ b.label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { badge } from '../../utils/cargoUi';

// Same labels and colours as the admin portal's badges
const props = defineProps({
  kind: { type: String, default: 'status' }, // 'status' | 'payment'
  value: { type: String, default: '' },
});

const b = computed(() => badge(props.kind, props.value));
</script>
