<div x-data="{ 
        open: false, 
        title: '', 
        message: '', 
        actionMethod: '', 
        actionId: null,
        formAction: null,
        formMethod: 'POST',
        statusValue: null,
        buttonText: 'Confirm',
        buttonColor: 'brand'
     }" 
     @open-action-modal.window="
        open = true; 
        title = $event.detail.title || 'Confirm Action'; 
        message = $event.detail.message || 'Are you sure you want to proceed?'; 
        actionMethod = $event.detail.action || ''; 
        actionId = $event.detail.id || null;
        formAction = $event.detail.formAction || null;
        formMethod = $event.detail.formMethod || 'POST';
        statusValue = $event.detail.statusValue || null;
        buttonText = $event.detail.buttonText || 'Confirm';
        buttonColor = $event.detail.buttonColor || 'brand';
     "
     x-show="open" 
     @keydown.escape.window="open = false"
     x-cloak
     class="fixed inset-0 z-[60] overflow-y-auto" 
     aria-labelledby="modal-title" role="dialog" aria-modal="true">
     
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="open" 
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="open = false" aria-hidden="true"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="open" 
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200/60">
            
            <div class="bg-white px-6 py-8 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-5"
                     :class="{
                        'bg-red-50 text-red-500': buttonColor === 'red',
                        'bg-amber-50 text-amber-500': buttonColor === 'amber',
                        'bg-emerald-50 text-emerald-500': buttonColor === 'emerald',
                        'bg-brand-50 text-brand-500': buttonColor === 'brand'
                     }">
                    <i class="fas text-2xl" 
                       :class="{
                           'fa-exclamation-circle': buttonColor === 'red' || buttonColor === 'amber',
                           'fa-check-circle': buttonColor === 'emerald',
                           'fa-question-circle': buttonColor === 'brand'
                       }"></i>
                </div>
                <h3 class="text-xl leading-6 font-bold text-slate-800" id="modal-title" x-text="title"></h3>
                <div class="mt-3">
                    <p class="text-sm text-slate-500" x-text="message"></p>
                </div>
            </div>

            <div class="bg-slate-50 px-6 py-5 flex justify-center gap-4 border-t border-slate-100">
                <button type="button" @click="open = false" class="px-6 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-sm transition-colors w-full">
                    Cancel
                </button>
                <template x-if="!formAction">
                    <button type="button" 
                            @click="$wire.call(actionMethod, actionId); open = false" 
                            :class="{
                                'bg-red-500 hover:bg-red-600': buttonColor === 'red',
                                'bg-amber-500 hover:bg-amber-600': buttonColor === 'amber',
                                'bg-emerald-500 hover:bg-emerald-600': buttonColor === 'emerald',
                                'bg-brand-500 hover:bg-brand-600': buttonColor === 'brand'
                            }"
                            class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl shadow-sm transition-colors w-full">
                        <span x-text="buttonText"></span>
                    </button>
                </template>
                <template x-if="formAction">
                    <form :action="formAction" method="POST" class="w-full">
                        @csrf
                        <template x-if="formMethod !== 'POST'">
                            <input type="hidden" name="_method" :value="formMethod">
                        </template>
                        <template x-if="statusValue !== null">
                            <input type="hidden" name="status" :value="statusValue">
                        </template>
                        <button type="submit" 
                                :class="{
                                    'bg-red-500 hover:bg-red-600': buttonColor === 'red',
                                    'bg-amber-500 hover:bg-amber-600': buttonColor === 'amber',
                                    'bg-emerald-500 hover:bg-emerald-600': buttonColor === 'emerald',
                                    'bg-brand-500 hover:bg-brand-600': buttonColor === 'brand'
                                }"
                                class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl shadow-sm transition-colors w-full">
                            <span x-text="buttonText"></span>
                        </button>
                    </form>
                </template>
            </div>
        </div>
    </div>
</div>
