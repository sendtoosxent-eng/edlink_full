<div x-data="{ photoPreviewUrl: null, setPhotoPreview(event) { const file = event.target.files?.[0]; this.photoPreviewUrl = file ? URL.createObjectURL(file) : null; } }">
    @if($isOpen)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.close()">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="close"></div>

        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-100">

            <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between z-10">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <h2 class="text-lg font-bold text-gray-900 tracking-tight">Edit Student</h2>
                </div>
                <button wire:click="close" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-8">
                <form wire:submit="save" class="space-y-8">

                    {{-- Bio --}}
                    <div class="space-y-5">
                        <div class="border-b border-gray-50 pb-2"><h3 class="font-bold text-gray-900 text-base">Bio data</h3></div>

                        <div class="flex items-center space-x-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100/80">
                            <div class="w-16 h-16 rounded-full bg-white p-1 ring-2 ring-yellow-400/70 overflow-hidden flex-shrink-0 shadow-sm">
                                <div class="w-full h-full rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                                    <template x-if="photoPreviewUrl">
                                        <img :src="photoPreviewUrl" class="w-full h-full object-cover" alt="Preview">
                                    </template>
                                    <template x-if="!photoPreviewUrl">
                                        <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </template>
                                </div>
                            </div>
                            <label class="inline-block cursor-pointer text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl px-4 py-2 hover:bg-gray-50 shadow-sm transition">
                                <span wire:loading.remove wire:target="photo">Change photo</span>
                                <span wire:loading wire:target="photo" class="inline-flex items-center space-x-1.5"><x-edlink-loader size="12" /><span>Uploading…</span></span>
                                <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="hidden" x-on:change="setPhotoPreview($event)">
                            </label>
                            @error('photo') <span class="text-rose-600 text-xs block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Full name</label>
                            <input type="text" wire:model="name" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                            @error('name') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Admission no.</label>
                                <input type="text" wire:model="admission_no" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Gender</label>
                                <select wire:model="gender" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                                    <option value="">—</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Date of birth</label>
                                <input type="date" wire:model="date_of_birth" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                            </div>
                        </div>
                    </div>

                    {{-- Class --}}
                    <div class="space-y-5">
                        <div class="border-b border-gray-50 pb-2"><h3 class="font-bold text-gray-900 text-base">Class data</h3></div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Class</label>
                                <select wire:model.live="school_class_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                                    <option value="">Select</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                                @error('school_class_id') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Stream</label>
                                <select wire:model="stream_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                                    <option value="">—</option>
                                    @foreach($this->streamsForClass as $stream)
                                        <option value="{{ $stream->id }}">{{ $stream->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Category</label>
                                <select wire:model.live="student_category_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                                    <option value="">Select</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('student_category_id') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        @if($school_class_id && $student_category_id)
                            <div class="rounded-xl border {{ $this->mappedFee !== null ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }} px-4 py-3">
                                @if($this->mappedFee !== null)
                                    <p class="text-sm text-green-700"><span class="font-semibold">Fee:</span> UGX {{ number_format($this->mappedFee) }} this term.</p>
                                @else
                                    <p class="text-sm text-amber-700">No fee amount set for this class + category yet.</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Parents --}}
                    <div class="space-y-5">
                        <div class="border-b border-gray-50 pb-2"><h3 class="font-bold text-gray-900 text-base">Parents / Guardian data</h3></div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Full name</label>
                            <input type="text" wire:model="guardian_name" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                            @error('guardian_name') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Relationship</label>
                                <input type="text" wire:model="guardian_relationship" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Phone</label>
                                <input type="text" wire:model="guardian_phone" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Email</label>
                            <input type="email" wire:model="guardian_email" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                            @error('guardian_email') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Social --}}
                    <div class="space-y-5">
                        <div class="border-b border-gray-50 pb-2"><h3 class="font-bold text-gray-900 text-base">Social data</h3></div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Nationality</label>
                                <input type="text" wire:model="nationality" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Religion</label>
                                <input type="text" wire:model="religion" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Blood group</label>
                                <input type="text" wire:model="blood_group" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Medical notes</label>
                            <textarea wire:model="medical_notes" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-yellow-500 focus:ring-4 focus:ring-yellow-500/10 focus:outline-none transition"></textarea>
                        </div>
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="save,photo"
                        class="w-full inline-flex items-center justify-center space-x-2 bg-gray-900 text-white font-medium text-sm px-6 py-2.5 rounded-xl hover:bg-gray-800 shadow-sm transition disabled:opacity-50">
                        <span wire:loading wire:target="save"><x-edlink-loader size="14" /></span>
                        <span>Save changes</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
