<div>
    @if($isOpen)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.close()">
        <!-- Backdrop with modern blur effect -->
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="close"></div>

        <!-- Panel Wrapper -->
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden border border-gray-100">

            <!-- Elegant Header Area -->
            <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between z-10">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <h2 class="text-lg font-bold text-gray-900 tracking-tight">Account Settings</h2>
                </div>
                <button wire:click="close" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Scrollable Content Body -->
            <div class="p-6 overflow-y-auto space-y-8">

                {{-- SECTION 1: Profile Details --}}
                <div class="space-y-5">
                    <div class="border-b border-gray-50 pb-2">
                        <h3 class="font-bold text-gray-900 text-base">Profile Information</h3>
                    </div>

                    @if (session('profile_status'))
                        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm rounded-xl px-4 py-3 flex items-center space-x-2">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ session('profile_status') }}</span>
                        </div>
                    @endif

                    <form wire:submit="updateProfile" class="space-y-5">

                        {{-- Centered Photo Setup --}}
                        <div class="flex flex-col items-center justify-center space-y-3 bg-gray-50/50 p-4 rounded-2xl border border-gray-100/80">
                            <div class="relative group">
                                <div class="w-20 h-20 rounded-full bg-white p-1 ring-2 ring-yellow-400/70 overflow-hidden flex-shrink-0 shadow-sm">
                                    <div class="w-full h-full rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                                        @if($photo)
                                            <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover" alt="Preview">
                                        @elseif(auth()->user()->avatarUrl())
                                            <img src="{{ auth()->user()->avatarUrl() }}" class="w-full h-full object-cover" alt="Current photo">
                                        @else
                                            <span class="text-2xl font-bold text-gray-400">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="absolute bottom-0 right-0 bg-gray-900 text-white p-1.5 rounded-full shadow-md border border-white">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <label class="inline-block cursor-pointer text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl px-4 py-2 hover:bg-gray-50 hover:border-gray-300 shadow-sm transition">
                                    <span wire:loading.remove wire:target="photo">Change Photo</span>
                                    <span wire:loading wire:target="photo" class="inline-flex items-center space-x-1.5"><x-edlink-loader size="12" /><span>Uploading…</span></span>
                                    <input type="file" wire:model="photo" accept="image/*" class="hidden">
                                </label>
                                <p class="text-[11px] text-gray-400 mt-1.5">JPG or PNG up to 2MB</p>
                                @error('photo') <span class="text-rose-600 text-xs block mt-1 font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Full Name --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Full Name</label>
                            <div class="relative">
                                <input type="text" wire:model="name" required
                                    class="w-full border @error('name') border-rose-300 bg-rose-50/10 focus:border-rose-500 focus:ring-rose-500/10 @else border-gray-200 focus:border-yellow-500 focus:ring-yellow-500/10 @enderror rounded-xl pl-4 pr-10 py-2.5 text-sm transition focus:outline-none focus:ring-4">
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                            </div>
                            @error('name') <span class="text-rose-600 text-xs mt-1.5 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Email Address</label>
                            <div class="relative">
                                <input type="email" wire:model="email" required
                                    class="w-full border @error('email') border-rose-300 bg-rose-50/10 focus:border-rose-500 focus:ring-rose-500/10 @else border-gray-200 focus:border-yellow-500 focus:ring-yellow-500/10 @enderror rounded-xl pl-4 pr-10 py-2.5 text-sm transition focus:outline-none focus:ring-4">
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                            </div>
                            @error('email') <span class="text-rose-600 text-xs mt-1.5 block font-medium">{{ $message }}</span> @enderror
                            
                            {{-- Info Notice Banner --}}
                            <div class="mt-2.5 bg-amber-50/60 border border-amber-100/70 rounded-xl p-3 text-xs text-amber-800 flex items-start space-x-2">
                                <svg class="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                <span>Changing your email will require an inbox verification step before updates apply.</span>
                            </div>
                        </div>

                        <button type="submit" wire:loading.attr="disabled" wire:target="updateProfile,photo"
                            class="w-full inline-flex items-center justify-center space-x-2 bg-gray-900 text-white font-medium text-sm px-6 py-2.5 rounded-xl hover:bg-gray-800 shadow-sm transition disabled:opacity-50">
                            <span wire:loading wire:target="updateProfile" class="mr-1"><x-edlink-loader size="14" /></span>
                            <span>Save Profile Changes</span>
                        </button>
                    </form>
                </div>

                <!-- Separation Line -->
                <div class="border-t border-gray-100 my-2"></div>

                {{-- SECTION 2: Security Password --}}
                <div class="space-y-5">
                    <div class="border-b border-gray-50 pb-2">
                        <h3 class="font-bold text-gray-900 text-base">Change Password</h3>
                    </div>

                    @if (session('password_status'))
                        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm rounded-xl px-4 py-3 flex items-center space-x-2">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ session('password_status') }}</span>
                        </div>
                    @endif

                    <form wire:submit="updatePassword" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Current Password</label>
                            <input type="password" wire:model="current_password" required
                                class="w-full border @error('current_password') border-rose-300 focus:border-rose-500 focus:ring-rose-500/10 @else border-gray-200 focus:border-yellow-500 focus:ring-yellow-500/10 @enderror rounded-xl px-4 py-2.5 text-sm transition focus:outline-none focus:ring-4">
                            @error('current_password') <span class="text-rose-600 text-xs mt-1.5 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">New Password</label>
                                <input type="password" wire:model="new_password" required
                                    class="w-full border @error('new_password') border-rose-300 focus:border-rose-500 focus:ring-rose-500/10 @else border-gray-200 focus:border-yellow-500 focus:ring-yellow-500/10 @enderror rounded-xl px-4 py-2.5 text-sm transition focus:outline-none focus:ring-4">
                                @error('new_password') <span class="text-rose-600 text-xs mt-1.5 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Confirm Password</label>
                                <input type="password" wire:model="new_password_confirmation" required
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 transition focus:outline-none">
                            </div>
                        </div>

                        <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword"
                            class="w-full inline-flex items-center justify-center space-x-2 bg-yellow-400 text-gray-900 font-bold text-sm px-6 py-2.5 rounded-xl hover:bg-yellow-300 shadow-sm transition disabled:opacity-50">
                            <span wire:loading wire:target="updatePassword" class="mr-1"><x-edlink-loader size="14" /></span>
                            <span>Update Password</span>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    @endif
</div>
