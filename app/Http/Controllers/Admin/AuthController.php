<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !in_array($user->domain, ['system_user', 'agent', 'associate_agent'])) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not belong to an authorized staff or agent account.'],
            ]);
        }

        if ($user->isLocked()) {
            AccessLog::log('failed_login', $user->id, ['reason' => 'account_locked']);
            throw ValidationException::withMessages([
                'email' => ['Your account is temporarily locked. Please try again later.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact support.'],
            ]);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            $user->incrementFailedLogins();
            AccessLog::log('failed_login', $user->id, ['reason' => 'invalid_password']);
            AuditLog::createEntry($user->id, 'failed_login', 'User', $user->id, null, ['success' => false], $request->ip());

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $allPermissions = $user->getAllPermissions();
        if ($allPermissions->isEmpty()) {
            AccessLog::log('failed_login', $user->id, ['reason' => 'no_permissions']);
            throw ValidationException::withMessages([
                'email' => ['You do not have sufficient privileges. Please contact the administrator.'],
            ]);
        }

        $user->resetFailedLogins();
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->save();

        AccessLog::log('login', $user->id);
        AuditLog::createEntry($user->id, 'login', 'User', $user->id, null, ['success' => true], $request->ip());

        if ($user->can('admin_portal:access')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user->can('portal:access')) {
            $request->session()->forget('url.intended');
            return redirect('/portal');
        }

        Auth::logout();
        throw ValidationException::withMessages([
            'email' => ['You do not have permission to access any portal.'],
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AccessLog::log('logout', $user->id);
            AuditLog::createEntry($user->id, 'logout', 'User', $user->id, null, null, $request->ip());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
