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

    // Confirm dialog (components/ui/ConfirmDialog.vue) — never the browser's confirm():
    // if (!(await uiStore.confirm({ title, message, tone, icon, confirmText, cancelText }))) return;
    const confirmDialog = ref(null);
    let confirmResolve = null;

    const confirm = (opts = {}) => {
        confirmResolve?.(false); // only one at a time: a new question cancels the old one
        const given = Object.entries(typeof opts === 'string' ? { message: opts } : opts).filter(([, v]) => v !== undefined && v !== null && v !== '');
        confirmDialog.value = { confirmText: 'Confirm', cancelText: 'Cancel', ...Object.fromEntries(given) };
        return new Promise(resolve => { confirmResolve = resolve; });
    };

    const answerConfirm = (answer) => {
        const resolve = confirmResolve;
        confirmResolve = null;
        confirmDialog.value = null;
        resolve?.(answer);
    };

    return {
        confirmDialog,
        confirm,
        answerConfirm,
        showNotificationModal,
        notificationTitle,
        notificationMessage,
        notificationType,
        notify
    };
});
