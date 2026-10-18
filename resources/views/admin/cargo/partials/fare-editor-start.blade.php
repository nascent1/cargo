{{-- "Starts: immediately / when approved / on …" for the bulk and copy forms of the fare editor --}}
<div class="rounded-xl border px-4 py-3 space-y-2" :class="needsApproval() ? 'border-amber-200 bg-amber-50/60' : 'border-surface-200 bg-surface-50'">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2" role="radiogroup" aria-label="Starts">
        <span class="text-xs font-semibold text-surface-700">Starts</span>
        <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" value="now" x-model="f.takes_effect" class="text-brand-600"> <span x-text="needsApproval() ? 'When approved' : 'Immediately'"></span></label>
        <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" value="at" x-model="f.takes_effect" class="text-brand-600"> On</label>
        <input type="datetime-local" x-model="f.takes_effect_at" x-show="f.takes_effect === 'at'" :min="minStart()" :required="f.takes_effect === 'at'" aria-label="Start date and time" class="fe-field w-auto py-1.5">
    </div>
    <p class="text-xs" :class="needsApproval() ? 'text-amber-800' : 'text-surface-600'" x-text="needsApproval() ? 'Needs a checker\'s approval. Approved before the start, it starts then; approved later, it starts when approved.' : 'Applies without approval.'"></p>
    <p class="fe-error" x-show="errors.takes_effect_at" x-text="errors.takes_effect_at"></p>
</div>
