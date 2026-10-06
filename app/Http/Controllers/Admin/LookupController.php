<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\DualControlService;

class LookupController extends Controller
{
    protected DualControlService $dcService;

    public function __construct(DualControlService $dcService)
    {
        $this->dcService = $dcService;
    }
    /**
     * Map of valid status/lookup tables and their human-readable labels.
     */
    protected array $allowedTables = [
        'payment_statuses' => [
            'title' => 'Payment Statuses',
            'description' => 'Financial transaction states for mobile money, bank, and card payments.',
            'model' => \App\Models\PaymentStatus::class,
            'fields' => ['slug', 'name', 'color'],
        ],
        'transaction_categories' => [
            'title' => 'Transaction Categories',
            'description' => 'Types of financial transactions (ticket_sale, float_topup, refund, commission, penalty).',
            'model' => \App\Models\TransactionCategory::class,
            'fields' => ['slug', 'name', 'color', 'sort_order'],
        ],
        'transaction_statuses' => [
            'title' => 'Transaction Statuses',
            'description' => 'Financial transaction processing states (pending, completed, failed, reversed).',
            'model' => \App\Models\TransactionStatus::class,
            'fields' => ['slug', 'name', 'color', 'sort_order'],
        ],
        'id_types' => [
            'title' => 'Identity Document Types',
            'description' => 'Valid ID document types accepted during passenger registration and ticket booking.',
            'model' => \App\Models\IdType::class,
            'fields' => ['code', 'label', 'sort_order'],
        ],
    ];

    /**
     * Display the Lookups & Statuses settings interface.
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'payment_statuses');
        if (!isset($this->allowedTables[$activeTab])) {
            $activeTab = 'payment_statuses';
        }

        $config = $this->allowedTables[$activeTab];
        $items = DB::table($activeTab)->orderBy('id')->get();

        return view('admin.settings.lookups', [
            'allowedTables' => $this->allowedTables,
            'activeTab'     => $activeTab,
            'config'        => $config,
            'items'         => $items,
        ]);
    }

    /**
     * Store or update a lookup record.
     */
    public function updateOrCreate(Request $request, string $table)
    {
        if (!isset($this->allowedTables[$table])) {
            return redirect()->back()->with('error', 'Invalid lookup table specified.');
        }

        $config = $this->allowedTables[$table];
        $id = $request->input('id');

        $rules = [];
        $data = [];

        if (in_array('code', $config['fields']) || in_array('slug', $config['fields'])) {
            $rules['code'] = 'required|string|max:50';
            $codeVal = strtolower(trim($request->input('code')));
            if (in_array('code', $config['fields'])) $data['code'] = $codeVal;
            if (in_array('slug', $config['fields'])) $data['slug'] = $codeVal;
        }

        if (in_array('label', $config['fields']) || in_array('name', $config['fields'])) {
            $rules['label'] = 'required|string|max:100';
            $labelVal = trim($request->input('label'));
            if (in_array('label', $config['fields'])) $data['label'] = $labelVal;
            if (in_array('name', $config['fields'])) $data['name'] = $labelVal;
        }

        if (in_array('sort_order', $config['fields'])) {
            $rules['sort_order'] = 'nullable|integer';
            $data['sort_order'] = (int) $request->input('sort_order', 10);
        }

        if (in_array('color_badge', $config['fields']) || in_array('color', $config['fields'])) {
            $rules['color_badge'] = 'nullable|string|max:100';
            $colorVal = $request->input('color_badge', 'bg-gray-100 text-gray-800');
            if (in_array('color_badge', $config['fields'])) $data['color_badge'] = $colorVal;
            if (in_array('color', $config['fields'])) $data['color'] = $colorVal;
        }

        if (in_array('direction', $config['fields'])) {
            $rules['direction'] = 'required|in:in,out,internal';
            $data['direction'] = $request->input('direction');
        }

        if (in_array('is_terminal', $config['fields'])) {
            $data['is_terminal'] = $request->boolean('is_terminal', false);
        }

        if (in_array('description', $config['fields']) && $request->has('description')) {
            $data['description'] = $request->input('description');
        }

        $request->validate($rules);

        $payload = array_merge($data, ['_table' => $table]);

        if ($id) {
            $this->dcService->submit('lookup_update', 'lookup', $id, [
                'after' => $payload
            ]);
            $message = 'Lookup update submitted for authorization.';
        } else {
            $this->dcService->submit('lookup_create', 'lookup', 0, [
                'after' => $payload
            ]);
            $message = 'Lookup creation submitted for authorization.';
        }

        return redirect()->route('admin.settings.lookups.index', ['tab' => $table])->with('success', $message);
    }

    /**
     * Remove the specified lookup item.
     */
    public function destroy(string $table, int $id)
    {
        if (!isset($this->allowedTables[$table])) {
            return redirect()->back()->with('error', 'Invalid lookup table specified.');
        }

        $this->dcService->submit('lookup_delete', 'lookup', $id, [
            'after' => ['_table' => $table]
        ]);

        return redirect()->route('admin.settings.lookups.index', ['tab' => $table])->with('success', 'Lookup deletion submitted for authorization.');
    }
}
