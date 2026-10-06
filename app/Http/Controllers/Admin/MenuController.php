<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Permission;
use App\Services\DualControlService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    protected DualControlService $dcService;

    public function __construct(DualControlService $dcService)
    {
        $this->dcService = $dcService;
    }
    public function index()
    {
        $menus = Menu::with('children')->whereNull('parent_id')->orderBy('section')->orderBy('sort_order')->get();
        return view('admin.settings.menus', compact('menus'));
    }

    public function create()
    {
        $parentMenus = Menu::whereNull('parent_id')->orderBy('title')->get();
        $permissions = Permission::orderBy('name')->get();
        return view('admin.menus.create', compact('parentMenus', 'permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'route' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'permission_name' => 'nullable|string|max:255',
            'section' => 'required|string|in:main,administration',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $this->dcService->submit('menu_create', 'menu', 0, ['after' => $validated]);

        return redirect()->route('admin.settings.menus.index')->with('success', 'Menu creation submitted for authorization.');
    }

    public function edit(Menu $menu)
    {
        $parentMenus = Menu::whereNull('parent_id')->where('id', '!=', $menu->id)->orderBy('title')->get();
        $permissions = Permission::orderBy('name')->get();
        return view('admin.menus.edit', compact('menu', 'parentMenus', 'permissions'));
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'route' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'permission_name' => 'nullable|string|max:255',
            'section' => 'required|string|in:main,administration',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $this->dcService->submit('menu_update', 'menu', $menu->id, [
            'before' => $menu->toArray(),
            'after' => $validated
        ]);

        return redirect()->route('admin.settings.menus.index')->with('success', 'Menu update submitted for authorization.');
    }

    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'menus' => 'required|array',
            'menus.*.id' => 'required|exists:menus,id',
            'menus.*.sort_order' => 'required|integer|min:0',
            'menus.*.is_active' => 'sometimes|boolean',
        ]);

        foreach ($validated['menus'] as $menuData) {
            Menu::where('id', $menuData['id'])->update([
                'sort_order' => $menuData['sort_order'],
                'is_active' => $menuData['is_active'] ?? false,
            ]);
        }

        return redirect()->route('admin.settings.menus.index')->with('success', 'Menu order updated.');
    }

    public function destroy(Menu $menu)
    {
        if ($menu->children()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete menu with children. Delete children first.');
        }

        $this->dcService->submit('menu_delete', 'menu', $menu->id, [
            'before' => $menu->toArray()
        ]);

        return redirect()->route('admin.settings.menus.index')->with('success', 'Menu deletion submitted for authorization.');
    }
}
