<x-layouts.admin>
    <x-slot:title>Create Menu</x-slot:title>
    <x-slot:header>Settings — Create Menu</x-slot:header>

    <div class="glass-card rounded-2xl shadow-sm overflow-hidden max-w-3xl">
        <div class="px-6 py-4 bg-surface-50/80 border-b border-surface-200/60">
            <h3 class="text-sm font-semibold text-surface-900">
                <i class="fas fa-plus-circle text-brand-500 mr-2"></i> Add New Menu Item
            </h3>
        </div>

        <form method="POST" action="{{ route('admin.settings.menus.store') }}" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-surface-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                           class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-surface-900 ring-1 ring-inset ring-surface-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:text-sm sm:leading-6 bg-white">
                    @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <!-- Section -->
                <div>
                    <label for="section" class="block text-sm font-medium text-surface-700">Section <span class="text-red-500">*</span></label>
                    <select name="section" id="section" required class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-surface-900 ring-1 ring-inset ring-surface-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:text-sm sm:leading-6 bg-white">
                        <option value="main" {{ old('section') == 'main' ? 'selected' : '' }}>Main Navigation</option>
                        <option value="administration" {{ old('section') == 'administration' ? 'selected' : '' }}>Administration</option>
                    </select>
                    @error('section') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <!-- Route -->
                <div>
                    <label for="route" class="block text-sm font-medium text-surface-700">Route Name</label>
                    <input type="text" name="route" id="route" value="{{ old('route') }}" placeholder="e.g. admin.users.index"
                           class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-surface-900 ring-1 ring-inset ring-surface-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:text-sm sm:leading-6 bg-white">
                    <p class="mt-1 text-[11px] text-surface-500">Leave blank if this is a parent menu without a direct link.</p>
                    @error('route') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <!-- Icon -->
                <div>
                    <label for="icon" class="block text-sm font-medium text-surface-700">Icon Class</label>
                    <input type="text" name="icon" id="icon" value="{{ old('icon') }}" placeholder="e.g. fa-users"
                           class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-surface-900 ring-1 ring-inset ring-surface-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:text-sm sm:leading-6 bg-white font-mono">
                    @error('icon') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <!-- Parent Menu -->
                <div>
                    <label for="parent_id" class="block text-sm font-medium text-surface-700">Parent Menu</label>
                    <select name="parent_id" id="parent_id" class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-surface-900 ring-1 ring-inset ring-surface-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:text-sm sm:leading-6 bg-white">
                        <option value="">-- No Parent (Root Level) --</option>
                        @foreach($parentMenus as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->title }} ({{ $parent->section }})
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <!-- Permission -->
                <div>
                    <label for="permission_name" class="block text-sm font-medium text-surface-700">Required Permission</label>
                    <select name="permission_name" id="permission_name" class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-surface-900 ring-1 ring-inset ring-surface-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:text-sm sm:leading-6 bg-white">
                        <option value="">-- No Permission Required --</option>
                        @foreach($permissions as $permission)
                            <option value="{{ $permission->name }}" {{ old('permission_name') == $permission->name ? 'selected' : '' }}>
                                {{ $permission->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('permission_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <!-- Sort Order -->
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-surface-700">Sort Order <span class="text-red-500">*</span></label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" required min="0"
                           class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-surface-900 ring-1 ring-inset ring-surface-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:text-sm sm:leading-6 bg-white">
                    @error('sort_order') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <!-- Is Active -->
                <div class="flex items-center pt-6">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-surface-200 peer-focus:ring-4 peer-focus:ring-brand-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-surface-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-500"></div>
                        <span class="ml-3 text-sm font-medium text-surface-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3 border-t border-surface-100 pt-5">
                <a href="{{ route('admin.settings.menus.index') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-surface-900 shadow-sm ring-1 ring-inset ring-surface-300 hover:bg-surface-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 transition-colors">
                    <i class="fas fa-save mr-2"></i> Save Menu
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
