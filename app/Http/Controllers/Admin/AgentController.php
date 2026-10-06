<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\AssociateAgent;
use App\Models\User;
use App\Services\FloatManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    protected FloatManagementService $floatService;
    protected \App\Services\DualControlService $dcService;

    public function __construct(FloatManagementService $floatService, \App\Services\DualControlService $dcService)
    {
        $this->floatService = $floatService;
        $this->dcService = $dcService;
    }

    public function index(Request $request)
    {
        $agents = Agent::with(['user', 'account'])
            ->withCount('associates')
            ->when($request->search, function ($query, $search) {
                $query->where('agency_name', 'like', "%{$search}%")
                      ->orWhere('agent_code', 'like', "%{$search}%")
                      ->orWhereHas('user', fn($q) => $q->where('email', 'like', "%{$search}%"));
            })
            ->when($request->type, fn($q, $t) => $q->where('agent_type', $t))
            ->when($request->status !== null && $request->status !== '', fn($q) => $q->where('is_active', (bool)$request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.agents.index', compact('agents'));
    }

    public function show(Agent $agent)
    {
        $agent->load(['user', 'account', 'associates.user', 'ferries', 'routes']);
        $allFerries = \App\Models\Ferry::where('status', 'active')->orderBy('name')->get();
        $allRoutes = \App\Models\Route::where('status', 'active')->orderBy('name')->get();
        $transactions = $agent->transactions()->with('performedBy')->paginate(20);

        return view('admin.agents.show', compact('agent', 'transactions', 'allFerries', 'allRoutes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'agency_name'           => 'required|string|max:190',
            'email'                 => 'required|email|max:255|unique:users,email',
            'first_name'            => 'required|string|max:100',
            'last_name'             => 'required|string|max:100',
            'contact_mobile'        => 'nullable|string|max:25|unique:users,phone',
            'address'               => 'nullable|string',
            'agent_type'            => 'required|in:direct,reseller',
        ]);

        $dcReq = $this->dcService->submit('agent_create', 'agent', 0, [
            'after' => $validated,
        ]);

        if ($dcReq->status === 'pending') {
            return redirect()->route('admin.agents.index')->with('success', "Agency partner creation requires Maker-Checker approval (Request #{$dcReq->id} submitted).");
        }

        return redirect()->route('admin.agents.index')->with('success', 'Agency partner account registered successfully.');
    }

    public function update(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'agency_name'       => 'required|string|max:190',
            'contact_mobile'    => 'nullable|string|max:25',
            'address'           => 'nullable|string',
            'agent_type'        => 'required|in:direct,reseller',
            'commission_method' => 'nullable|in:post_sale,pre_deposit',
            'commission_rate'   => 'nullable|numeric|min:0|max:100',
            'is_active'         => 'boolean',
        ]);

        if ($this->dcService->requiresDualControl('agent_update')) {
            $dcReq = $this->dcService->submit('agent_update', 'agent', $agent->id, [
                'before' => $agent->toArray(),
                'after'  => $validated,
            ]);
            return back()->with('success', "Agency details update requires Maker-Checker approval (Request #{$dcReq->id} submitted).");
        }

        $agent->update([
            'agency_name'       => $validated['agency_name'],
            'contact_mobile'    => $validated['contact_mobile'] ?? null,
            'address'           => $validated['address'] ?? null,
            'agent_type'        => $validated['agent_type'],
            'commission_method' => !empty($validated['commission_method']) ? $validated['commission_method'] : null,
            'commission_rate'   => $validated['commission_rate'] !== null && $validated['commission_rate'] !== '' ? (float)$validated['commission_rate'] : null,
            'is_active'         => $request->has('is_active') ? (bool)$request->is_active : $agent->is_active,
        ]);

        return back()->with('success', 'Agency details updated successfully.');
    }

    public function updateFerries(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'ferries'            => 'nullable|array',
            'ferries.*.selected' => 'nullable|boolean',
            'ferries.*.status'   => 'nullable|in:active,suspended',
        ]);

        $syncData = [];
        if (!empty($validated['ferries'])) {
            foreach ($validated['ferries'] as $ferryId => $data) {
                if (!empty($data['selected'])) {
                    $syncData[$ferryId] = ['status' => $data['status'] ?? 'active'];
                }
            }
        }

        if ($this->dcService->requiresDualControl('agent_ferries_update')) {
            $dcReq = $this->dcService->submit('agent_ferries_update', 'agent', $agent->id, [
                'before' => ['ferries' => $agent->ferries->mapWithKeys(fn($f) => [$f->id => ['status' => $f->pivot->status]])->toArray()],
                'after'  => ['ferries' => $syncData],
            ]);
            return back()->with('success', "Assigned fleet update requires Maker-Checker approval (Request #{$dcReq->id} submitted).");
        }

        $agent->ferries()->sync($syncData);

        return back()->with('success', 'Assigned fleet authorization updated successfully.');
    }

    public function updateRoutes(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'routes'            => 'nullable|array',
            'routes.*.selected' => 'nullable|boolean',
            'routes.*.status'   => 'nullable|in:active,suspended',
        ]);

        $syncData = [];
        if (!empty($validated['routes'])) {
            foreach ($validated['routes'] as $routeId => $data) {
                if (!empty($data['selected'])) {
                    $syncData[$routeId] = ['status' => $data['status'] ?? 'active'];
                }
            }
        }

        logger()->info('Validated routes:', $validated['routes'] ?? []);
        logger()->info('Syncing routes:', $syncData);

        if ($this->dcService->requiresDualControl('agent_routes_update')) {
            $dcReq = $this->dcService->submit('agent_routes_update', 'agent', $agent->id, [
                'before' => ['routes' => $agent->routes->mapWithKeys(fn($r) => [$r->id => ['status' => $r->pivot->status]])->toArray()],
                'after'  => ['routes' => $syncData],
            ]);
            return back()->with('success', "Assigned routes update requires Maker-Checker approval (Request #{$dcReq->id} submitted).");
        }

        $agent->routes()->sync($syncData);
        logger()->info('Routes synced!');

        return back()->with('success', 'Assigned routes authorization updated successfully.');
    }

    public function topup(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'amount'            => 'required|numeric|min:100',
            'payment_reference' => 'required|string|max:100',
        ]);

        $result = $this->floatService->requestTopup(
            $agent->account,
            (float) $validated['amount'],
            $validated['payment_reference'],
            auth()->user()
        );

        if ($result instanceof \App\Models\DualControlRequest) {
            return back()->with('success', "Float Top-Up of " . number_format($validated['amount'], 2) . " TZS requires Maker-Checker approval (Request #{$result->id} submitted).");
        }

        return back()->with('success', "Float Top-Up of " . number_format($validated['amount'], 2) . " TZS credited to Account #{$agent->account->account_number} successfully.");
    }

    public function updateCreditLimit(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'credit_limit' => 'required|numeric|min:0',
        ]);

        if ($this->dcService->requiresDualControl('agent_adjust_limit')) {
            $dcReq = $this->dcService->submit('agent_adjust_limit', 'agent_account', $agent->account->id, [
                'before' => ['credit_limit' => $agent->account->credit_limit],
                'after'  => ['credit_limit' => $validated['credit_limit']],
            ]);
            return back()->with('success', "Credit limit adjustment requires Maker-Checker approval (Request #{$dcReq->id} submitted).");
        }

        $this->floatService->updateCreditLimit($agent->account, (float) $validated['credit_limit']);

        return back()->with('success', 'Credit limit updated successfully.');
    }

    public function storeAssociate(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'associate_name'     => 'required|string|max:190',
            'email'              => 'required|email|max:255|unique:users,email',
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'contact_mobile'     => 'nullable|string|max:25|unique:users,phone',
            'daily_credit_limit' => 'nullable|numeric|min:0',
        ]);

        if ($this->dcService->requiresDualControl('associate_create')) {
            $dcReq = $this->dcService->submit('associate_create', 'associate_agent', 0, [
                'after' => $validated + ['agent_id' => $agent->id],
            ]);
            return back()->with('success', "Associate staff registration requires Maker-Checker approval (Request #{$dcReq->id} submitted).");
        }

        DB::transaction(function () use ($validated, $agent) {
            $plainPassword = \Illuminate\Support\Str::password(10, true, true, false, false);
            $user = User::create([
                'email'      => $validated['email'],
                'phone'      => $validated['contact_mobile'] ?? null,
                'password'   => Hash::make($plainPassword),
                'password_changed_at' => null,
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'domain'     => 'associate_agent',
                'status'     => 'active',
            ]);

            AssociateAgent::create([
                'user_id'            => $user->id,
                'agent_id'           => $agent->id,
                'associate_name'     => $validated['associate_name'],
                'contact_mobile'     => $validated['contact_mobile'] ?? null,
                'email'              => $validated['email'],
                'daily_credit_limit' => round((float) ($validated['daily_credit_limit'] ?? 0), 4),
                'is_active'          => true,
            ]);

            $user->assignRole('associate_agent');

            if (!empty($user->phone)) {
                $sms = new \App\Services\SmsService();
                $sms->sendSms($user->phone, "Welcome to Victoria Ferries. Your Associate account is created. Username: {$user->email}, Password: {$plainPassword}");
            }
        });

        return back()->with('success', 'Associate staff member registered successfully.');
    }

    public function updateAssociate(Request $request, Agent $agent, AssociateAgent $associate)
    {
        $validated = $request->validate([
            'associate_name'     => 'required|string|max:190',
            'contact_mobile'     => 'nullable|string|max:25',
            'daily_credit_limit' => 'nullable|numeric|min:0',
            'is_active'          => 'boolean',
        ]);

        if ($this->dcService->requiresDualControl('associate_update')) {
            $dcReq = $this->dcService->submit('associate_update', 'associate_agent', $associate->id, [
                'before' => $associate->toArray(),
                'after'  => $validated,
            ]);
            return back()->with('success', "Associate staff update requires Maker-Checker approval (Request #{$dcReq->id} submitted).");
        }

        DB::transaction(function () use ($validated, $associate, $request) {
            $associate->update([
                'associate_name'     => $validated['associate_name'],
                'contact_mobile'     => $validated['contact_mobile'] ?? null,
                'daily_credit_limit' => round((float) ($validated['daily_credit_limit'] ?? 0), 4),
                'is_active'          => $request->has('is_active') ? (bool)$request->is_active : $associate->is_active,
            ]);

            if ($associate->user) {
                $associate->user->update([
                    'phone'  => $validated['contact_mobile'] ?? null,
                    'status' => $associate->is_active ? 'active' : 'inactive',
                ]);
            }
        });

        return back()->with('success', 'Associate staff details updated successfully.');
    }

    public function destroyAssociate(Agent $agent, AssociateAgent $associate)
    {
        if ($this->dcService->requiresDualControl('associate_update')) {
            $dcReq = $this->dcService->submit('associate_update', 'associate_agent', $associate->id, [
                'before' => $associate->toArray(),
                'after'  => ['is_active' => false, '_deleted' => true],
            ]);
            return back()->with('success', "Associate staff deletion requires Maker-Checker approval (Request #{$dcReq->id} submitted).");
        }

        DB::transaction(function () use ($associate) {
            $associate->update(['is_active' => false]);
            if ($associate->user) {
                $associate->user->update(['status' => 'inactive']);
            }
            $associate->delete();
        });

        return back()->with('success', 'Associate staff member removed successfully.');
    }
}
