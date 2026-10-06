<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use App\Services\DualControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected DualControlService $dcService;

    public function __construct(DualControlService $dcService)
    {
        $this->dcService = $dcService;
    }

    public function index(Request $request)
    {
        $users = User::with('roles')
            ->visibleToAdmin()
            ->when($request->search, fn($q, $s) => $q->where('email', 'like', "%$s%")->orWhere('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%"))
            ->when($request->domain, fn($q, $d) => $q->where('domain', $d))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['roles.permissions', 'systemUser']);
        $allRoles = Role::all();
        return view('admin.users.show', compact('user', 'allRoles'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email',
            'phone'       => 'nullable|string|max:20|unique:users,phone',
            'domain'      => 'required|in:system_user,agent,associate_agent,passenger',
            'roles'       => 'nullable|array',
            'roles.*'     => 'exists:roles,id',
            'employee_id' => 'nullable|string|max:50',
            'department'  => 'nullable|string|max:100',
            'position'    => 'nullable|string|max:100',
        ]);

        // Passwords will be generated asynchronously by DualControlService::executeApproval
        // to prevent them from being scrubbed out of the JSON payloads.

        $dcReq = $this->dcService->submit('user_create', 'user', 0, ['after' => $validated]);

        if ($dcReq->status === 'approved') {
            return redirect()->route('admin.users.index')->with('success', 'System User created successfully.');
        }

        return redirect()->route('admin.users.index')->with('success', 'System User creation submitted for Dual Control authorization.');
    }

    public function updateStatus(Request $request, User $user)
    {
        $validated = $request->validate(['status' => 'required|in:active,locked,suspended,inactive']);
        $this->dcService->submit('user_status_update', 'user', $user->id, [
            'before' => ['status' => $user->status],
            'after' => ['status' => $validated['status']]
        ]);

        return redirect()->back()->with('success', 'User status update submitted for authorization.');
    }

    public function updateRoles(Request $request, User $user)
    {
        $validated = $request->validate(['roles' => 'array', 'roles.*' => 'exists:roles,id']);
        $before = $user->roles->pluck('id')->toArray();
        
        $this->dcService->submit('user_roles_update', 'user', $user->id, [
            'before' => ['roles' => $before],
            'after' => ['roles' => $validated['roles'] ?? []]
        ]);

        return redirect()->back()->with('success', 'Roles update submitted for authorization.');
    }

    public function reset2fa(User $user)
    {
        $user->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'two_factor_confirmed_at' => null
        ]);
        
        return back()->with('success', "2FA has been reset for {$user->full_name}. They can now set it up again upon their next login.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => null, // Force change on next login
        ]);

        AuditLog::createEntry(
            auth()->id(),
            'admin_reset_password',
            'user',
            $user->id,
            null,
            ['password_changed_at' => null],
            $request->ip()
        );

        $user->notify(new \App\Notifications\AdminPasswordResetNotification($validated['password']));

        return back()->with('success', "Password has been reset for {$user->full_name}. They will be forced to change it upon their next login.");
    }
}
