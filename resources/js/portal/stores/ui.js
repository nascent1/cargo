import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUiStore = defineStore('ui', () => {
    const showNotificationModal = ref(false);
    const notificationTitle = ref('');
    const notificationMessage = ref('');
    const notificationType = ref('error'); // 'error', 'success', 'info'

    const notify = (message, title = 'Notification', type = 'info') => {
        notificationMessage.value = message;
        notificationTitle.value = title;
        notificationType.value = type;
        showNotificationModal.value = true;
    };

    return {
        showNotificationModal,
        notificationTitle,
        notificationMessage,
        notificationType,
        notify
    };
});
