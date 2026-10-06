<template>
  <div class="space-y-5">
    <!-- Shift Context Bar -->
    <ShiftContextBar v-if="data.shift" :shift="data.shift" />

    <!-- Attention Required -->
    <AttentionRequired :items="data.attention_items || []" />

    <!-- Live Voyage Board -->
    <VoyageBoardTable :voyages="data.voyage_board || []" />

    <!-- Session Activity Feed -->
    <SessionActivityFeed :items="data.session_feed || []" @reprint="handleReprint" />
  </div>
</template>

<script setup>
import { useUiStore } from '../../stores/ui';
import ShiftContextBar from '../dashboard/ShiftContextBar.vue';
import AttentionRequired from '../dashboard/AttentionRequired.vue';
import VoyageBoardTable from '../dashboard/VoyageBoardTable.vue';
import SessionActivityFeed from '../dashboard/SessionActivityFeed.vue';

defineProps({
  data: { type: Object, default: () => ({}) },
});

const uiStore = useUiStore();

function handleReprint(ticket) {
  const url = ticket.ticket_number
    ? `/portal/api/tickets/${ticket.ticket_number}/print`
    : null;
  if (url) {
    uiStore.notify(
      `Sending [${ticket.ticket_number}] to printer...`,
      'Printing Ticket',
      'info'
    );
    window.open(url, '_blank');
  } else {
    uiStore.notify('Could not print ticket', 'Error', 'error');
  }
}
</script>
