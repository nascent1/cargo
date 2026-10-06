import { createApp, reactive, h } from 'vue';
import SeatLayoutEditor from './components/SeatLayoutEditor.vue';
import SeatLayoutViewer from '../portal/components/ticketing/SeatLayoutViewer.vue';

const mountEl = document.getElementById('layout-editor-app');
if (mountEl) {
    const app = createApp(SeatLayoutEditor, {
        ferry: JSON.parse(mountEl.dataset.ferry || '{}'),
        layoutJson: JSON.parse(mountEl.dataset.layout || '{}'),
        saveUrl: mountEl.dataset.saveUrl || ''
    });

    app.mount('#layout-editor-app');
}

// Global Bridge to mount the Vue Viewer inside Alpine/Blade
window.mountAdminSeatViewer = function(containerId, onSeatSelected) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error("mountAdminSeatViewer: Container not found", containerId);
        return null;
    }

    // Reactive state holder
    const state = reactive({
        layoutJson: null,
        bookedSeats: [],
        selectedSeats: [],
        targetClassId: null,
        allowedRoomIds: [],
        loading: false
    });

    const app = createApp({
        render() {
            return h(SeatLayoutViewer, {
                layoutJson: state.layoutJson,
                bookedSeats: state.bookedSeats,
                selectedSeats: state.selectedSeats,
                targetClassId: state.targetClassId,
                allowedRoomIds: state.allowedRoomIds,
                loading: state.loading,
                onSeatSelected: (seatId) => {
                    if (typeof onSeatSelected === 'function') {
                        onSeatSelected(seatId);
                    }
                }
            });
        }
    });

    app.mount(container);

    return {
        updateState(newState) {
            Object.assign(state, newState);
        },
        destroy() {
            app.unmount();
        }
    };
};
