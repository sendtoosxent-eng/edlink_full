@php $professionalInput = 'mt-1.5 w-full rounded-xl border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-amber-400 focus:ring-amber-400'; @endphp
<div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
    <div>
        <h3 class="text-sm font-bold text-slate-900">Personal and professional details</h3>
        <p class="mt-1 text-xs text-slate-500">All fields in this section are optional. You can add or update them later from the staff profile.</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <label class="text-xs font-semibold text-slate-700">Date of birth
            <input type="date" wire:model="date_of_birth" max="{{ now()->toDateString() }}" class="{{ $professionalInput }}">
            @error('date_of_birth')<span class="mt-1 block text-rose-600">{{ $message }}</span>@enderror
        </label>
        <label class="text-xs font-semibold text-slate-700">Gender
            <select wire:model="gender" class="{{ $professionalInput }}">
                <option value="">Not specified</option>
                @foreach (['Female', 'Male', 'Other', 'Prefer not to say'] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
            </select>
            @error('gender')<span class="mt-1 block text-rose-600">{{ $message }}</span>@enderror
        </label>
        @foreach (['nationality' => ['Nationality', 'e.g. Ugandan'], 'teaching_license_number' => ['Teaching licence / registration number', 'Enter licence number, if available'], 'qualification_institution' => ['Qualification institution', 'University, college or training institution']] as $field => [$label, $placeholder])
            <label class="text-xs font-semibold text-slate-700">{{ $label }}
                <input wire:model="{{ $field }}" maxlength="{{ $field === 'qualification_institution' ? 255 : 100 }}" placeholder="{{ $placeholder }}" class="{{ $professionalInput }}">
                @error($field)<span class="mt-1 block text-rose-600">{{ $message }}</span>@enderror
            </label>
        @endforeach
        <label class="text-xs font-semibold text-slate-700">Qualification year
            <input wire:model="qualification_year" type="number" min="1900" max="{{ now()->year }}" placeholder="e.g. 2020" class="{{ $professionalInput }}">
            @error('qualification_year')<span class="mt-1 block text-rose-600">{{ $message }}</span>@enderror
        </label>
        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Academic qualifications
            <textarea wire:model="academic_qualifications" rows="3" maxlength="5000" placeholder="e.g. Bachelor of Education in Mathematics; Diploma in Secondary Education" class="{{ $professionalInput }}"></textarea>
            @error('academic_qualifications')<span class="mt-1 block text-rose-600">{{ $message }}</span>@enderror
        </label>
    </div>
</div>
