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
        buttonColor: 'brand',
        takesEffect: false,
        needsApproval: false,
        mode: 'now'
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
        takesEffect = !!$event.detail.takesEffect;
        needsApproval = !!$event.detail.needsApproval;
        mode = 'now';
     "
     x-show="open"
     @keydown.escape.window="open = false"
     x-cloak
     x-transition:enter="ease-out duration-150 motion-reduce:duration-0" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-100 motion-reduce:duration-0" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-4"
     aria-labelledby="modal-title" aria-describedby="modal-message" role="dialog" aria-modal="true">
    {{-- Same look as the confirm dialog (x-admin.confirm-dialog) --}}
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]" @click="open = false" aria-hidden="true"></div>

    <div x-show="open"
         x-transition:enter="ease-out duration-150 motion-reduce:duration-0" x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-100 motion-reduce:duration-0" x-transition:leave-start="opacity-100 sm:scale-100" x-transition:leave-end="opacity-0 sm:scale-95"
         class="relative w-full max-w-md bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden text-left">

        <div class="px-5 pt-5 sm:px-6 sm:pt-6 flex items-start gap-4">
            <div class="shrink-0 h-11 w-11 rounded-xl ring-1 ring-inset flex items-center justify-center text-lg" aria-hidden="true"
                 :class="{
                    'bg-rose-50 text-rose-600 ring-rose-100': buttonColor === 'red',
                    'bg-amber-50 text-amber-600 ring-amber-100': buttonColor === 'amber',
                    'bg-emerald-50 text-emerald-600 ring-emerald-100': buttonColor === 'emerald',
                    'bg-brand-50 text-brand-600 ring-brand-100': !['red', 'amber', 'emerald'].includes(buttonColor)
                 }">
                <i class="fas"
                   :class="{
                       'fa-circle-exclamation': buttonColor === 'red',
                       'fa-triangle-exclamation': buttonColor === 'amber',
                       'fa-circle-check': buttonColor === 'emerald',
                       'fa-circle-question': !['red', 'amber', 'emerald'].includes(buttonColor)
                   }"></i>
            </div>
            <div class="min-w-0 flex-1 pt-0.5">
                <h3 id="modal-title" class="text-base font-semibold text-slate-900 leading-snug break-words" x-text="title"></h3>
                <p id="modal-message" class="mt-1 text-sm text-slate-600 leading-relaxed break-words" x-text="message"></p>
            </div>
        </div>

        {{-- Dual control: when the change takes effect (inputs belong to the form below via form="…") --}}
        <template x-if="takesEffect || needsApproval">
            <div class="mx-5 sm:mx-6 mt-4 rounded-xl border px-4 py-3 text-sm" :class="needsApproval ? 'border-amber-200 bg-amber-50/60' : 'border-slate-200 bg-slate-50'">
                <div x-show="takesEffect" class="flex flex-wrap items-center gap-x-4 gap-y-2" role="radiogroup" aria-label="Takes effect">
                    <span class="text-xs font-semibold text-slate-700">Takes effect</span>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer"><input form="action-modal-form" type="radio" name="takes_effect" value="now" x-model="mode"> <span x-text="needsApproval ? 'When approved' : 'Immediately'"></span></label>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer"><input form="action-modal-form" type="radio" name="takes_effect" value="at" x-model="mode"> On</label>
                    <input form="action-modal-form" type="datetime-local" name="takes_effect_at" x-show="mode === 'at'" :required="mode === 'at'" aria-label="Date and time it takes effect"
                        class="rounded-lg border-slate-300 py-1.5 px-2 text-sm">
                </div>
                <p class="text-xs mt-1.5" :class="needsApproval ? 'text-amber-800' : 'text-slate-500'"
                    x-text="needsApproval ? 'Needs a checker\'s approval. If approved after the chosen start, it applies when approved.' : 'Applies without approval.'"></p>
            </div>
        </template>

        <div class="mt-5 px-5 sm:px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button type="button" @click="open = false"
                    class="h-10 px-4 rounded-xl text-sm font-semibold border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-300">
                Cancel
            </button>
            <template x-if="!formAction">
                <button type="button"
                        @click="$wire.call(actionMethod, actionId); open = false"
                        :class="{
                            'bg-rose-600 hover:bg-rose-700': buttonColor === 'red',
                            'bg-amber-700 hover:bg-amber-800': buttonColor === 'amber',
                            'bg-emerald-700 hover:bg-emerald-800': buttonColor === 'emerald',
                            'bg-brand-600 hover:bg-brand-700': !['red', 'amber', 'emerald'].includes(buttonColor)
                        }"
                        class="h-10 px-4 rounded-xl text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-400">
                    <span x-text="buttonText"></span>
                </button>
            </template>
            <template x-if="formAction">
                <form id="action-modal-form" :action="formAction" method="POST">
                    @csrf
                    <template x-if="formMethod !== 'POST'">
                        <input type="hidden" name="_method" :value="formMethod">
                    </template>
                    <template x-if="statusValue !== null">
                        <input type="hidden" name="status" :value="statusValue">
                    </template>
                    <button type="submit"
                            :class="{
                                'bg-rose-600 hover:bg-rose-700': buttonColor === 'red',
                                'bg-amber-700 hover:bg-amber-800': buttonColor === 'amber',
                                'bg-emerald-700 hover:bg-emerald-800': buttonColor === 'emerald',
                                'bg-brand-600 hover:bg-brand-700': !['red', 'amber', 'emerald'].includes(buttonColor)
                            }"
                            class="h-10 w-full px-4 rounded-xl text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-400">
                        <span x-text="buttonText"></span>
                    </button>
                </form>
            </template>
        </div>
    </div>
</div>
