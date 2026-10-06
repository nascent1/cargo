<div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-4 sidebar-scroll border-r border-surface-200/80 relative">
    {{-- Decorative accent strip --}}
    <div class="absolute inset-y-0 left-0 w-[4px] bg-brand-600"></div>

    <!-- Logo -->
    <div class="flex py-5 shrink-0 items-center justify-between border-b border-surface-200/60 mb-2">
        <div class="flex items-center gap-4">
            <img src="{{ asset('images/logo.png') }}" alt="{{ \App\Models\SystemConfig::getValue('platform_name', 'MAFIA FAST FERRIES') }}" class="h-14 w-auto object-contain drop-shadow-sm">
            <div class="flex flex-col justify-center">
                <h2 class="text-base font-extrabold text-surface-900 tracking-wide leading-tight">{{ \App\Models\SystemConfig::getValue('platform_name', 'MAFIA FAST FERRIES') }}</h2>
                <p class="text-[10px] text-brand-600 font-bold uppercase tracking-widest mt-0.5">Booking System</p>
            </div>
        </div>
        <button type="button" @click="sidebarOpen = false" class="lg:hidden rounded-md p-2 -mr-2 text-surface-400 hover:bg-surface-100 hover:text-surface-600 focus:outline-none">
            <span class="sr-only">Close sidebar</span>
            <i class="fas fa-times text-xl w-6 h-6 flex items-center justify-center"></i>
        </button>
    </div>

    <nav class="flex flex-1 flex-col">
        <ul role="list" class="flex flex-1 flex-col gap-y-7">
            @php
                $allMenus = \App\Models\Menu::getMenuTree();
                $mainMenus = $allMenus->where('section', 'main');
                $adminMenus = $allMenus->where('section', 'administration');
            @endphp

            {{-- Main Navigation --}}
            @if($mainMenus->isNotEmpty())
            <li>
                <ul role="list" class="-mx-2 space-y-1">
                    @foreach($mainMenus as $menu)
                        @if($menu->canAccess(auth()->user()))
                            @if($menu->children->isNotEmpty())
                                @php
                                    $canAccessAnyChild = $menu->children->contains(fn($c) => $c->canAccess(auth()->user()));
                                @endphp
                                @if($canAccessAnyChild)
                                <div class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-2 px-3 mt-4">{{ $menu->title }}</div>
                                @foreach($menu->children as $child)
                                    @if($child->canAccess(auth()->user()))
                                        @php
                                            $isActive = $child->route && request()->routeIs($child->route . '*');
                                        @endphp
                                        <li>
                                            <a href="{{ $child->route ? route($child->route) : '#' }}"
                                               class="group flex gap-x-3 rounded-r-lg px-3 py-2 text-sm font-medium transition-all duration-150
                                                   {{ $isActive ? 'bg-brand-50 text-brand-700 border-l-4 border-brand-600 shadow-sm' : 'text-surface-600 hover:text-surface-900 hover:bg-surface-100/50 border-l-4 border-transparent' }}">
                                                <i class="fas {{ $child->icon }} w-5 text-center {{ $isActive ? 'text-brand-600' : 'text-surface-500 group-hover:text-surface-700' }}"></i>
                                                {{ $child->title }}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                                @endif
                            @else
                                @php
                                    $isActive = $menu->route && request()->routeIs($menu->route . '*');
                                @endphp
                                <li>
                                    <a href="{{ $menu->route ? route($menu->route) : '#' }}"
                                       class="group flex gap-x-3 rounded-r-lg px-3 py-2 text-sm font-medium transition-all duration-150
                                           {{ $isActive ? 'bg-brand-50 text-brand-700 border-l-4 border-brand-600 shadow-sm' : 'text-surface-600 hover:text-surface-900 hover:bg-surface-100/50 border-l-4 border-transparent' }}">
                                        <i class="fas {{ $menu->icon }} w-5 text-center
                                            {{ $isActive ? 'text-brand-600' : 'text-surface-500 group-hover:text-surface-700' }}"></i>
                                        {{ $menu->title }}
                                    </a>
                                </li>
                            @endif
                        @endif
                    @endforeach
                </ul>
            </li>
            @endif

            {{-- Administration Section --}}
            @if($adminMenus->isNotEmpty())
            @php
                $canAccessAdmin = $adminMenus->contains(function($menu) {
                    if ($menu->canAccess(auth()->user())) return true;
                    return $menu->children->contains(fn($c) => $c->canAccess(auth()->user()));
                });
            @endphp
            @if($canAccessAdmin)
            <li>
                <ul role="list" class="-mx-2 space-y-1">
                    @foreach($adminMenus as $menu)
                        @if($menu->children->isNotEmpty())
                            @php
                                $canAccessAnyChild = $menu->children->contains(fn($c) => $c->canAccess(auth()->user()));
                            @endphp
                            @if($canAccessAnyChild)
                            <div class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-2 px-3 mt-4">{{ $menu->title }}</div>
                            @foreach($menu->children as $child)
                                @if($child->canAccess(auth()->user()))
                                    @php
                                        $isActive = $child->route && request()->routeIs($child->route . '*');
                                    @endphp
                                    <li>
                                            <a href="{{ $child->route ? route($child->route) : '#' }}"
                                               class="group flex gap-x-3 rounded-r-lg px-3 py-2 text-sm font-medium transition-all duration-150
                                                   {{ $isActive ? 'bg-brand-50 text-brand-700 border-l-4 border-brand-600 shadow-sm' : 'text-surface-600 hover:text-surface-900 hover:bg-surface-100/50 border-l-4 border-transparent' }}">
                                                <i class="fas {{ $child->icon }} w-5 text-center {{ $isActive ? 'text-brand-600' : 'text-surface-500 group-hover:text-surface-700' }}"></i>
                                                {{ $child->title }}
                                            </a>
                                    </li>
                                @endif
                            @endforeach
                            @endif
                        @else
                            @if($menu->canAccess(auth()->user()))
                                @php
                                    $isActive = $menu->route && request()->routeIs($menu->route . '*');
                                @endphp
                                <li>
                                    <a href="{{ $menu->route ? route($menu->route) : '#' }}"
                                       class="group flex gap-x-3 rounded-r-lg px-3 py-2 text-sm font-medium transition-all duration-150
                                           {{ $isActive ? 'bg-brand-50 text-brand-700 border-l-4 border-brand-600 shadow-sm' : 'text-surface-600 hover:text-surface-900 hover:bg-surface-100/50 border-l-4 border-transparent' }}">
                                        <i class="fas {{ $menu->icon }} w-5 text-center {{ $isActive ? 'text-brand-600' : 'text-surface-500 group-hover:text-surface-700' }}"></i>
                                        {{ $menu->title }}
                                    </a>
                                </li>
                            @endif
                        @endif
                    @endforeach
                </ul>
            </li>
            @endif
            @endif

        </ul>
    </nav>
</div>

<style>
.sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 9999px; }
</style>
