<template>
    <div class="flex flex-col h-full w-full relative bg-slate-50 rounded-xl overflow-hidden border border-slate-200">
        <div v-if="loading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-slate-50 backdrop-blur-sm">
            <i class="fas fa-spinner fa-spin text-4xl text-cyan-500 mb-4"></i>
            <span class="text-cyan-600 font-bold">Loading Blueprint...</span>
        </div>
        
        <div v-if="!loading && layoutJson === 'processing'" class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-slate-50">
            <div class="relative mb-6">
                <i class="fas fa-microchip text-5xl text-fuchsia-400 absolute opacity-30 animate-ping"></i>
                <i class="fas fa-microchip text-5xl text-fuchsia-500 relative z-10"></i>
            </div>
            <span class="text-fuchsia-600 font-bold text-lg mb-1">AI is mapping the seat layout...</span>
            <span class="text-slate-500 text-sm">Please close this drawer and check back in a minute.</span>
        </div>

        <div v-if="!loading && !hasDeckData && layoutJson !== 'processing'" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white">
            <i class="fas fa-exclamation-triangle text-4xl text-amber-500 mb-4"></i>
            <span class="text-slate-500 font-medium">No digital layout configured for this ferry.</span>
        </div>

        <!-- Deck Selector (only shown when more than one deck) -->
        <div v-if="visibleDecks.length > 1" class="flex items-center gap-2 px-4 pt-3 pb-2 bg-white border-b border-slate-100 flex-shrink-0 overflow-x-auto">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-1 flex-shrink-0">Deck:</span>
            <button
                v-for="deck in visibleDecks"
                :key="deck.originalIndex"
                @click="switchDeck(deck.originalIndex)"
                :class="activeDeckIndex === deck.originalIndex
                    ? 'bg-cyan-600 text-white shadow-md shadow-cyan-500/30'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="px-4 py-1.5 rounded-full text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5">
                <i class="fas fa-layer-group text-[10px]"></i>
                {{ deck.name || 'Deck ' + (deck.originalIndex + 1) }}
            </button>
        </div>

        <!-- Toolbar -->
        <div class="absolute top-4 left-4 z-10 bg-white/90 backdrop-blur-md p-2 rounded-xl border border-slate-200 shadow-xl flex gap-2" :style="decks.length > 1 ? 'top: 3.5rem' : ''">
            <button @click="resetView" class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors" title="Reset View">
                <i class="fas fa-expand-arrows-alt"></i>
            </button>
            <div class="w-px h-6 bg-slate-700 mx-1 mt-1"></div>
            <div class="flex items-center gap-3 px-3 text-xs font-bold text-slate-500">
                <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div> Available</div>
                <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded-full bg-slate-700"></div> Booked</div>
                <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.5)]"></div> Selected</div>
            </div>
        </div>

        <div class="flex-1 relative w-full h-full" ref="canvasContainer">
            <canvas ref="canvasEl" class="absolute top-0 left-0"></canvas>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';
import { fabric } from 'fabric';

const props = defineProps({
    layoutJson: {
        type: [Object, String],
        default: null
    },
    bookedSeats: {
        type: Array,
        default: () => []
    },
    selectedSeats: {
        type: Array,
        default: () => []
    },
    targetClassId: {
        type: [Number, String],
        required: true
    },
    allowedRoomIds: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['seatSelected']);

const canvasEl = ref(null);
const canvasContainer = ref(null);
let canvas = null;
let resizeObserver = null;
const layoutData = ref(null);

// Multi-deck state
const decks = ref([]);
const activeDeckIndex = ref(0);
const activeDeck = computed(() => decks.value[activeDeckIndex.value] || null);
const hasDeckData = computed(() => activeDeck.value && (activeDeck.value.backgroundImage || (activeDeck.value.seats || []).length > 0));

const visibleDecks = computed(() => {
    if (!props.targetClassId) return decks.value.map((d, i) => ({ ...d, originalIndex: i }));

    const targetClassId = parseInt(props.targetClassId);
    let allowedRoomIds = [];
    if (props.allowedRoomIds && props.allowedRoomIds.length > 0) {
        allowedRoomIds = props.allowedRoomIds.map(id => parseInt(id));
    }

    const filtered = decks.value.map((deck, index) => {
        const hasValidSeats = deck.seats && deck.seats.some(seat => {
            if (parseInt(seat.class_id) !== targetClassId) return false;
            if (allowedRoomIds.length > 0 && !allowedRoomIds.includes(parseInt(seat.room_id))) return false;
            return true;
        });
        return hasValidSeats ? { ...deck, originalIndex: index } : null;
    }).filter(d => d !== null);

    // Fallback to all decks if no seats match at all across the entire vessel
    return filtered.length > 0 ? filtered : decks.value.map((d, i) => ({ ...d, originalIndex: i }));
});

// Normalize v1 or v2 layout into a decks array
const normalizeToDecks = (raw) => {
    if (!raw || raw === 'processing') return [];
    if (Array.isArray(raw.decks)) return raw.decks;
    // v1: wrap
    if (raw.deck || raw.seats) {
        return [{
            deck_number: 1,
            name: raw.deck?.name || 'Deck 1',
            width: raw.deck?.width || 1200,
            height: raw.deck?.height || 600,
            backgroundImage: raw.deck?.backgroundImage || null,
            seats: raw.seats || [],
        }];
    }
    return [];
};

watch(() => props.layoutJson, (newVal) => {
    decks.value = normalizeToDecks(newVal);
    if (visibleDecks.value.length > 0) {
        activeDeckIndex.value = visibleDecks.value[0].originalIndex;
    } else {
        activeDeckIndex.value = 0;
    }
    layoutData.value = activeDeck.value; // for legacy compat check
    if (!props.loading && canvas && activeDeck.value) renderLayout();
}, { immediate: true });

watch(() => props.loading, (newLoading) => {
    if (!newLoading && activeDeck.value) {
        renderLayout();
    }
});

watch([() => props.bookedSeats, () => props.selectedSeats], () => {
    if (canvas) {
        updateSeatColors();
    }
}, { deep: true });

watch([() => props.targetClassId, () => props.allowedRoomIds], () => {
    // If target class changes, check if current deck is still valid
    const isCurrentVisible = visibleDecks.value.some(d => d.originalIndex === activeDeckIndex.value);
    
    if (!isCurrentVisible && visibleDecks.value.length > 0) {
        switchDeck(visibleDecks.value[0].originalIndex);
    } else if (canvas) {
        // Re-evaluate seat availability colors for the new class on the current deck
        updateSeatColors();
    }
}, { deep: true });

const switchDeck = (index) => {
    if (index === activeDeckIndex.value) return;
    activeDeckIndex.value = index;
    layoutData.value = activeDeck.value;
    renderLayout();
};

onMounted(() => {
    nextTick(() => {
        initCanvas();
    });
});

onBeforeUnmount(() => {
    if(resizeObserver && canvasContainer.value) {
        resizeObserver.disconnect();
    }
    if(canvas) {
        canvas.dispose();
    }
});

const initCanvas = () => {
    canvas = new fabric.Canvas(canvasEl.value, {
        width: canvasContainer.value.clientWidth || 800,
        height: canvasContainer.value.clientHeight || 600,
        selection: false, // No drag-selection in viewer
        backgroundColor: '#0f172a', // slate-950
        preserveObjectStacking: true
    });

    resizeObserver = new ResizeObserver(entries => {
        for (let entry of entries) {
            const { width, height } = entry.contentRect;
            if (canvas && width > 0 && height > 0) {
                canvas.setWidth(width);
                canvas.setHeight(height);
                if (width > 200 && height > 200 && layoutData.value) {
                    resetView();
                }
            }
        }
    });
    if (canvasContainer.value) {
        resizeObserver.observe(canvasContainer.value);
    }

    setupCanvasEvents();
    if(layoutData.value) {
        renderLayout();
    }
};

let isDragging = false;
let lastPosX = 0;
let lastPosY = 0;

const setupCanvasEvents = () => {
    // Panning
    canvas.on('mouse:down', function (opt) {
        let evt = opt.e;
        // Right click or alt key to pan, or just drag background
        if(!opt.target || opt.target.type !== 'seat') {
            isDragging = true;
            canvas.selection = false;
            lastPosX = evt.clientX;
            lastPosY = evt.clientY;
        }
    });

    canvas.on('mouse:move', function (opt) {
        if (isDragging) {
            let e = opt.e;
            let vpt = canvas.viewportTransform;
            vpt[4] += e.clientX - lastPosX;
            vpt[5] += e.clientY - lastPosY;
            canvas.requestRenderAll();
            lastPosX = e.clientX;
            lastPosY = e.clientY;
        }
    });

    canvas.on('mouse:up', function () {
        canvas.setViewportTransform(canvas.viewportTransform);
        isDragging = false;
        canvas.selection = false;
    });

    // Zooming
    canvas.on('mouse:wheel', function (opt) {
        let delta = opt.e.deltaY;
        let zoom = canvas.getZoom();
        zoom *= 0.999 ** delta;
        if (zoom > 5) zoom = 5;
        if (zoom < 0.1) zoom = 0.1;
        canvas.zoomToPoint({ x: opt.e.offsetX, y: opt.e.offsetY }, zoom);
        opt.e.preventDefault();
        opt.e.stopPropagation();
    });

    // Hover cursors
    canvas.on('mouse:over', function(e) {
        if (e.target && e.target.data && e.target.data.type === 'seat') {
            const isAvailable = isSeatAvailable(e.target);
            e.target.set('hoverCursor', isAvailable ? 'pointer' : 'not-allowed');
        }
    });
};

const isSeatAvailable = (seatObj) => {
    const seatId = seatObj.data.id;
    const seatLabel = seatObj.data.label;
    const seatClassId = parseInt(seatObj.data.class_id);
    const targetClassId = parseInt(props.targetClassId);
    
    // Seat must belong to the selected class
    if (seatClassId !== targetClassId) return false;
    
    // Seat must belong to allowed rooms (if specified)
    if (props.allowedRoomIds && props.allowedRoomIds.length > 0) {
        const seatRoomId = parseInt(seatObj.data.room_id);
        const allowedIds = props.allowedRoomIds.map(id => parseInt(id));
        if (!allowedIds.includes(seatRoomId)) return false;
    }
    
    return isSeatSelectable(seatId, seatLabel);
};

const isSeatSelectable = (seatId, seatLabel) => {
    if (!props.targetClassId) return false; // Default to false if no target class
    
    // Check against both ID and Label since the backend now stores the human readable Label
    const isBooked = props.bookedSeats && (props.bookedSeats.includes(seatId) || (seatLabel && props.bookedSeats.includes(seatLabel)));
    if (isBooked) return false;
    
    const isSelected = props.selectedSeats && (props.selectedSeats.includes(seatId) || (seatLabel && props.selectedSeats.includes(seatLabel)));
    if (isSelected) return false;
    
    return true;
};

const updateSeatColors = () => {
    if(!canvas) return;
    const objects = canvas.getObjects();
    
    objects.forEach(obj => {
        if(obj.data && obj.data.type === 'seat') {
            const seatId = obj.data.id;
            const seatLabel = obj.data.label;
            const seatClassId = parseInt(obj.data.class_id);
            const targetClassId = parseInt(props.targetClassId);
            
            let fillColor = '#334155'; // slate-700 (unavailable / wrong class)
            let strokeColor = '#475569';
            let shadowColor = null;
            let opacity = 1;

            if (seatClassId === targetClassId) {
                let isInAllowedRoom = true;
                // Check if seat belongs to allowed room
                if (props.allowedRoomIds && props.allowedRoomIds.length > 0) {
                    const seatRoomId = parseInt(obj.data.room_id);
                    const allowedIds = props.allowedRoomIds.map(id => parseInt(id));
                    if (!allowedIds.includes(seatRoomId)) {
                        isInAllowedRoom = false;
                    }
                }

                if (isInAllowedRoom) {
                    const isBooked = props.bookedSeats && (props.bookedSeats.includes(seatId) || (seatLabel && props.bookedSeats.includes(seatLabel)));
                    const isSelected = props.selectedSeats && (props.selectedSeats.includes(seatId) || (seatLabel && props.selectedSeats.includes(seatLabel)));
                    
                    if (isSelected) {
                        fillColor = '#3b82f6'; // blue-500
                        strokeColor = '#60a5fa';
                        shadowColor = 'rgba(59,130,246,0.5)';
                    } else if (isBooked) {
                        // Booked by someone else
                        fillColor = '#334155'; // slate-700
                        strokeColor = '#475569';
                    } else {
                        // Available
                        fillColor = '#10b981'; // emerald-500
                        strokeColor = '#34d399';
                        shadowColor = 'rgba(16,185,129,0.3)';
                    }
                } else {
                    opacity = 0.15; // Very dim out seats not in allowed room
                    fillColor = '#94a3b8'; // slate-400 to distinguish from black (booked)
                    strokeColor = '#cbd5e1';
                }
            } else {
                opacity = 0.2; // Dim out seats not in selected class
            }
            
            // Set styles for the rect
            if(obj.type === 'group') {
                const rectObj = obj.getObjects()[0];
                const textObj = obj.getObjects()[1];
                if (rectObj) {
                    rectObj.set('fill', fillColor);
                    rectObj.set('stroke', strokeColor);
                    rectObj.set('shadow', shadowColor ? new fabric.Shadow({
                        color: shadowColor,
                        blur: 8,
                        offsetX: 0,
                        offsetY: 0
                    }) : null);
                }
                if (textObj) {
                    textObj.set('fill', (fillColor === '#334155') ? '#64748b' : '#ffffff');
                }
                obj.set('opacity', opacity);
            }
        }
    });
    canvas.requestRenderAll();
};

const renderLayout = () => {
    if (!canvas || !activeDeck.value) return;
    
    canvas.clear();
    const data = activeDeck.value;
    
    const deckWidth  = data.width  || 1200;
    const deckHeight = data.height || 600;
    
    // Dark mode outline
    const deckOutline = new fabric.Rect({
        left: 0,
        top: 0,
        width: deckWidth,
        height: deckHeight,
        fill: 'transparent',
        stroke: '#334155',
        strokeWidth: 2,
        selectable: false,
        evented: false,
        rx: 20,
        ry: 20
    });
    
    canvas.add(deckOutline);
    
    if (data.backgroundImage) {
        fabric.Image.fromURL(data.backgroundImage, function(img) {
            const scale = Math.min(deckWidth / img.width, deckHeight / img.height);
            img.set({
                originX: 'center',
                originY: 'center',
                left: deckWidth / 2,
                top:  deckHeight / 2,
                scaleX: scale,
                scaleY: scale,
                selectable: false,
                evented: false,
                opacity: 0.3 // Dim the blueprint for dark mode
            });
            canvas.add(img);
            img.sendToBack();
            renderSeats(data.seats || []);
            resetView();
        });
    } else {
        renderSeats(data.seats || []);
        resetView();
    }
};

const renderSeats = (seats) => {
    seats.forEach(seat => {
        const rect = new fabric.Rect({
            width: seat.width,
            height: seat.height,
            fill: '#10b981',
            stroke: '#34d399',
            strokeWidth: 2,
            rx: 6,
            ry: 6,
            originX: 'center',
            originY: 'center'
        });
        
        const text = new fabric.Text(seat.label || seat.id, {
            fontSize: 12,
            fontFamily: 'monospace',
            fontWeight: 'bold',
            fill: '#ffffff',
            originX: 'center',
            originY: 'center'
        });
        
        const group = new fabric.Group([rect, text], {
            left: seat.x,
            top: seat.y,
            angle: seat.angle || 0,
            hasControls: false,
            hasBorders: false,
            selectable: false, // Handle clicks manually without showing selection box
            data: {
                type: 'seat',
                id: seat.id,
                label: seat.label || seat.id,
                class_id: seat.class_id,
                room_id: seat.room_id
            }
        });
        
        group.on('mousedown', (e) => {
            if (isSeatAvailable(group)) {
                // If left click, select the seat
                if (e.e.button !== 2) {
                    // Emit the label as the primary ID; also include deck_number, class_id, and room_id
                    const seatLabel = seat.label || seat.id;
                    const deckNum = activeDeck.value?.deck_number || 1;
                    emit('seatSelected', { 
                        id: seatLabel, 
                        label: seatLabel, 
                        rawId: seat.id, 
                        deck_number: deckNum, 
                        class_id: seat.class_id, 
                        room_id: seat.room_id 
                    });
                }
            }
        });
        
        canvas.add(group);
    });
    
    updateSeatColors();
};

const resetView = () => {
    if(!canvas) return;
    const deckWidth  = activeDeck.value?.width  || 1200;
    const deckHeight = activeDeck.value?.height || 600;
    const padding = 20;
    
    const scaleX = (canvas.width - padding) / deckWidth;
    const scaleY = (canvas.height - padding) / deckHeight;
    let scale = Math.min(scaleX, scaleY);
    
    if(scale < 0.1) scale = 0.1;
    if(scale > 2.5) scale = 2.5;

    canvas.setViewportTransform([1, 0, 0, 1, 0, 0]);
    canvas.zoomToPoint({ x: 0, y: 0 }, scale);
    
    const panX = (canvas.width - (deckWidth * scale)) / 2;
    const panY = (canvas.height - (deckHeight * scale)) / 2;
    
    const vpt = canvas.viewportTransform;
    vpt[4] = panX;
    vpt[5] = panY;
    canvas.setViewportTransform(vpt);
};
</script>
