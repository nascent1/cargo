<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DualControlConfig;
use App\Models\DualControlRequest;
use App\Services\DualControl\DualControlRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DualControlConfigController extends Controller
{
    public function index(Request $request)
    {
        $showLegacy = $request->boolean('legacy');
        $search = Str::lower(trim((string) $request->search));

        $rows = DualControlConfig::orderBy('operation_type')->get()
            ->map(fn (DualControlConfig $c) => ['config' => $c, 'op' => $c->operation()])
            ->filter(fn ($r) => $showLegacy || !$r['op']['legacy'])
            ->filter(fn ($r) => $search === '' || Str::contains(Str::lower($r['op']['label'] . ' ' . $r['config']->description . ' ' . $r['config']->operation_type), $search));

        // Modules in registry order, unknown ones last
        $order = collect(DualControlRegistry::all())->pluck('module')->unique()->values()->flip();
        $groups = $rows->groupBy(fn ($r) => $r['op']['module'])
            ->sortBy(fn ($items, $module) => $order[$module] ?? 999);

        $pending = DualControlRequest::open()->selectRaw('operation_type, count(*) as c')->groupBy('operation_type')->pluck('c', 'operation_type');

        $lastChanges = AuditLog::with('user')->where('entity_type', 'dual_control_config')
            ->orderByDesc('id')->get()->unique('entity_id')->keyBy('entity_id');

        return view('admin.dual_control_configs.index', [
            'groups' => $groups,
            'pending' => $pending,
            'lastChanges' => $lastChanges,
            'showLegacy' => $showLegacy,
            'notEligible' => DualControlRegistry::notEligible(),
        ]);
    }

    public function toggle(Request $request, DualControlConfig $dualControlConfig)
    {
        $before = $dualControlConfig->only(['operation_type', 'requires_dual_control']);
        $dualControlConfig->update(['requires_dual_control' => !$dualControlConfig->requires_dual_control]);

        AuditLog::createEntry(auth()->id(), 'update', 'dual_control_config', $dualControlConfig->id, $before,
            $dualControlConfig->only(['operation_type', 'requires_dual_control']), $request->ip());

        $label = $dualControlConfig->operation()['label'];

        return back()->with('success', $dualControlConfig->requires_dual_control
            ? "“{$label}” now needs a checker's approval."
            : "“{$label}” now applies without approval.");
    }
}
