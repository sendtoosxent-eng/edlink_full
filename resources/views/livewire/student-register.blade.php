<div class="w-full max-w-none mx-auto" x-data="{ photoPreviewUrl: null, setPhotoPreview(event) { const file = event.target.files?.[0]; this.photoPreviewUrl = file ? URL.createObjectURL(file) : null; } }">

    {{-- Form Header --}}
     <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <h1 class="text-2xl font-bold tracking-tight text-amber-300">Register Student</h1>
        <p class="text-gray-500 text-sm mt-1">Bio data, class assignment, parents, and social data — in four short steps.</p>
    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    {{-- Step progress wizard layout --}}
    <div class="flex items-center mb-8 bg-white p-4 rounded-2xl border border-gray-100 shadow-sm sm:px-6">
        @foreach(['Bio', 'Class', 'Parents', 'Social'] as $i => $label)
            @php $n = $i + 1; @endphp
            <div class="flex items-center {{ $n < 4 ? 'flex-1' : '' }}">
                <div class="flex items-center space-x-2.5">
                    <div class="p-0.5 rounded-full transition-all duration-300 {{ $step === $n ? 'ring-4 ring-yellow-400/30 border border-yellow-500' : '' }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold shadow-sm transition-colors duration-200
                            {{ $step === $n ? 'bg-yellow-400 text-darken font-bold' : ($step > $n ? 'bg-darken text-white' : 'bg-gray-100 text-gray-400') }}">
                            @if($step > $n) 
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            @else 
                                {{ $n }} 
                            @endif
                        </div>
                    </div>
                    <span class="text-xs sm:text-sm font-medium whitespace-nowrap hidden sm:inline {{ $step === $n ? 'text-darken font-bold' : 'text-gray-400' }}">
                        {{ $label }}
                    </span>
                </div>
                @if($n < 4)
                    <div class="flex-1 h-0.5 mx-4 rounded-full transition-colors duration-300 {{ $step > $n ? 'bg-darken' : 'bg-gray-100' }}"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Full Screen Grid Workspace Split --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        {{-- Left Grid Container: Form Wizard inputs --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:p-8 ring-4 ring-yellow-400/10">

            {{-- STEP 1: Bio data --}}
            @if($step === 1)
                <div class="border-b border-gray-100 pb-4 mb-5">
                    <h2 class="text-lg font-bold text-darken">Bio data</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Primary personal identity metrics parameters.</p>
                </div>
                
                <form wire:submit="goToStep2" class="space-y-5">
                    {{-- Profile Photo Upload Row --}}
                    <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="p-0.5 rounded-full ring-4 ring-yellow-400/20 border border-yellow-400 flex-shrink-0">
                            <div class="w-14 h-14 rounded-full bg-white flex items-center justify-center overflow-hidden border border-gray-100 shadow-inner">
                                <template x-if="photoPreviewUrl">
                                    <img :src="photoPreviewUrl" class="w-full h-full object-cover" alt="Preview">
                                </template>
                                <template x-if="!photoPreviewUrl">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label class="inline-block cursor-pointer text-xs font-semibold text-darken bg-white border border-gray-200 shadow-sm rounded-lg px-4 py-2 hover:bg-gray-50 hover:border-gray-300 transition-all">
                                <span wire:loading.remove wire:target="photo">Upload image</span>
                                <span wire:loading wire:target="photo" class="inline-flex items-center space-x-2"><x-edlink-loader size="12" /><span>Uploading…</span></span>
                                <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="hidden" x-on:change="setPhotoPreview($event)">
                            </label>
                            @error('photo') <span class="text-red-500 text-xs block mt-1 font-medium">{{ $message }}</span> @enderror
                            <span class="mt-1 block text-[10px] text-gray-400">JPG, PNG or WebP up to 4 MB</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Full name</label>
                        <input type="text" wire:model.live="name" required autofocus
                            class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                        @error('name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div class="flex items-end">
                            <p class="w-full rounded-xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-2.5 text-xs text-gray-500">
                                Student ID is generated automatically when registration is completed.
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Gender</label>
                            <select wire:model.live="gender" class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Date of birth</label>
                            <input type="date" wire:model="date_of_birth"
                                class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Admission date</label>
                            <input type="date" wire:model="admission_date" required
                                class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                            @error('admission_date') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-50">
                        <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-darken font-bold px-8 py-2.5 rounded-xl shadow-sm hover:shadow transition-all focus:outline-none focus:ring-4 focus:ring-yellow-400/30">Continue</button>
                    </div>
                </form>
            @endif

            {{-- STEP 2: Class data --}}
            @if($step === 2)
                <div class="border-b border-gray-100 pb-4 mb-5">
                    <h2 class="text-lg font-bold text-darken">Class data</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Assign academic placement parameters.</p>
                </div>

                <form wire:submit="goToStep3" class="space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Class</label>
                        <select wire:model.live="school_class_id" class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                            <option value="">Select a class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('school_class_id') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Stream</label>
                        <select wire:model.live="stream_id" class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                            <option value="">{{ $this->streamsForClass->isEmpty() ? 'No streams for this class' : 'Select a stream' }}</option>
                            @foreach($this->streamsForClass as $stream)
                                <option value="{{ $stream->id }}">{{ $stream->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Category</label>
                        <select wire:model.live="student_category_id" class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                            <option value="">Select a category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('student_category_id') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        @if($categories->isEmpty())
                            <p class="text-xs text-amber-600 mt-1.5 flex items-center space-x-1">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                <span>No categories yet — <a href="{{ route('student-categories.index') }}" wire:navigate class="underline font-medium hover:text-amber-700">add one first</a>.</span>
                            </p>
                        @endif
                    </div>

                    {{-- Live fee mapping preview card --}}
                    @if($school_class_id && $student_category_id)
                        <div class="rounded-xl border shadow-inner transition-all duration-300 {{ $this->mappedFee !== null ? 'border-green-200 bg-green-50/50 text-green-800' : 'border-amber-200 bg-amber-50/50 text-amber-800' }} px-4 py-3 text-sm flex items-start space-x-2.5">
                            @if($this->mappedFee !== null)
                                <svg class="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                <div><span class="font-bold">Fee auto-mapped:</span> UGX {{ number_format($this->mappedFee) }} for this term.</div>
                            @else
                                <svg class="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                                <div>No fee amount set for this class + category combination yet. You can still register the student — <a href="{{ route('fee-structures.index') }}" wire:navigate class="underline font-bold hover:text-amber-900">set it up here</a>.</div>
                            @endif
                        </div>
                    @endif

                    <div class="flex justify-between pt-4 border-t border-gray-50">
                        <button type="button" wire:click="back" class="border border-gray-200 shadow-sm text-gray-600 font-semibold px-6 py-2.5 rounded-xl hover:bg-gray-50 transition-all">Back</button>
                        <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-darken font-bold px-8 py-2.5 rounded-xl shadow-sm hover:shadow transition-all focus:outline-none focus:ring-4 focus:ring-yellow-400/30">Continue</button>
                    </div>
                </form>
            @endif

            {{-- STEP 3: Parents data --}}
            @if($step === 3)
                <div class="border-b border-gray-100 pb-4 mb-5">
                    <h2 class="text-lg font-bold text-darken">Parents / Guardian data</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Primary emergency dashboard point-of-contact registration parameters.</p>
                </div>

                <form wire:submit="goToStep4" class="space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Full name</label>
                        <input type="text" wire:model.live="guardian_name" required autofocus
                            class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                        @error('guardian_name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Relationship</label>
                            <select wire:model.live="guardian_relationship" class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                                <option value="Parent">Parent</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Guardian">Guardian</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Phone</label>
                            <input type="text" wire:model.live="guardian_phone"
                                class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Email address</label>
                        <input type="email" wire:model="guardian_email"
                            class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                        @error('guardian_email') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Physical address</label>
                        <textarea wire:model.live="guardian_address" rows="2"
                            class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all"></textarea>
                    </div>

                    <div class="flex justify-between pt-4 border-t border-gray-50">
                        <button type="button" wire:click="back" class="border border-gray-200 shadow-sm text-gray-600 font-semibold px-6 py-2.5 rounded-xl hover:bg-gray-50 transition-all">Back</button>
                        <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-darken font-bold px-8 py-2.5 rounded-xl shadow-sm hover:shadow transition-all focus:outline-none focus:ring-4 focus:ring-yellow-400/30">Continue</button>
                    </div>
                </form>
            @endif

            {{-- STEP 4: Social data --}}
            @if($step === 4)
                <div class="border-b border-gray-100 pb-4 mb-5">
                    <h2 class="text-lg font-bold text-darken">Social / Medical data</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Final metrics profiles mapping parameters.</p>
                </div>

                <form wire:submit="register" class="space-y-5">
                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Nationality</label>
                            <input type="text" wire:model.live="nationality"
                                class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Religion</label>
                            <input type="text" wire:model.live="religion"
                                class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Blood group</label>
                        <input type="text" wire:model.live="blood_group" placeholder="e.g. O+"
                            class="w-full sm:w-40 border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Home address</label>
                        <textarea wire:model.live="home_address" rows="2"
                            class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Medical notes</label>
                        <textarea wire:model="medical_notes" rows="2" placeholder="Allergies, conditions, regular medications, etc. (optional)"
                            class="w-full border border-gray-200 bg-gray-50/50 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30 focus:border-yellow-400 transition-all"></textarea>
                    </div>

                    <div class="flex justify-between pt-4 border-t border-gray-50">
                        <button type="button" wire:click="back" class="border border-gray-200 shadow-sm text-gray-600 font-semibold px-6 py-2.5 rounded-xl hover:bg-gray-50 transition-all">Back</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="register"
                            class="inline-flex items-center space-x-2 bg-darken text-white font-bold px-8 py-2.5 rounded-xl shadow-sm hover:shadow hover:bg-opacity-95 transition-all disabled:opacity-60 focus:outline-none focus:ring-4 focus:ring-darken/20">
                            <span wire:loading wire:target="register"><x-edlink-loader size="14" /></span>
                            <span>Finish registration</span>
                        </button>
                    </div>
                </form>
            @endif

        </div>

        {{-- Right Grid Container: Dynamic Student Preview Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24 ring-4 ring-yellow-400/10">
            <div class="border-b border-gray-100 pb-3 mb-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Live Preview</h3>
            </div>

            <div class="flex flex-col items-center text-center p-4 bg-gray-50/50 rounded-2xl border border-gray-100 mb-5 relative overflow-hidden">
                <div class="absolute top-3 right-3">
                    @if($is_active ?? true)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                            Inactive
                        </span>
                    @endif
                </div>

                {{-- Image display area --}}
                <div class="p-0.5 rounded-full ring-4 ring-yellow-400/30 border border-yellow-400 shadow-sm mb-3">
                    <div class="w-24 h-24 rounded-full bg-white flex items-center justify-center overflow-hidden border border-gray-100 shadow-inner">
                        <template x-if="photoPreviewUrl">
                            <img :src="photoPreviewUrl" class="w-full h-full object-cover" alt="Live Preview Profile">
                        </template>
                        <template x-if="!photoPreviewUrl">
                            <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </template>
                    </div>
                </div>

                <h4 class="text-base font-bold text-darken transition-all truncate max-w-full">
                    {{ trim($name ?? '') ?: 'Student Name' }}
                </h4>
                <p class="text-xs text-gray-400 mt-0.5 font-medium uppercase tracking-wider">
                    ID: GENERATED ON SAVE
                </p>
                
                @if($gender)
                    <span class="mt-2 inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium capitalize {{ $gender === 'male' ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-pink-50 text-pink-700 border border-pink-100' }}">
                        {{ $gender }}
                    </span>
                @endif
            </div>

            {{-- Metadata stack profile info --}}
            <div class="space-y-3.5 text-sm mb-5">
                <div class="flex justify-between items-start py-2 border-b border-gray-50">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Academic Placement</span>
                    <span class="font-bold text-darken text-right">
                        @if($school_class_id)
                            {{ $classes->firstWhere('id', $school_class_id)?->name ?? 'Class Assigned' }}
                            @if($stream_id && method_exists($this, 'getStreamsForClassProperty'))
                                &middot; {{ $this->streamsForClass->firstWhere('id', $stream_id)?->name ?? '' }}
                            @endif
                        @else
                            <span class="text-gray-300 font-normal italic text-xs">Unassigned</span>
                        @endif
                    </span>
                </div>

                <div class="flex justify-between items-start py-2 border-b border-gray-50">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Primary Guardian</span>
                    <div class="text-right">
                        <span class="font-bold text-darken block">{{ trim($guardian_name ?? '') ?: 'Not provided' }}</span>
                        @if($guardian_relationship || $guardian_phone)
                            <span class="text-xs text-gray-400 block mt-0.5">
                                {{ $guardian_relationship ?? 'Contact' }} @if($guardian_phone) &middot; {{ $guardian_phone }} @endif
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex justify-between items-start py-2 border-b border-gray-50">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Home / Physical Address</span>
                    <span class="text-darken font-medium text-right max-w-[60%] truncate">
                        {{ trim($home_address ?? '') ?: trim($guardian_address ?? '') ?: 'Not provided' }}
                    </span>
                </div>

                <div class="flex justify-between items-start py-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Social Matrix</span>
                    <div class="text-right space-y-0.5">
                        @if($nationality || $religion || $blood_group)
                            <span class="font-bold text-darken block text-xs">
                                @if($nationality){{ $nationality }} @endif
                                @if($religion) &middot; {{ $religion }} @endif
                            </span>
                            @if($blood_group)
                                <span class="inline-block bg-red-50 border border-red-100 text-red-700 text-[10px] font-bold px-1.5 py-0.5 rounded mt-1">
                                    Blood Type: {{ strtoupper($blood_group) }}
                                </span>
                            @endif
                        @else
                            <span class="text-gray-300 font-normal italic text-xs">Awaiting Step 4</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Interactive State Toggle Button --}}
            <div class="pt-4 border-t border-gray-100">
                <button type="button" wire:click="toggleStatus" 
                    class="w-full inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all duration-200 focus:outline-none focus:ring-4
                    {{ ($is_active ?? true) 
                        ? 'bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 focus:ring-red-400/20' 
                        : 'bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 focus:ring-green-400/20' }}">
                    
                    @if($is_active ?? true)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                        <span>Deactivate Student</span>
                    @else
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Activate Student</span>
                    @endif
                </button>
            </div>
        </div>

    </div>
</div>
