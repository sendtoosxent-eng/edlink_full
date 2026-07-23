<?php

use App\Models\DemoRegistration;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\SchoolAcademicSetup;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest-blank')] class extends Component
{
    public int $step = 1;

    // Step 1 — school + admin
    public string $school_name = '';
    public string $school_type = 'primary';
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Step 2 — class + stream
    public string $class_name = '';
    public string $stream_name = '';

    // Step 3 — team member
    public string $staff_name = '';
    public string $staff_email = '';
    public string $staff_role = 'teacher'; // teacher | bursar
    public string $staff_password = '';

    public function goToStep2(): void
    {
        $this->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_type' => ['required', 'in:primary,secondary,kindergarten'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (DemoRegistration::where('email', $this->email)->exists()) {
            $this->addError('email', 'This email has already used a free demo. Please log in, or contact us to upgrade.');
            return;
        }

        $this->step = 2;
    }

    public function goToStep3(): void
    {
        $this->validate([
            'class_name' => [$this->school_type === 'kindergarten' ? 'required' : 'nullable', 'string', 'max:255'],
            'stream_name' => ['required', 'string', 'max:255'],
        ]);

        $this->step = 3;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function skipStaffAndFinish(): void
    {
        $this->staff_name = '';
        $this->staff_email = '';
        $this->staff_password = '';
        $this->finish();
    }

    public function finish(): void
    {
        if ($this->staff_name || $this->staff_email || $this->staff_password) {
            $this->validate([
                'staff_name' => ['required', 'string', 'max:255'],
                'staff_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
                'staff_role' => ['required', 'in:teacher,bursar'],
                'staff_password' => ['required', 'string', 'min:8'],
            ]);
        }

        $user = DB::transaction(function () {
            $school = School::create([
                'name' => $this->school_name,
                'school_type' => $this->school_type,
                'slug' => Str::slug($this->school_name).'-'.Str::lower(Str::random(5)),
                'status' => 'demo',
                'is_demo' => true,
                'demo_expires_at' => now()->addDays(7),
            ]);

            $admin = User::create([
                'school_id' => $school->id,
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => 'admin',
            ]);

            DemoRegistration::create([
                'email' => $this->email,
                'used_at' => now(),
            ]);

            Term::create([
                'school_id' => $school->id,
                'name' => 'Term 1',
                'year' => now()->year,
                'is_current' => true,
            ]);

            SchoolAcademicSetup::provision($school);
            $class = $this->school_type === 'kindergarten'
                ? SchoolClass::create(['school_id' => $school->id, 'name' => $this->class_name, 'education_stage' => 'kindergarten'])
                : $school->classes()->orderBy('sort_order')->firstOrFail();

            $stream = Stream::create([
                'school_id' => $school->id,
                'school_class_id' => $class->id,
                'name' => $this->stream_name,
            ]);

            foreach (['A. Nakato', 'B. Kato', 'C. Mugisha'] as $i => $studentName) {
                Student::create([
                    'school_id' => $school->id,
                    'school_class_id' => $class->id,
                    'stream_id' => $stream->id,
                    'name' => $studentName,
                    'admission_no' => 'DEMO-'.$class->id.'-'.($i + 1),
                ]);
            }

            if ($this->staff_email) {
                User::create([
                    'school_id' => $school->id,
                    'name' => $this->staff_name,
                    'email' => $this->staff_email,
                    'password' => Hash::make($this->staff_password),
                    'role' => $this->staff_role,
                ]);
            }

            return $admin;
        });

        event(new Registered($user));
        Auth::login($user);

        session()->flash('new_school_number', $user->school->school_number);

        $this->redirect(route('verification.notice', absolute: false), navigate: true);
    }

    public function getSideImageProperty(): string
    {
        return match ($this->step) {
            1 => 'reg.png',
            2 => 'girl-with-books.png',
            3 => 'discussion.png',
            default => 'teacher-explaining.png',
        };
    }
}; ?>

<div class="min-h-screen flex font-sans bg-slate-50 text-slate-900" x-data="{ currentStep: @define($step) }">
    
    <!-- Left Panel Graphic Layout Area -->
    <div class="hidden lg:flex lg:w-5/12 bg-[#252641] flex-col justify-between p-12 relative overflow-hidden">
        <div>
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
    <img src="{{ asset('img/logo.png') }}" alt="Edlink logo" class="w-[180px] h-auto">
</a>
        </div>

        <!-- Center Stage Image Vector with Entry Transitions -->
        <div class="relative z-10 my-auto flex flex-col items-center text-center space-y-8" 
             wire:key="display-image-{{ $step }}"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">
            
            <img src="{{ asset('img/' . $this->sideImage) }}" alt="Onboarding graphic" class="max-h-[400px] w-auto object-contain drop-shadow-2xl transition-all duration-500 transform hover:scale-105">
            
            <div class="max-w-xs space-y-2">
                <h3 class="text-white font-bold text-lg tracking-tight">
                    @if($step === 1) Complete registration profile @endif
                    @if($step === 2) Organize student classes @endif
                    @if($step === 3) Link your team nodes @endif
                </h3>
                <p class="text-slate-300 text-xs leading-relaxed">
                    Set up your institution options seamlessly. Change configurations anytime directly from the admin panel dashboard.
                </p>
            </div>
        </div>

       
        
        <!-- Background Ornaments -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-20 -mt-20 blur-2xl"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-yellow-400/5 rounded-full -ml-30 -mb-30 blur-3xl"></div>
    </div>

    <!-- Right Side Direct Input Form Engine -->
    <div class="w-full lg:w-7/12 flex flex-col justify-between p-6 sm:p-16 md:p-24 bg-white relative overflow-hidden">
        
        <!-- Navigation Breadcrumbs Top Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-6">
            <div class="flex items-center gap-6">
                <span class="text-xs font-bold uppercase tracking-wider {{ $step === 1 ? 'text-[#252641] border-b-2 border-yellow-500 pb-1' : 'text-slate-400' }}">01. Profile</span>
                <span class="text-xs font-bold uppercase tracking-wider {{ $step === 2 ? 'text-[#252641] border-b-2 border-yellow-500 pb-1' : 'text-slate-400' }}">02. Class Structure</span>
                <span class="text-xs font-bold uppercase tracking-wider {{ $step === 3 ? 'text-[#252641] border-b-2 border-yellow-500 pb-1' : 'text-slate-400' }}">03. Collaboration</span>
            </div>
            
            <span class="text-xs font-bold text-slate-400 hidden sm:inline">Demo Mode</span>
        </div>

        <!-- Main Form Wrapper Box with Animated Transition Entry -->
        <div class="my-auto py-12 max-w-xl w-full mx-auto" 
             wire:key="step-form-{{ $step }}"
             x-transition:enter="transition ease-out duration-400 delay-100"
             x-transition:enter-start="opacity-0 scale-[0.98] translate-x-4"
             x-transition:enter-end="opacity-100 scale-100 translate-x-0">
            
            {{-- STEP 1: Main Register Interface Form Fields --}}
            @if ($step === 1)
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold text-[#252641] tracking-tight">Create your demo workspace</h1>
                    <p class="text-sm text-slate-500 mt-1">Fill out the basic info blocks below to unlock full standard system access.</p>
                </div>

                <form wire:submit="goToStep2" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Official School Name</label>
                        <input type="text" wire:model="school_name" required autofocus placeholder="e.g. St Monica ss"
                            class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm transition-all focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 placeholder:text-slate-300">
                        @error('school_name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">School Type</label>
                        <select wire:model.live="school_type" required class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400">
                            <option value="primary">Primary school</option>
                            <option value="secondary">Secondary school</option>
                            <option value="kindergarten">Kindergarten</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">This is selected once and determines standard classes, grading profiles, and reports.</p>
                        @error('school_type') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Your Full Name</label>
                        <input type="text" wire:model="name" required placeholder="Musis osxent"
                            class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm transition-all focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 placeholder:text-slate-300">
                        @error('name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Address</label>
                        <input type="email" wire:model="email" required placeholder="you@school.com"
                            class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm transition-all focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 placeholder:text-slate-300">
                        @error('email') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password</label>
                            <input type="password" wire:model="password" required placeholder="••••••••"
                                class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm transition-all focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 placeholder:text-slate-300">
                            @error('password') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Confirm Password</label>
                            <input type="password" wire:model="password_confirmation" required placeholder="••••••••"
                                class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm transition-all focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 placeholder:text-slate-300">
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-[#252641] hover:bg-[#32345a] text-white font-bold py-3.5 rounded-xl transition shadow-md active:scale-[0.99]">
                            Continue &rarr;
                        </button>
                    </div>
                </form>

                <p class="text-sm text-slate-500 mt-8">
                    Already registered? <a href="{{ route('login') }}" wire:navigate class="text-[#252641] font-bold hover:underline">Log in to your dashboard</a>
                </p>
            @endif

            {{-- STEP 2: Structural Inputs Setup --}}
            @if ($step === 2)
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold text-[#252641] tracking-tight">Setup classes & streams</h1>
                    <p class="text-sm text-slate-500 mt-1">Specify your entry level academic blocks. Edlink will pre-fill mock students inside this choice.</p>
                </div>

                <form wire:submit="goToStep3" class="space-y-6">
                    @if ($school_type === 'kindergarten')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Class Name</label>
                        <input type="text" wire:model="class_name" required autofocus placeholder="e.g. Primary 5, or Senior 1"
                            class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm transition-all focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 placeholder:text-slate-300">
                        @error('class_name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                    @else
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ $school_type === 'primary' ? 'Primary 1–7' : 'Senior 1–6' }} will be created automatically and protected from deletion.</div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Stream Name</label>
                        <input type="text" wire:model="stream_name" required placeholder="e.g. Blue, North, Stream A"
                            class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm transition-all focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 placeholder:text-slate-300">
                        @error('stream_name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-4 pt-4">
                        <button type="button" wire:click="back" class="w-1/3 border border-slate-200 text-slate-600 font-bold py-3.5 rounded-xl hover:bg-slate-50 transition active:scale-[0.99]">
                            Back
                        </button>
                        <button type="submit" class="w-2/3 bg-[#252641] hover:bg-[#32345a] text-white font-bold py-3.5 rounded-xl transition shadow-md active:scale-[0.99]">
                            Continue
                        </button>
                    </div>
                </form>
            @endif

            {{-- STEP 3: Staff Assignment Field Elements --}}
            @if ($step === 3)
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold text-[#252641] tracking-tight">Onboard a staff member</h1>
                    <p class="text-sm text-slate-500 mt-1">Add a teacher or financial bursar node to preview full platform collaborative workflows.</p>
                </div>

                <form wire:submit="finish" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Staff Full Name</label>
                            <input type="text" wire:model="staff_name" autofocus placeholder="Sarah Namubiru"
                                class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm transition-all focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 placeholder:text-slate-300">
                            @error('staff_name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Assigned System Role</label>
                            <select wire:model="staff_role" class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm transition-all focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400">
                                <option value="teacher">Teacher / Instructor</option>
                                <option value="bursar">Bursar / Accountant</option>
                            </select>
                            @error('staff_role') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Staff Email Address</label>
                        <input type="email" wire:model="staff_email" placeholder="sarah@school.com"
                            class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm transition-all focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 placeholder:text-slate-300">
                        @error('staff_email') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Temporary Login Password</label>
                        <input type="password" wire:model="staff_password" placeholder="••••••••"
                            class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 text-sm transition-all focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 placeholder:text-slate-300">
                        @error('staff_password') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-4 pt-4">
                        <button type="button" wire:click="back" class="w-1/3 border border-slate-200 text-slate-600 font-bold py-3.5 rounded-lg hover:bg-slate-50 transition active:scale-[0.99]">
                            Back
                        </button>
                        <button type="submit" class="w-2/3 bg-yellow-400 hover:bg-yellow-500 text-[#252641] font-black py-3.5 rounded-lg transition shadow-md active:scale-[0.99]">
                            Launch Workspace &rarr;
                        </button>
                    </div>
                </form>

                <button wire:click="skipStaffAndFinish" class="w-full text-center text-xs font-bold text-slate-400 tracking-wider uppercase hover:text-red-500 transition mt-6 block">
                    Skip this configuration step
                </button>
            @endif

        </div>
 <div class="text-[11px] text-slate-400 font-medium">
            &copy; {{ now()->year }} Edlink Systems &bull; Spotnet Technologies.
        </div>
        <!-- Mobile Layout Footer Area -->
        {{-- <div class="border-t border-slate-100 pt-6 text-[11px] text-slate-400 flex items-center justify-between lg:hidden">
            <span>&copy; {{ now()->year }} Edlink Systems.</span>
            <span>Powered by Spotnet Technologies.</span>
        </div> --}}

    </div>
</div>