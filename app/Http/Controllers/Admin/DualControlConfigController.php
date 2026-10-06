<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DualControlConfig;
use Illuminate\Http\Request;

class DualControlConfigController extends Controller
{
    public function index()
    {
        $configs = DualControlConfig::orderBy('operation_type')->get();
        return view('admin.dual_control_configs.index', compact('configs'));
    }

    public function toggle(Request $request, DualControlConfig $dualControlConfig)
    {
        $dualControlConfig->update([
            'requires_dual_control' => !$dualControlConfig->requires_dual_control
        ]);

        return back()->with('success', 'Configuration updated successfully.');
    }
}
