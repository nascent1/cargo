<template>
    <div class="flex flex-col h-[calc(100vh-140px)] w-full border border-slate-300 rounded-2xl overflow-hidden bg-slate-50 shadow-sm relative">

        <!-- Deck Tab Bar (only shown when more than one deck) -->
        <div v-if="decks.length > 1" class="h-12 bg-slate-900 border-b border-slate-700 flex items-stretch px-4 gap-1 z-20 shrink-0 overflow-x-auto">
            <button
                v-for="(deck, i) in decks"
                :key="i"
                @click="switchDeck(i)"
                :class="activeDeckIndex === i
                    ? 'bg-white text-slate-900 font-black border-b-2 border-transparent'
                    : 'text-slate-400 hover:text-white hover:bg-white/10'"
                class="flex items-center gap-2 px-5 text-sm font-semibold transition-colors whitespace-nowrap rounded-t-lg mt-1">
                <i class="fas fa-layer-group text-xs"></i>
                {{ deck.name || 'Deck ' + (i + 1) }}
            </button>
        </div>

        <!-- Toolbar -->
        <div class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <div class="flex items-center gap-4">
                <button @click="setMode('select')" :class="mode === 'select' ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-slate-100'" class="p-2 rounded-lg transition-colors" title="Select / Move">
                    <i class="fas fa-mouse-pointer"></i>
                </button>
                <button @click="setMode('pan')" :class="mode === 'pan' ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-slate-100'" class="p-2 rounded-lg transition-colors" title="Pan Canvas">
                    <i class="fas fa-hand-paper"></i>
                </button>
                <button @click="setMode('zone')" :class="mode === 'zone' ? 'bg-purple-100 text-purple-700' : 'text-slate-600 hover:bg-slate-100'" class="p-2 rounded-lg transition-colors" title="Draw Class Zone">
                    <i class="fas fa-vector-square"></i>
                </button>
                <div class="w-px h-6 bg-slate-200 mx-2"></div>
                <button @click="zoomIn" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Zoom In">
                    <i class="fas fa-search-plus"></i>
                </button>
                <button @click="zoomOut" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Zoom Out">
                    <i class="fas fa-search-minus"></i>
                </button>
                <button @click="resetView" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Reset View">
                    <i class="fas fa-expand-arrows-alt"></i>
                </button>
                <div class="w-px h-6 bg-slate-200 mx-2"></div>
                <button @click="selectAllSeats" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Select All Seats">
                    <i class="fas fa-object-group"></i>
                </button>
            </div>

            <div class="flex items-center gap-4">
                <div v-if="saving" class="text-sm font-semibold text-slate-500 animate-pulse">
                    <i class="fas fa-circle-notch fa-spin mr-2"></i> Saving...
                </div>
                <div v-if="saveSuccess" class="text-sm font-semibold text-emerald-600 transition-opacity" :class="{'opacity-0': !showSuccessBadge}">
                    <i class="fas fa-check-circle mr-2"></i> Saved
                </div>
                <button @click="resetCanvas" class="px-5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 text-sm font-bold rounded-xl transition-colors shadow-sm flex items-center gap-2 border border-rose-200">
                    <i class="fas fa-trash-alt"></i> Reset
                </button>
                <button @click="generateAIMapping" :disabled="saving || generatingAI" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold rounded-xl transition-colors disabled:opacity-50 shadow-sm flex items-center gap-2">
                    <i class="fas fa-magic" :class="{'fa-spin': generatingAI}"></i> Generate AI Mapping
                </button>
                <button @click="saveLayout" :disabled="saving || generatingAI" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl transition-colors disabled:opacity-50 shadow-sm flex items-center gap-2">
                    <i class="fas fa-save"></i> Publish Layout
                </button>
            </div>
        </div>

        <!-- Main Workspace -->
        <div class="flex-1 flex overflow-hidden relative">
            <!-- Left Sidebar (Object Library / Properties) -->
            <div class="w-64 bg-white border-r border-slate-200 flex flex-col z-10 shadow-[4px_0_15px_rgba(0,0,0,0.03)] overflow-y-auto">
                <div class="p-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider">Properties</h3>
                </div>
                <div class="p-4 border-b border-slate-100" v-if="mode === 'zone'">
                    <h4 class="text-xs font-bold text-slate-500 mb-2">Zone Painter Active</h4>
                    <p class="text-xs text-slate-600 mb-3">Select a class below, then click and drag on the canvas to draw a zone. All seats inside the zone will be automatically assigned to that class.</p>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Painting Class</label>
                    <select v-model="paintingClassId" @change="paintingRoomId = null" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option :value="null">-- Select Class --</option>
                        <option v-for="cls in ferryClasses" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                    </select>
                    
                    <div v-if="selectedPaintingClass?.is_room_based && selectedPaintingClass?.rooms?.length > 0" class="mt-3 transition-all">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Painting Room</label>
                        <select v-model="paintingRoomId" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option :value="null">-- Select Room --</option>
                            <option v-for="room in selectedPaintingClass.rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="p-4 flex-1">
                    <!-- Bulk Selection Properties -->
                    <div v-if="selectedObjects.length > 1" class="space-y-4">
                        <p class="text-sm font-bold text-slate-700">{{ selectedObjects.length }} objects selected</p>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Assign Class to All Seats</label>
                        <select v-model="bulkClassId" @change="bulkAssignClass" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option :value="null">-- Select Class --</option>
                            <option v-for="cls in ferryClasses" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                        <div class="pt-4 border-t border-slate-100">
                            <button @click="deleteSelected" class="w-full py-2 text-sm font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-trash"></i> Delete Selected
                            </button>
                        </div>
                    </div>
                    <!-- Single Selection Properties -->
                    <div v-else-if="selectedObjects.length === 1 && selectedObject">
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-slate-500 mb-1">Type</label>
                            <div class="text-sm font-semibold text-slate-800 capitalize">{{ selectedObject.data?.type || 'Unknown' }}</div>
                        </div>
                        <div class="mb-4" v-if="selectedObject.data?.id">
                            <label class="block text-xs font-bold text-slate-500 mb-1">ID / Label</label>
                            <input type="text" v-model="selectedObject.data.label" @input="updateObjectLabel" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="mb-4" v-if="selectedObject.data?.type === 'seat'">
                            <label class="block text-xs font-bold text-slate-500 mb-1">Assign Class</label>
                            <select v-model="selectedObject.data.class_id" @change="selectedObject.data.room_id = null; updateSeatColor(selectedObject)" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option :value="null">Unassigned</option>
                                <option v-for="cls in ferryClasses" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                            </select>
                            
                            <div v-if="selectedObjectAssignedClass?.is_room_based && selectedObjectAssignedClass?.rooms?.length > 0" class="mt-3">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Assign Room</label>
                                <select v-model="selectedObject.data.room_id" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option :value="null">-- Select Room --</option>
                                    <option v-for="room in selectedObjectAssignedClass.rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-slate-100">
                            <button @click="deleteSelected" class="w-full py-2 text-sm font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-trash"></i> Delete Object
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-sm text-slate-400 text-center mt-10">
                        <i class="fas fa-mouse-pointer text-3xl mb-3 text-slate-300 block"></i>
                        Select an object on the canvas to edit its properties.
                    </div>

                    <!-- Class Legend -->
                    <div class="mt-8 border-t border-slate-100 pt-4">
                        <h4 class="text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Class Legend</h4>
                        <div class="space-y-2">
                            <div v-for="(cls, idx) in ferryClasses" :key="cls.id" class="flex items-center text-sm text-slate-700">
                                <div class="w-4 h-4 rounded-sm mr-2 border border-slate-200" :style="{ backgroundColor: getSeatColor(cls.id) }"></div>
                                {{ cls.name }}
                            </div>
                            <div class="flex items-center text-sm text-slate-700">
                                <div class="w-4 h-4 rounded-sm mr-2 bg-slate-500 border border-slate-200"></div>
                                Unassigned
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Canvas Wrapper -->
            <div class="flex-1 bg-slate-100 overflow-hidden relative" ref="canvasContainer">
                <!-- We render the fabric canvas here -->
                <canvas ref="canvasEl" class="absolute top-0 left-0"></canvas>
            </div>
        </div>
    </div>

    <!-- Validation Modal -->
    <div v-if="showValidationModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-clipboard-check text-blue-500"></i> Capacity Validation
                </h3>
                <button @click="showValidationModal = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-slate-600 mb-6">Publishing this layout will automatically update the seat capacities for the following classes and rooms to match the drawn blueprint exactly. Do you wish to proceed?</p>
                
                <div class="space-y-3 mb-6 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                    <div v-for="cap in layoutCapacities" :key="cap.label" class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                                <i :class="cap.is_room ? 'fas fa-door-open' : 'fas fa-couch'"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-700">{{ cap.label }}</div>
                                <div class="text-xs text-slate-500">{{ cap.is_room ? 'Room' : 'Class' }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-slate-800">{{ cap.count }}</div>
                            <div class="text-xs text-slate-500 font-medium">Seats</div>
                        </div>
                    </div>
                    <div v-if="layoutCapacities.length === 0" class="text-center p-4 text-slate-500 text-sm">
                        No assigned seats detected on the canvas.
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="showValidationModal = false" class="flex-1 py-3 bg-white border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-colors">Cancel</button>
                    <button @click="confirmPublishLayout" :disabled="saving" class="flex-1 py-3 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-check" v-if="!saving"></i>
                        <i class="fas fa-circle-notch fa-spin" v-if="saving"></i>
                        Confirm & Publish
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';
import { fabric } from 'fabric';

const props = defineProps({
    ferry: {
        type: Object,
        required: true
    },
    layoutJson: {
        type: Object,
        required: true
    },
    saveUrl: {
        type: String,
        required: true
    }
});

const canvasEl = ref(null);
const canvasContainer = ref(null);
let canvas = null;

const mode = ref('select'); // select, pan, zone
const saving = ref(false);
const saveSuccess = ref(false);
const generatingAI = ref(false);
const showSuccessBadge = ref(false);
const selectedObjects = ref([]);
const selectedObject = computed(() => selectedObjects.value.length === 1 ? selectedObjects.value[0] : null);
const bulkClassId = ref(null);
const paintingClassId = ref(null);
const paintingRoomId = ref(null);
const showValidationModal = ref(false);
const layoutCapacities = ref([]);

// Multi-deck state
const decks = ref([]);          // Array of deck objects from v2 layout (or wrapped v1)
const activeDeckIndex = ref(0); // Currently edited deck tab index

// Normalize incoming layout into v2 structure
const normalizeLayout = (raw) => {
    if (raw && Array.isArray(raw.decks)) {
        // Already v2
        return raw.decks;
    }
    // v1: wrap single deck
    if (raw && (raw.deck || raw.seats)) {
        return [{
            deck_number: 1,
            name: raw.deck?.name || 'Deck 1',
            width: raw.deck?.width || 1200,
            height: raw.deck?.height || 800,
            backgroundImage: raw.deck?.backgroundImage || null,
            walls: raw.walls || [],
            seats: raw.seats || [],
            zones: raw.zones || [],
        }];
    }
    // Empty / null
    return [{
        deck_number: 1,
        name: 'Deck 1',
        width: 1200,
        height: 800,
        backgroundImage: null,
        walls: [],
        seats: [],
        zones: [],
    }];
};

const selectedPaintingClass = computed(() => {
    if (!paintingClassId.value) return null;
    return ferryClasses.value.find(c => c.id === paintingClassId.value);
});

const selectedObjectAssignedClass = computed(() => {
    if (!selectedObject.value || !selectedObject.value.data?.class_id) return null;
    return ferryClasses.value.find(c => c.id === selectedObject.value.data.class_id);
});

let isDragging = false;
let lastPosX = 0;
let lastPosY = 0;

let isDrawingZone = false;
let zoneRect = null;
let origX = 0;
let origY = 0;

const ferryClasses = ref(props.ferry.classes || []);

// Active deck data shortcut (reactive reference into decks array)
const activeDeck = computed(() => decks.value[activeDeckIndex.value] || {});

// Keep localLayoutData pointing at active deck for backward-compat usage inside helpers
const localLayoutData = computed(() => activeDeck.value);

const hasLayoutData = computed(() => {
    return activeDeck.value && (activeDeck.value.backgroundImage || activeDeck.value.seats?.length > 0);
});

// Generate a globally unique seat ID including deck prefix: d{n}_{class_id}_{seq}
const nextSeatId = (classId) => {
    const deckNum = activeDeck.value.deck_number || (activeDeckIndex.value + 1);
    const prefix = `d${deckNum}_${classId}_`;
    const existing = (activeDeck.value.seats || []).map(s => s.id).filter(id => String(id).startsWith(prefix));
    let max = 0;
    existing.forEach(id => {
        const n = parseInt(String(id).split('_').pop(), 10);
        if (!isNaN(n) && n > max) max = n;
    });
    return `${prefix}${max + 1}`;
};

// Configuration
const CLASS_COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4'];

const fetchClasses = async () => {
    try {
        const response = await fetch(`/admin/ferries/${props.ferry.id}/classes`);
        if (response.ok) {
            ferryClasses.value = await response.json();
            // Re-render to update colors if needed
            if (canvas) renderLayout();
        }
    } catch (e) {
        console.error("Failed to fetch classes", e);
    }
};

const initCanvas = () => {
    // Initialize Fabric
    canvas = new fabric.Canvas(canvasEl.value, {
        width: canvasContainer.value.clientWidth || 800,
        height: canvasContainer.value.clientHeight || 600,
        selection: true,
        preserveObjectStacking: true, // Keep selected objects at their z-index
    });

    const resizeObserver = new ResizeObserver(entries => {
        for (let entry of entries) {
            const { width, height } = entry.contentRect;
            if (canvas && width > 0 && height > 0) {
                canvas.setWidth(width);
                canvas.setHeight(height);
                canvas.renderAll();
                if (width > 200 && height > 200) {
                    resetView();
                }
            }
        }
    });
    if (canvasContainer.value) {
        resizeObserver.observe(canvasContainer.value);
    }

    setupCanvasEvents();
    renderLayout();
    
    // Fetch fresh classes
    fetchClasses();
};

const setupCanvasEvents = () => {
    // Panning & Drawing logic
    canvas.on('mouse:down', function (opt) {
        let evt = opt.e;
        
        if (mode.value === 'pan' || (evt.altKey === true)) {
            isDragging = true;
            canvas.selection = false;
            lastPosX = evt.clientX;
            lastPosY = evt.clientY;
        } else if (mode.value === 'zone' && paintingClassId.value) {
            isDrawingZone = true;
            let pointer = canvas.getPointer(opt.e);
            origX = pointer.x;
            origY = pointer.y;
            let pointerColor = getSeatColor(paintingClassId.value);
            
            zoneRect = new fabric.Rect({
                left: origX,
                top: origY,
                originX: 'left',
                originY: 'top',
                width: 0,
                height: 0,
                fill: pointerColor + '33', // 20% opacity hex
                stroke: pointerColor,
                strokeWidth: 2,
                strokeDashArray: [5, 5],
                selectable: false,
                evented: false,
                data: { 
                    type: 'zone', 
                    class_id: paintingClassId.value,
                    room_id: paintingRoomId.value 
                }
            });
            canvas.add(zoneRect);
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
        } else if (isDrawingZone) {
            let pointer = canvas.getPointer(opt.e);
            if (origX > pointer.x) {
                zoneRect.set({ left: Math.abs(pointer.x) });
            }
            if (origY > pointer.y) {
                zoneRect.set({ top: Math.abs(pointer.y) });
            }
            zoneRect.set({ width: Math.abs(origX - pointer.x) });
            zoneRect.set({ height: Math.abs(origY - pointer.y) });
            canvas.requestRenderAll();
        }
    });

    canvas.on('mouse:up', function (opt) {
        if (isDragging) {
            canvas.setViewportTransform(canvas.viewportTransform);
            isDragging = false;
            if(mode.value === 'select') canvas.selection = true;
        } else if (isDrawingZone) {
            isDrawingZone = false;
            if (zoneRect.width > 10 && zoneRect.height > 10) {
                // Auto-assign seats inside this zone
                assignSeatsInZone(zoneRect, paintingClassId.value);
            } else {
                // Too small, remove it
                canvas.remove(zoneRect);
            }
            zoneRect = null;
        }
    });

    // Zooming logic
    canvas.on('mouse:wheel', function (opt) {
        let delta = opt.e.deltaY;
        let zoom = canvas.getZoom();
        zoom *= 0.999 ** delta;
        if (zoom > 20) zoom = 20;
        if (zoom < 0.1) zoom = 0.1;
        canvas.zoomToPoint({ x: opt.e.offsetX, y: opt.e.offsetY }, zoom);
        opt.e.preventDefault();
        opt.e.stopPropagation();
    });

    // Selection logic
    canvas.on('selection:created', handleSelection);
    canvas.on('selection:updated', handleSelection);
    canvas.on('selection:cleared', () => { 
        selectedObjects.value = [];
        bulkClassId.value = null;
    });
};

const assignSeatsInZone = (rect, classId) => {
    let assignedCount = 0;
    canvas.getObjects().forEach(obj => {
        if (obj.data && obj.data.type === 'seat') {
            // Check intersection (simple bounding box overlap)
            if (obj.left >= rect.left && obj.left <= (rect.left + rect.width) &&
                obj.top >= rect.top && obj.top <= (rect.top + rect.height)) {
                
                obj.data.class_id = classId;
                const rectObj = obj.getObjects('rect')[0];
                if (rectObj) {
                    rectObj.set('fill', getSeatColor(classId));
                }
                assignedCount++;
            }
        }
    });
    
    // Add zone to active deck layout data
    if (decks.value[activeDeckIndex.value]) {
        if (!decks.value[activeDeckIndex.value].zones) decks.value[activeDeckIndex.value].zones = [];
        decks.value[activeDeckIndex.value].zones.push({
            name: ferryClasses.value.find(c => c.id === classId)?.name || 'Zone',
            class_id: classId,
            bounding_box: {
                x: rect.left,
                y: rect.top,
                width: rect.width,
                height: rect.height
            }
        });
    }
    
    canvas.requestRenderAll();
    
    // Optional: show a toast
    // alert(`Assigned ${assignedCount} seats to class.`);
};

const handleSelection = (opt) => {
    selectedObjects.value = opt.selected;
    if (selectedObjects.value.length === 1 && selectedObjects.value[0].data?.type === 'seat') {
        bulkClassId.value = selectedObjects.value[0].data.class_id;
    } else {
        bulkClassId.value = null;
    }
};

const bulkAssignClass = () => {
    if(selectedObjects.value.length > 0 && bulkClassId.value) {
        selectedObjects.value.forEach(obj => {
            if(obj.data && obj.data.type === 'seat') {
                obj.data.class_id = bulkClassId.value;
                updateSeatColor(obj);
            }
        });
        canvas.discardActiveObject();
        canvas.requestRenderAll();
    }
};

const renderLayout = () => {
    canvas.clear();
    const data = activeDeck.value;

    if (!data) return;

    if (data.backgroundImage) {
        fabric.Image.fromURL(data.backgroundImage, function(img) {
            const scale = Math.min((data.width || 1200) / img.width, (data.height || 800) / img.height);
            img.set({
                originX: 'center',
                originY: 'center',
                left: (data.width || 1200) / 2,
                top:  (data.height || 800) / 2,
                scaleX: scale,
                scaleY: scale,
                opacity: 0.8,
                selectable: false,
                evented: false
            });
            canvas.add(img);
            canvas.sendToBack(img);
        });
    } else {
        const deckOutline = new fabric.Rect({
            left: 0,
            top: 0,
            width: data.width || 1200,
            height: data.height || 800,
            fill: '#f8fafc',
            stroke: '#cbd5e1',
            strokeWidth: 2,
            selectable: false,
            evented: false,
            data: { type: 'deck' }
        });
        canvas.add(deckOutline);
    }

    if (data.walls) {
        data.walls.forEach(wall => {
            const points = wall.points.map(p => ({ x: p[0], y: p[1] }));
            const polyline = new fabric.Polyline(points, {
                fill: 'transparent',
                stroke: wall.type === 'outer' ? '#1e293b' : '#94a3b8',
                strokeWidth: wall.type === 'outer' ? 6 : 2,
                selectable: false,
                data: { ...wall }
            });
            canvas.add(polyline);
        });
    }

    // Render Zones from data
    if (data.zones) {
        data.zones.forEach(zone => {
            const bb = zone.bounding_box;
            if(bb) {
                let color = getSeatColor(zone.class_id);
                const rect = new fabric.Rect({
                    left: bb.x,
                    top: bb.y,
                    width: bb.width,
                    height: bb.height,
                    fill: color + '33',
                    stroke: color,
                    strokeWidth: 2,
                    strokeDashArray: [5, 5],
                    selectable: true,
                    data: { ...zone, type: 'zone' }
                });
                canvas.add(rect);
            }
        });
    }

    if (data.seats) {
        data.seats.forEach(seat => {
            if (seat.is_aisle) return;

            const rect = new fabric.Rect({
                width: seat.width,
                height: seat.height,
                fill: getSeatColor(seat.class_id),
                rx: 4,
                ry: 4,
                stroke: '#0f172a',
                strokeWidth: 1,
                originX: 'center',
                originY: 'center'
            });

            const text = new fabric.Text(seat.label, {
                fontSize: 10,
                fontFamily: 'sans-serif',
                fill: '#fff',
                originX: 'center',
                originY: 'center',
                fontWeight: 'bold'
            });

            const group = new fabric.Group([rect, text], {
                left: seat.x,
                top: seat.y,
                angle: seat.rotation || 0,
                selectable: true,
                hasControls: true,
                data: { ...seat, type: 'seat' }
            });

            canvas.add(group);
        });
    }

    resetView();
};

const getSeatColor = (classId) => {
    if (!classId) return '#64748b'; // default slate for unassigned
    const index = ferryClasses.value.findIndex(c => c.id === classId);
    if (index >= 0) {
        return CLASS_COLORS[index % CLASS_COLORS.length];
    }
    return '#64748b';
};

const setMode = (m) => {
    mode.value = m;
    if (m === 'pan') {
        canvas.defaultCursor = 'grab';
        canvas.selection = false;
        canvas.getObjects().forEach(o => o.set('selectable', false));
    } else if (m === 'zone') {
        canvas.defaultCursor = 'crosshair';
        canvas.selection = false;
        canvas.getObjects().forEach(o => o.set('selectable', false));
    } else {
        canvas.defaultCursor = 'default';
        canvas.selection = true;
        canvas.getObjects().forEach(o => {
            if(o.data && o.data.type !== 'deck') o.set('selectable', true);
        });
    }
};

const zoomIn = () => {
    let zoom = canvas.getZoom() * 1.2;
    if (zoom > 20) zoom = 20;
    canvas.zoomToPoint({ x: canvas.width / 2, y: canvas.height / 2 }, zoom);
};

const zoomOut = () => {
    let zoom = canvas.getZoom() / 1.2;
    if (zoom < 0.1) zoom = 0.1;
    canvas.zoomToPoint({ x: canvas.width / 2, y: canvas.height / 2 }, zoom);
};

const selectAllSeats = () => {
    const seats = canvas.getObjects().filter(o => o.data?.type === 'seat');
    const sel = new fabric.ActiveSelection(seats, { canvas: canvas });
    canvas.setActiveObject(sel);
    canvas.requestRenderAll();
};

const resetCanvas = () => {
    if (confirm('Are you sure you want to clear the entire deck layout? This will remove all drawn zones and mapped seats for this deck. You will still need to click "Publish Layout" to save this.')) {
        if (decks.value[activeDeckIndex.value]) {
            decks.value[activeDeckIndex.value].seats = [];
            decks.value[activeDeckIndex.value].zones = [];
        }
        renderLayout();
        selectedObjects.value = [];
    }
};

// Serialize current canvas state back into the active deck object
const serializeActiveDeck = () => {
    const newSeats = [];
    const newZones = [];
    canvas.getObjects().forEach(obj => {
        if (obj.data && obj.data.type === 'seat') {
            newSeats.push({ ...obj.data, x: obj.left, y: obj.top, angle: obj.angle });
        }
        if (obj.data && obj.data.type === 'zone') {
            newZones.push(obj.data);
        }
    });
    if (decks.value[activeDeckIndex.value]) {
        decks.value[activeDeckIndex.value].seats = newSeats;
        decks.value[activeDeckIndex.value].zones = newZones;
    }
};

// Switch to a different deck tab
const switchDeck = (index) => {
    if (index === activeDeckIndex.value) return;
    // Save current deck canvas state before switching
    serializeActiveDeck();
    selectedObjects.value = [];
    activeDeckIndex.value = index;
    renderLayout();
    resetView();
};

const resetView = () => {
    const deckWidth  = activeDeck.value?.width  || 1200;
    const deckHeight = activeDeck.value?.height || 600;
    const padding = 100;
    
    const scaleX = (canvas.width - padding) / deckWidth;
    const scaleY = (canvas.height - padding) / deckHeight;
    let scale = Math.min(scaleX, scaleY);
    
    if(scale < 0.1) scale = 0.1;
    if(scale > 1) scale = 1;

    canvas.setViewportTransform([1, 0, 0, 1, 0, 0]); 
    canvas.zoomToPoint({ x: 0, y: 0 }, scale);
    
    const panX = (canvas.width - (deckWidth * scale)) / 2;
    const panY = (canvas.height - (deckHeight * scale)) / 2;
    
    const vpt = canvas.viewportTransform;
    vpt[4] = panX;
    vpt[5] = panY;
    canvas.setViewportTransform(vpt);
};

const updateObjectLabel = () => {
    if (selectedObject.value && selectedObject.value.type === 'group' && selectedObject.value.data.type === 'seat') {
        const textObj = selectedObject.value.getObjects('text')[0];
        if (textObj) {
            textObj.set('text', selectedObject.value.data.label);
            canvas.requestRenderAll();
        }
    }
};

const updateSeatColor = (obj) => {
    if (obj && obj.type === 'group' && obj.data.type === 'seat') {
        const rectObj = obj.getObjects('rect')[0];
        if (rectObj) {
            rectObj.set('fill', getSeatColor(obj.data.class_id));
            canvas.requestRenderAll();
        }
    }
};

const deleteSelected = () => {
    const activeObjects = canvas.getActiveObjects();
    if (activeObjects.length) {
        activeObjects.forEach(obj => {
            canvas.remove(obj);
        });
        canvas.discardActiveObject();
        canvas.requestRenderAll();
    }
};

const saveLayout = () => {
    // First, save the current deck's canvas state into decks array
    serializeActiveDeck();

    // Tally seats across ALL decks
    const counts = {};
    
    // Pre-populate all active classes and rooms with 0
    ferryClasses.value.forEach(fc => {
        counts[`class_${fc.id}`] = { type: 'class', id: fc.id, count: 0, label: fc.name };
        if (fc.rooms && fc.rooms.length > 0) {
            fc.rooms.forEach(r => {
                counts[`room_${r.id}`] = { type: 'room', id: r.id, count: 0, label: r.name };
            });
        }
    });

    decks.value.forEach((deck, di) => {
        const seats = di === activeDeckIndex.value
            ? canvas.getObjects().filter(o => o.data?.type === 'seat').map(o => o.data)
            : (deck.seats || []);

        seats.forEach(seat => {
            if (seat && seat.class_id) {
                const cId = seat.class_id;
                const rId = seat.room_id || null;
                if (counts[`class_${cId}`]) counts[`class_${cId}`].count++;
                if (rId && counts[`room_${rId}`]) counts[`room_${rId}`].count++;
            }
        });
    });

    const capacities = [];
    Object.values(counts).forEach(c => {
        capacities.push({ label: c.label, count: c.count, is_room: c.type === 'room' });
    });

    layoutCapacities.value = capacities;
    showValidationModal.value = true;
};

const confirmPublishLayout = async () => {
    if (saving.value) return;
    saving.value = true;

    // Ensure active deck is serialized before publishing
    serializeActiveDeck();

    // Build v2 payload from all decks
    const v2Payload = {
        version: 2,
        decks: JSON.parse(JSON.stringify(decks.value)),
    };

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const response = await fetch(props.saveUrl, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                seat_layout_json: v2Payload
            })
        });
        
        if (response.ok) {
            saveSuccess.value = true;
            showSuccessBadge.value = true;
            showValidationModal.value = false;
            setTimeout(() => { showSuccessBadge.value = false; }, 3000);
        } else {
            alert('Failed to save layout.');
        }
    } catch (err) {
        console.error(err);
        alert('Error saving layout.');
    } finally {
        saving.value = false;
    }
};

const generateAIMapping = async () => {
    // Get all zones from canvas
    const currentZones = [];
    canvas.getObjects().forEach(obj => {
        if (obj.data && obj.data.type === 'zone') {
            currentZones.push({
                ...obj.data,
                bounding_box: {
                    left: obj.left,
                    top: obj.top,
                    width: obj.width * (obj.scaleX || 1),
                    height: obj.height * (obj.scaleY || 1)
                }
            });
        }
    });

    if (currentZones.length === 0) {
        alert("Please draw at least one zone before generating AI mapping.");
        return;
    }

    generatingAI.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const response = await fetch(`/admin/ferries/${props.ferry.id}/generate-ai-layout`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                zones: currentZones,
                deckIndex: activeDeckIndex.value
            })
        });
        
        if (response.ok) {
            const data = await response.json();
            
            // Clean up existing seats from canvas
            const objectsToRemove = canvas.getObjects().filter(o => o.data && o.data.type === 'seat');
            objectsToRemove.forEach(o => canvas.remove(o));
            
            // Assign new seats to active deck
            if (decks.value[activeDeckIndex.value]) {
                decks.value[activeDeckIndex.value].seats = data.seats || [];
            }
            
            // Re-render to show new seats
            renderLayout();
        } else {
            const err = await response.json();
            alert('AI Generation Failed: ' + (err.error || 'Unknown Error'));
        }
    } catch (err) {
        console.error(err);
        alert('Error generating AI mapping.');
    } finally {
        generatingAI.value = false;
    }
};

onMounted(() => {
    // Normalize layout into v2 decks structure
    decks.value = normalizeLayout(props.layoutJson);

    nextTick(() => {
        initCanvas();
    });
});

onBeforeUnmount(() => {
    if (canvas) {
        canvas.dispose();
    }
});
</script>
