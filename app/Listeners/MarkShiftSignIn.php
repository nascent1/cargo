<?php

namespace App\Listeners;

use App\Http\Middleware\StartShiftOnSignIn;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Auth\Events\Login;

/**
 * A sales officer signed in (password, or "remember me"): note it in the session. The shift starts
 * when they reach the sales portal past 2FA (StartShiftOnSignIn), so a password alone never starts one.
 * Picked up by event discovery — don't register it by hand as well.
 */
class MarkShiftSignIn
{
    public function __construct(private ShiftService $shifts)
    {
    }

    public function handle(Login $event): void
    {
        $request = request();
        if ($event->user instanceof User && $request->hasSession() && $this->shifts->startsOnSignIn($event->user)) {
            $request->session()->put(StartShiftOnSignIn::PENDING, true);
        }
    }
}
