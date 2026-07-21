<div>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-darken">Fee Structure</h1>
        <p class="text-gray-500 text-sm mt-1">
            Set an amount per class + category for the current term.
            @if($term)
                <span class="font-medium text-darken">{{ $term->name }}, {{ $term->year }}</span>
            @endif
            — students assigned to a class and category will automatically be mapped to this fee.
        </p>
    </div>

    @if (session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3 mb-6">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-6">{{ session('error') }}</div>
    @endif

    @if(!$term)
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm rounded-lg px-4 py-3 mb-6">
            No active term found for your school — fee structures are tracked per term, so one is needed first.
        </div>
    @elseif($classes->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm rounded-lg px-4 py-3 mb-6">
            You don't have any classes yet — add one before setting up fees.
        </div>
    @elseif($categories->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm rounded-lg px-4 py-3 mb-6">
            You don't have any student categories yet — <a href="{{ route('student-categories.index') }}" wire:navigate class="underline font-medium">add one first</a>.
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Add fee structure --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
            <h2 class="font-semibold text-darken mb-4">Add a fee amount</h2>
            <form wire:submit="add" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                    <select wire:model="school_class_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                        <option value="">Select a class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('school_class_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select wire:model="student_category_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('student_category_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount (UGX)</label>
                    <input type="number" step="0.01" min="0" wire:model="amount" placeholder="e.g. 350000"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                    @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="add"
                    class="inline-flex items-center space-x-2 bg-yellow-500 text-darken font-semibold px-6 py-2.5 rounded-full hover:bg-yellow-400 transition disabled:opacity-60">
                    <span wire:loading wire:target="add"><x-edlink-loader size="16" /></span>
                    <span>Add</span>
                </button>
            </form>
        </div>

        {{-- List --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-darken mb-4">Current fee structure</h2>

            @if($structures->isEmpty())
                <p class="text-gray-400 text-sm">No fee amounts set yet for this term.</p>
            @else
                <div class="space-y-2">
                    @foreach($structures as $fs)
                        <div class="flex items-center justify-between py-3 px-3 rounded-lg border border-gray-100">
                            <div>
                                <p class="font-medium text-darken text-sm">{{ $fs->schoolClass->name }} — {{ $fs->studentCategory->name }}</p>
                                <p class="text-xs text-gray-400">UGX {{ number_format($fs->amount) }}</p>
                            </div>

                            @if($deletingId === $fs->id)
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-500">Remove?</span>
                                    <button wire:click="delete({{ $fs->id }})" class="text-xs bg-red-500 text-white px-3 py-1.5 rounded-full hover:bg-red-600">Yes</button>
                                    <button wire:click="cancelDelete" class="text-xs border border-gray-300 text-gray-600 px-3 py-1.5 rounded-full hover:bg-gray-50">Cancel</button>
                                </div>
                            @else
                                <button wire:click="confirmDelete({{ $fs->id }})" class="text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
