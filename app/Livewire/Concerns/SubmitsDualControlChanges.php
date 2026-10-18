<?php

namespace App\Livewire\Concerns;

use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlRegistry;
use App\Services\DualControlService;
use Carbon\Carbon;

/**
 * Livewire side of dual control: the "Takes effect" choice (bound by <x-dual-control.takes-effect wire />)
 * and one way of telling the user what happened (applied / scheduled / awaiting approval / blocked).
 */
trait SubmitsDualControlChanges
{
    public string $takesEffect = 'now';

    public ?string $takesEffectAt = null;

    /**
     * Submit a change; flashes the outcome. Returns false when it was refused (e.g. another change is open).
     */
    protected function submitDualControl(string $operation, string $resourceType, int $resourceId, array $payload, string $appliedMessage, bool $useTakesEffect = true): bool
    {
        $commenceAt = null;
        if ($useTakesEffect && $this->takesEffect === 'at' && DualControlRegistry::canSchedule($operation)) {
            $this->validate(
                ['takesEffectAt' => 'required|date|after:now'],
                ['takesEffectAt.required' => 'Choose when the change takes effect.', 'takesEffectAt.after' => 'The start must be in the future.']
            );
            $commenceAt = Carbon::parse($this->takesEffectAt);
        }

        $dualControl = app(DualControlService::class);
        try {
            $request = $dualControl->submit($operation, $resourceType, $resourceId, $payload, ['commence_at' => $commenceAt]);
        } catch (DualControlException $e) {
            session()->flash('error', $e->getMessage());

            return false;
        }

        session()->flash('success', $dualControl->outcomeMessage($request, $appliedMessage));
        $this->reset(['takesEffect', 'takesEffectAt']);

        return true;
    }
}
