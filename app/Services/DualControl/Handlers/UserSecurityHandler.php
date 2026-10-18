<?php

namespace App\Services\DualControl\Handlers;

use App\Models\AuditLog;
use App\Models\DualControlRequest;
use App\Models\User;
use App\Notifications\AdminPasswordResetNotification;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use App\Services\DualControlService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Hash;

/**
 * Password and two-factor resets done for another user. The new password travels sealed (encrypted)
 * in the request and is only decrypted here, to hash it and send it to the user.
 */
class UserSecurityHandler implements DualControlHandler
{
    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $user = User::find($request->resource_id) ?? throw new DualControlException('This user no longer exists.');

        if ($request->operation_type === 'user_2fa_reset') {
            $user->update(['mfa_enabled' => false, 'mfa_secret' => null, 'two_factor_confirmed_at' => null]);
            AuditLog::createEntry($request->maker_id, 'admin_reset_2fa', 'user', $user->id, null, ['mfa_enabled' => false], null);

            return;
        }

        $password = DualControlService::unseal($request->payload_after ?? [])['password'] ?? null;
        if (!$password) {
            throw new DualControlException('The new password is missing from this request.');
        }

        $user->update(['password' => Hash::make($password), 'password_changed_at' => null]); // forced change on next login
        AuditLog::createEntry($request->maker_id, 'admin_reset_password', 'user', $user->id, null, ['password_changed_at' => null], null);
        $user->notify(new AdminPasswordResetNotification($password));
    }
}
