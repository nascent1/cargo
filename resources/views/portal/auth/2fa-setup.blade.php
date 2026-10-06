<x-layouts.admin>
    <x-slot:title>2FA Setup</x-slot:title>
    <x-slot:header>Two-Factor Authentication</x-slot:header>

    <div class="max-w-2xl mx-auto bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden mt-6">
        <div class="p-8 border-b border-slate-100 bg-slate-50 text-center">
            <div class="h-16 w-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Setup Two-Factor Authentication</h2>
            <p class="text-slate-500 mt-2 text-sm">Enhance your account security by requiring an authentication code upon login.</p>
        </div>

        <div class="p-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200 text-sm">
                    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                </div>
            @endif

            @if($is_enabled)
                <div class="text-center py-6">
                    <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                        <i class="fas fa-shield-check"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">2FA is currently active</h3>
                    <p class="text-slate-500 mt-2 text-sm max-w-md mx-auto">Your account is secured with Two-Factor Authentication. If you wish to use a different device or disable this feature, click below.</p>
                    
                    <div x-data="{ showDisableModal: false }">
                        <button @click="showDisableModal = true" type="button" class="mt-8 px-6 py-3 bg-red-50 hover:bg-red-100 text-red-600 font-bold rounded-xl border border-red-200 transition-colors">
                            <i class="fas fa-power-off mr-2"></i> Disable 2FA
                        </button>

                        <!-- Alpine Modal -->
                        <div x-show="showDisableModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
                            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                <div x-show="showDisableModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-slate-900/75 backdrop-blur-sm" aria-hidden="true"></div>

                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                                <div x-show="showDisableModal" @click.away="showDisableModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 border border-slate-100">
                                    <div class="sm:flex sm:items-start">
                                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10 text-red-600">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                            <h3 class="text-lg font-medium leading-6 text-slate-900" id="modal-title">Disable Two-Factor Authentication</h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-slate-500">Are you sure you want to disable 2FA? This will significantly reduce the security of your account and leave it vulnerable to unauthorized access.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                                        <form action="{{ route('admin.2fa.disable') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">
                                                Yes, Disable 2FA
                                            </button>
                                        </form>
                                        <button @click="showDisableModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:mt-0 sm:w-auto sm:text-sm">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <ol class="list-decimal list-inside space-y-6 text-slate-700 text-sm">
                    <li class="pl-2">
                        <strong>Download an Authenticator App</strong>
                        <p class="text-slate-500 ml-5 mt-1">Download Google Authenticator, Authy, or Microsoft Authenticator on your mobile device.</p>
                    </li>
                    <li class="pl-2">
                        <strong>Scan the QR Code</strong>
                        <p class="text-slate-500 ml-5 mt-1">Open your authenticator app and scan the QR code below.</p>
                        <div class="flex justify-center mt-4">
                            <div class="p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                                {!! $qrCodeUrl !!}
                            </div>
                        </div>
                        <p class="text-center text-xs text-slate-400 mt-3 font-mono">Secret Key: {{ $secret }}</p>
                    </li>
                    <li class="pl-2">
                        <strong>Verify Setup</strong>
                        <p class="text-slate-500 ml-5 mt-1">Enter the 6-digit code generated by your app to verify and enable 2FA.</p>
                        
                        <form action="{{ route('admin.2fa.confirm') }}" method="POST" class="mt-4 ml-5">
                            @csrf
                            <div class="flex gap-3 max-w-sm">
                                <input type="text" name="one_time_password" placeholder="123456" required autocomplete="off" class="flex-1 px-4 py-2.5 rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-center text-lg tracking-widest font-mono">
                                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-sm transition-colors">
                                    Verify
                                </button>
                            </div>
                            @error('one_time_password')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror
                        </form>
                    </li>
                </ol>
            @endif
        </div>
    </div>
</x-layouts.admin>
